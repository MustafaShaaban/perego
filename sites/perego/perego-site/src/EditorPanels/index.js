/**
 * Perego post-type sidebar panels (spec 021 Phase 4 / T019, T022; DECISIONS 2026-07-22).
 *
 * Registers one editor plugin that adds typed, grouped field panels to the document sidebar of every
 * Perego post type, replacing four separate classic meta boxes — `PostMetaBoxes`,
 * `ProjectGalleryMetaBox`, `ClientMediaMetaBox` and `ServicePortfolioMetaBox` — each of which carried
 * its own nonce, save handler and inline JS.
 *
 * Everything moves through `useEntityProp( 'postType', type, 'meta' )`: the meta is already
 * `show_in_rest` on all three post types, so there is **no nonce, no save handler and no page
 * reload** — a field edit is part of the post's normal save. **No meta key changes and no data
 * migration**: the panels write exactly the keys the renderers already read.
 *
 * The gallery and portfolio pickers reuse the shared block-Inspector primitives (`MediaField`,
 * `RecordPicker`) rather than the bespoke inline JS the meta boxes had — the same controls an editor
 * already knows from the blocks.
 */
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { registerPlugin } from '@wordpress/plugins';
import { Button } from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { RecordPicker } from '../Editor/RecordPicker';
import { Field, FieldGroup } from './fields';
import {
	CLIENT_PANELS,
	CLIENT_POST_TYPE,
	LEGAL_PANEL,
	PORTFOLIO_MODE_OPTIONS,
	PROJECT_PANELS,
	PROJECT_POST_TYPE,
	PROJECT_TAXONOMY,
	SERVICE_PANELS,
	SERVICE_POST_TYPE,
	WEB_CATEGORY_SLUG,
} from './schema';
import './style.scss';

/** Mirror of `ProjectPostType::META_GALLERY`. */
const PROJECT_GALLERY_KEY = '_perego_gallery_attachment_ids';
/** Mirror of `ServicePostType::META_PORTFOLIO_*`. */
const PORTFOLIO_MODE_KEY = '_perego_service_portfolio_mode';
const PORTFOLIO_PROJECTS_KEY = '_perego_service_portfolio_project_ids';
const PORTFOLIO_EXCLUDE_KEY = '_perego_service_portfolio_exclude_ids';

/** The current post's meta plus a patching setter, the shape every panel below wants. */
function usePostMeta( postType ) {
	const [ meta, setMetaRaw ] = useEntityProp( 'postType', postType, 'meta' );

	return [ meta || {}, ( patch ) => setMetaRaw( { ...meta, ...patch } ) ];
}

/**
 * A multi-image picker storing an ordered list of attachment IDs — replacing `ProjectGalleryMetaBox`,
 * whose IDs rode in one comma-separated hidden field.
 */
function GalleryPanel( { ids, onChange } ) {
	const images = useSelect(
		( select ) => ids.map( ( id ) => select( 'core' ).getMedia( id ) ).filter( Boolean ),
		[ ids ]
	);

	return (
		<div className="perego-panel-gallery">
			{ images.length > 0 && (
				<ul className="perego-panel-gallery__grid">
					{ images.map( ( image ) => (
						<li key={ image.id }>
							<img src={ image.media_details?.sizes?.thumbnail?.source_url || image.source_url } alt={ image.alt_text || '' } />
							<Button size="small" isDestructive variant="tertiary"
								onClick={ () => onChange( ids.filter( ( id ) => id !== image.id ) ) }>
								{ __( 'Remove', 'perego-site' ) }
							</Button>
						</li>
					) ) }
				</ul>
			) }
			<MediaUploadCheck>
				<MediaUpload
					multiple
					gallery
					allowedTypes={ [ 'image' ] }
					value={ ids }
					onSelect={ ( selected ) => onChange( selected.map( ( item ) => item.id ) ) }
					render={ ( { open } ) => (
						<Button variant="secondary" onClick={ open }>
							{ ids.length > 0 ? __( 'Edit gallery', 'perego-site' ) : __( 'Add images', 'perego-site' ) }
						</Button>
					) }
				/>
			</MediaUploadCheck>
			{ ids.length === 0 && (
				<p className="perego-editor-help">
					{ __( 'No gallery images yet. The project single hides the gallery until you add some.', 'perego-site' ) }
				</p>
			) }
		</div>
	);
}

function ProjectPanels() {
	const [ meta, setMeta ] = usePostMeta( PROJECT_POST_TYPE );
	const [ terms ] = useEntityProp( 'postType', PROJECT_POST_TYPE, PROJECT_TAXONOMY );

	// The Website-showcase fields only apply to web-category projects; resolve the assigned terms so
	// the panel can hide itself rather than offering fields that will never render.
	const isWebProject = useSelect( ( select ) => {
		if ( ! Array.isArray( terms ) || terms.length === 0 ) {
			return false;
		}

		return terms.some( ( termId ) => {
			const term = select( 'core' ).getEntityRecord( 'taxonomy', PROJECT_TAXONOMY, termId );
			const slug = term?.slug;

			// Polylang gives every language its own term, so the Arabic Website Making category is
			// `web-ar`. Match the canonical slug or a `web-<locale>` variant — not a bare prefix,
			// which would also swallow an unrelated future term like `webinar`.
			return slug === WEB_CATEGORY_SLUG || slug?.startsWith( `${ WEB_CATEGORY_SLUG }-` );
		} );
	}, [ terms ] );

	const galleryIds = Array.isArray( meta[ PROJECT_GALLERY_KEY ] ) ? meta[ PROJECT_GALLERY_KEY ] : [];

	return (
		<>
			{ PROJECT_PANELS.filter( ( panel ) => ! panel.showWhen || panel.showWhen( meta, { isWebProject } ) )
				.map( ( panel ) => (
					<PluginDocumentSettingPanel key={ panel.title } name={ `perego-project-${ panel.title }` }
						title={ panel.title } className="perego-panel">
						<FieldGroup fields={ panel.fields } meta={ meta } setMeta={ setMeta }
							context={ { isWebProject } } />
					</PluginDocumentSettingPanel>
				) ) }
			<PluginDocumentSettingPanel name="perego-project-gallery"
				title={ __( 'Project gallery', 'perego-site' ) } className="perego-panel">
				<GalleryPanel ids={ galleryIds }
					onChange={ ( next ) => setMeta( { [ PROJECT_GALLERY_KEY ]: next } ) } />
			</PluginDocumentSettingPanel>
		</>
	);
}

