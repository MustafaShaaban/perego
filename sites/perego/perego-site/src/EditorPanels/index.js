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
 * reload** — a field edit is part of the post's normal save.
 *
 * The gallery and portfolio pickers reuse the shared block-Inspector primitives (`MediaField`,
 * `RecordPicker`) rather than the bespoke inline JS the meta boxes had — the same controls an editor
 * already knows from the blocks.
 *
 * 2026-07-28: the Client panel goes further than the others. Where Project and Service panels are a
 * flat list of every field, a client's type decides which fields exist at all and its behaviour
 * decides which of those the card can even use — so the Client panel composes its controls rather
 * than listing them, and offers only what applies. That pass also retired the last classic meta box
 * (`ClientMediaMetaBox`) and did change client meta keys; `scripts/migrate-client-behavior.php`
 * carries the existing content across.
 */
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { registerPlugin } from '@wordpress/plugins';
import { Button, RadioControl, ToggleControl } from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { LinkPicker } from '../Editor/LinkPicker';
import { MediaField } from '../Editor/MediaField';
import { RecordPicker } from '../Editor/RecordPicker';
import { ClientGallery } from './ClientGallery';
import { Field, FieldGroup } from './fields';
import {
	CLIENT_BEHAVIOR_KEY,
	CLIENT_BEHAVIOR_OPTIONS,
	CLIENT_GALLERY_KEY,
	CLIENT_HIDE_PLAY_ICON_KEY,
	CLIENT_LINK_KEYS,
	CLIENT_PANELS,
	CLIENT_POST_TYPE,
	CLIENT_SUBTITLE_KEY,
	CLIENT_TAXONOMY,
	CLIENT_TYPE_CORPORATE,
	CLIENT_TYPE_INDIVIDUAL,
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

/**
 * Whether a `perego_client_type` slug is the given canonical type.
 *
 * Polylang gives every language its own term, so the Arabic Corporate term is `corporate-ar`. Match
 * the canonical slug or a `<slug>-<locale>` variant — not a bare prefix, which would also swallow an
 * unrelated future term.
 */
const isClientTypeSlug = ( slug, type ) => slug === type || slug?.startsWith( `${ type }-` );

/**
 * The client's type, and a setter that swaps the assigned term.
 *
 * Assignment stays inside the post's own language: switching an Arabic client from Corporate to
 * Individual must land on `individual-ar`, not the English `individual`, or the Arabic carousel — which
 * queries the locale's own term — would silently drop the card. The locale is read off the term the
 * post already carries; a brand-new client with no term yet gets the canonical (English) term, which
 * is what Polylang's own default does.
 */
function useClientType() {
	// Client type is offered once, at the top of the Client panel, so the editor's own taxonomy panel
	// is removed — two controls for one value can disagree, and the native one shows the raw term list
	// (including Polylang's per-language duplicates) with no hint that exactly one must be chosen.
	// `meta_box_cb => false` covers the classic-editor path; this covers the block editor.
	const { removeEditorPanel } = useDispatch( 'core/edit-post' );
	useEffect( () => {
		removeEditorPanel?.( `taxonomy-panel-${ CLIENT_TAXONOMY }` );
	}, [ removeEditorPanel ] );

	const [ terms, setTerms ] = useEntityProp( 'postType', CLIENT_POST_TYPE, CLIENT_TAXONOMY );
	const assigned = Array.isArray( terms ) ? terms : [];

	const allTerms = useSelect(
		( select ) => select( 'core' ).getEntityRecords( 'taxonomy', CLIENT_TAXONOMY, { per_page: 100 } ) || [],
		[]
	);

	const current = allTerms.find( ( term ) => assigned.includes( term.id ) );
	const type = [ CLIENT_TYPE_CORPORATE, CLIENT_TYPE_INDIVIDUAL ].find(
		( candidate ) => isClientTypeSlug( current?.slug, candidate )
	) || '';

	// `corporate-ar` → `-ar`; the canonical English term has no suffix.
	const localeSuffix = current && type ? current.slug.slice( type.length ) : '';

	const setType = ( next ) => {
		const term = allTerms.find( ( candidate ) => candidate.slug === `${ next }${ localeSuffix }` )
			|| allTerms.find( ( candidate ) => candidate.slug === next );

		if ( ! term ) {
			return;
		}

		// Replace rather than add: a client is one type, and two terms would put the same card in
		// both carousels.
		setTerms( [ ...assigned.filter( ( id ) => ! allTerms.some( ( candidate ) => candidate.id === id ) ), term.id ] );
	};

	return [ type, setType, allTerms.length > 0 ];
}

/** The client's featured image, presented as the Logo or the Thumbnail depending on the type. */
function ClientArtwork( { label, help } ) {
	const [ featuredMedia, setFeaturedMedia ] = useEntityProp( 'postType', CLIENT_POST_TYPE, 'featured_media' );
	const media = useSelect(
		( select ) => ( featuredMedia ? select( 'core' ).getMedia( featuredMedia ) : null ),
		[ featuredMedia ]
	);

	return (
		<>
			<MediaField
				label={ label }
				value={ featuredMedia }
				media={ media ? {
					url: media.source_url,
					alt: media.alt_text,
					width: media.media_details?.width,
					height: media.media_details?.height,
				} : null }
				onSelect={ ( selected ) => setFeaturedMedia( selected.id ) }
				onRemove={ () => setFeaturedMedia( 0 ) }
			/>
			{ help && <p className="perego-editor-help">{ help }</p> }
		</>
	);
}

/** A truthful, data-driven sketch of the card this client will render as. */
function ClientCardPreview( { type, title, subtitle, behavior, mediaUrl } ) {
	const behaviorLabel = CLIENT_BEHAVIOR_OPTIONS.find( ( option ) => option.value === behavior )?.label;

	return (
		<section className="perego-client-preview" aria-label={ __( 'Client card preview', 'perego-site' ) }>
			<div className="perego-client-preview__card">
				{ mediaUrl
					? <img src={ mediaUrl } alt="" />
					: <span className="perego-client-preview__placeholder" aria-hidden="true" /> }
				<div>
					<strong>{ title || __( 'Client name', 'perego-site' ) }</strong>
					{ type === CLIENT_TYPE_INDIVIDUAL && subtitle && (
						// The stored value may carry <strong>; showing it as text keeps the preview
						// honest about what was typed without executing it.
						<span className="perego-client-preview__sub">{ subtitle.replace( /<[^>]+>/g, '' ) }</span>
					) }
					<span className="perego-client-preview__behavior">{ behaviorLabel }</span>
				</div>
			</div>
		</section>
	);
}

/**
 * The Client panel — one place for everything a client card needs.
 *
 * Type comes first because it decides what the rest of the panel is: a Corporate client is a logo
 * tile, an Individual client is a name/subtitle/description card. Behaviour comes last because it
 * decides what the card DOES, and only the fields that behaviour actually uses are shown. Fields the
 * chosen type or behaviour cannot use are not merely ignored on save — they are not offered at all.
 */
function ClientPanels() {
	const [ meta, setMeta ] = usePostMeta( CLIENT_POST_TYPE );
	const [ type, setType, typesLoaded ] = useClientType();
	const [ title, setTitle ] = useEntityProp( 'postType', CLIENT_POST_TYPE, 'title' );
	const [ featuredMedia ] = useEntityProp( 'postType', CLIENT_POST_TYPE, 'featured_media' );
	const previewMedia = useSelect(
		( select ) => ( featuredMedia ? select( 'core' ).getMedia( featuredMedia ) : null ),
		[ featuredMedia ]
	);

	const isIndividual = type === CLIENT_TYPE_INDIVIDUAL;
	const behavior = meta[ CLIENT_BEHAVIOR_KEY ] || 'none';

	const link = Object.fromEntries(
		Object.entries( CLIENT_LINK_KEYS ).map( ( [ field, key ] ) => [ field, meta[ key ] ] )
	);
	const setLink = ( next ) => setMeta( Object.fromEntries(
		Object.entries( CLIENT_LINK_KEYS ).map( ( [ field, key ] ) => [ key, next[ field ] ?? '' ] )
	) );

	return (
		<PluginDocumentSettingPanel name="perego-client" title={ __( 'Client', 'perego-site' ) }
			className="perego-panel perego-client-panel">
			<RadioControl
				label={ __( 'Client type', 'perego-site' ) }
				help={ __( 'Decides which carousel the client appears in, and which fields below apply.', 'perego-site' ) }
				selected={ type }
				options={ [
					{ value: CLIENT_TYPE_CORPORATE, label: __( 'Corporate', 'perego-site' ) },
					{ value: CLIENT_TYPE_INDIVIDUAL, label: __( 'Individual', 'perego-site' ) },
				] }
				onChange={ setType }
			/>
			{ typesLoaded && type === '' && (
				<p className="perego-editor-help">
					{ __( 'Pick a type — until you do, this client appears in neither carousel.', 'perego-site' ) }
				</p>
			) }

			<ClientNameField title={ title } setTitle={ setTitle } isIndividual={ isIndividual } />

			<ClientArtwork
				label={ isIndividual ? __( 'Thumbnail', 'perego-site' ) : __( 'Logo', 'perego-site' ) }
				help={ isIndividual
					? __( 'The picture beside the card text.', 'perego-site' )
					: __( 'Shown on the corporate tile. This is the post’s featured image.', 'perego-site' ) }
			/>

			<FieldGroup fields={ CLIENT_PANELS[ 0 ].fields } meta={ meta } setMeta={ setMeta }
				context={ { isIndividual } } />

			{ /*
			   * Labelled like a field rather than left as a loose sentence. Without the label it
			   * ran straight on from the Subtitle's help text and the two read as one instruction.
			   */ }
			{ isIndividual && (
				<div className="perego-editor-note">
					<span className="perego-editor-note__label">{ __( 'Content', 'perego-site' ) }</span>
					<p className="perego-editor-help">
						{ __( 'The paragraph below the subtitle. Write it in the main editor area — the post’s own content is the card’s body.', 'perego-site' ) }
					</p>
				</div>
			) }

			<div className="perego-client-panel__group">
				<RadioControl
					label={ __( 'Behavior', 'perego-site' ) }
					help={ __( 'What happens when a visitor clicks the card. “No actions” shows the information and nothing else.', 'perego-site' ) }
					selected={ behavior }
					options={ CLIENT_BEHAVIOR_OPTIONS }
					onChange={ ( next ) => setMeta( { [ CLIENT_BEHAVIOR_KEY ]: next } ) }
				/>

				{ behavior === 'lightbox' && (
					<>
						<ClientGallery
							value={ meta[ CLIENT_GALLERY_KEY ] }
							onChange={ ( next ) => setMeta( { [ CLIENT_GALLERY_KEY ]: next } ) }
						/>
						{ isIndividual && (
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __( 'Show play icon', 'perego-site' ) }
								help={ __( 'The ▶ badge over the thumbnail. Decorative — the card opens its gallery either way.', 'perego-site' ) }
								checked={ ! meta[ CLIENT_HIDE_PLAY_ICON_KEY ] }
								onChange={ ( shown ) => setMeta( { [ CLIENT_HIDE_PLAY_ICON_KEY ]: ! shown } ) }
							/>
						) }
					</>
				) }

				{ behavior === 'link' && (
					<LinkPicker
						link={ link }
						showLabel={ false }
						hrefText={ __( 'Link URL', 'perego-site' ) }
						hrefHelp={ __( 'Where the card goes when clicked.', 'perego-site' ) }
						onChange={ setLink }
					/>
				) }
			</div>

			<ClientCardPreview type={ type } title={ decodeEntities( title || '' ) }
				subtitle={ meta[ CLIENT_SUBTITLE_KEY ] } behavior={ behavior }
				mediaUrl={ previewMedia?.media_details?.sizes?.medium?.source_url || previewMedia?.source_url } />
		</PluginDocumentSettingPanel>
	);
}

/**
 * The client's name — the post title, editable here so the panel really is one place.
 *
 * The value is passed through verbatim, never decoded: the title is stored raw, and decoding it on
 * the way in would write the decoded form back on the next keystroke (an `&amp;` in a client's name
 * would quietly become a bare `&`). Decoding belongs to display, which is what the preview does.
 */
function ClientNameField( { title, setTitle, isIndividual } ) {
	return (
		<Field
			field={ {
				type: 'text',
				label: isIndividual ? __( 'Title', 'perego-site' ) : __( 'Client name', 'perego-site' ),
				help: __( 'The same value as the post title at the top of the editor.', 'perego-site' ),
			} }
			value={ title || '' }
			onChange={ setTitle }
		/>
	);
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