function ServicePanels() {
	const [ meta, setMeta ] = usePostMeta( SERVICE_POST_TYPE );
	const projects = useSelect(
		( select ) => select( 'core' ).getEntityRecords( 'postType', PROJECT_POST_TYPE, {
			per_page: 100,
			status: 'publish',
			orderby: 'title',
			order: 'asc',
		} ) || [],
		[]
	);

	const mode = meta[ PORTFOLIO_MODE_KEY ] || 'automatic';
	const chosen = Array.isArray( meta[ PORTFOLIO_PROJECTS_KEY ] ) ? meta[ PORTFOLIO_PROJECTS_KEY ] : [];
	const excluded = Array.isArray( meta[ PORTFOLIO_EXCLUDE_KEY ] ) ? meta[ PORTFOLIO_EXCLUDE_KEY ] : [];
	const projectTitle = ( project ) =>
		decodeEntities( project?.title?.rendered || '' ) || __( 'Untitled Project', 'perego-site' );

	return (
		<>
			{ SERVICE_PANELS.map( ( panel ) => (
				<PluginDocumentSettingPanel key={ panel.title } name={ `perego-service-${ panel.title }` }
					title={ panel.title } className="perego-panel">
					<FieldGroup fields={ panel.fields } meta={ meta } setMeta={ setMeta } />
				</PluginDocumentSettingPanel>
			) ) }
			<PluginDocumentSettingPanel name="perego-service-portfolio"
				title={ __( 'Selected work', 'perego-site' ) } className="perego-panel">
				<Field
					field={ {
						type: 'select',
						label: __( 'Which projects', 'perego-site' ),
						options: PORTFOLIO_MODE_OPTIONS,
						required: true,
					} }
					value={ mode }
					onChange={ ( next ) => setMeta( { [ PORTFOLIO_MODE_KEY ]: next } ) }
				/>
				{ mode !== 'automatic' && (
					<RecordPicker mode="manual" records={ projects } order={ chosen }
						getLabel={ projectTitle }
						selectedHeading={ __( 'Shown projects (in order)', 'perego-site' ) }
						addHeading={ __( 'Add a project', 'perego-site' ) }
						noneSelectedLabel={ __( 'None chosen yet.', 'perego-site' ) }
						emptyLabel={ __( 'No published projects yet.', 'perego-site' ) }
						onChangeOrder={ ( next ) => setMeta( { [ PORTFOLIO_PROJECTS_KEY ]: next } ) } />
				) }
				{ mode !== 'manual' && (
					<RecordPicker mode="automatic" records={ projects } excluded={ excluded }
						getLabel={ projectTitle }
						emptyLabel={ __( 'No published projects yet.', 'perego-site' ) }
						onChangeExcluded={ ( next ) => setMeta( { [ PORTFOLIO_EXCLUDE_KEY ]: next } ) } />
				) }
			</PluginDocumentSettingPanel>
		</>
	);
}

function ClientPanels() {
	const [ meta, setMeta ] = usePostMeta( CLIENT_POST_TYPE );

	return CLIENT_PANELS.map( ( panel ) => (
		<PluginDocumentSettingPanel key={ panel.title } name={ `perego-client-${ panel.title }` }
			title={ panel.title } className="perego-panel">
			<FieldGroup fields={ panel.fields } meta={ meta } setMeta={ setMeta } />
		</PluginDocumentSettingPanel>
	) );
}

/** The legal "Last updated" field, scoped to pages using the `legal` template. */
function LegalPanel() {
	const [ meta, setMeta ] = usePostMeta( 'page' );
	const template = useSelect(
		( select ) => select( 'core/editor' )?.getEditedPostAttribute?.( 'template' ),
		[]
	);

	if ( template !== 'legal' ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel name="perego-legal" title={ LEGAL_PANEL.title } className="perego-panel">
			<FieldGroup fields={ LEGAL_PANEL.fields } meta={ meta } setMeta={ setMeta } />
		</PluginDocumentSettingPanel>
	);
}

/** Dispatch on the post type being edited — one plugin, one registration. */
function PeregoFieldPanels() {
	const postType = useSelect(
		( select ) => select( 'core/editor' )?.getCurrentPostType?.(),
		[]
	);

	switch ( postType ) {
		case PROJECT_POST_TYPE:
			return <ProjectPanels />;
		case SERVICE_POST_TYPE:
			return <ServicePanels />;
		case CLIENT_POST_TYPE:
			return <ClientPanels />;
		case 'page':
			return <LegalPanel />;
		default:
			return null;
	}
}

registerPlugin( 'perego-field-panels', { render: PeregoFieldPanels } );
