/**
 * Perego services-teaser block — editor registration (spec 021 C4 / T013; DECISIONS 2026-07-22).
 *
 * The canvas renders the REAL section markup (see preview.js), styled by the theme's `main.css` (loaded
 * via `add_editor_style`), instead of a `ServerSideRender` iframe — the static-layout live-canvas standard
 * shared with site-header (C1), site-footer (C2), and hero-slider (C3). The heading and the "See All
 * Services" link text are edited in place with `RichText` (English canvas); the Arabic variants and the
 * card composer (automatic / manual / hybrid selection, ordering, and per-card show/hide) live in the
 * Inspector on the shared `../../Editor` primitives (`PanelSection`, `RecordPicker`).
 *
 * The four service cards themselves stay a projection of the `perego_service` CPT — their label/image are
 * edited on each Service's own screen (spec 012), and which cards appear on the live site follows the
 * composer — so the canvas shows the seed cards as a faithful design preview. Server-rendered (save
 * returns null); hover is pure CSS.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';
import { SelectControl, TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { LinkPicker, linkFromAttributes, linkToAttributes } from '../../Editor/LinkPicker';
import { PanelSection } from '../../Editor/PanelSection';
import { RecordPicker } from '../../Editor/RecordPicker';
import {
	SEED_HEADING_AR,
	SEED_HEADING_EN,
	SEED_SEE_ALL_AR,
	SEED_SEE_ALL_EN,
	ServicesTeaserSkeleton,
} from './preview';
import metadata from './block.json';
import './style.scss';

const serviceLabel = ( service ) => service.title?.rendered || __( 'Untitled Service', 'perego-site' );

function ServicesComposer( { attributes, services, setAttributes } ) {
	const mode = attributes.servicesMode || 'automatic';
	const order = attributes.serviceOrder || [];
	const excluded = attributes.serviceExcludeIds || [];

	return (
		<PanelSection title={ __( 'Service cards', 'perego-site' ) }>
			<SelectControl __nextHasNoMarginBottom label={ __( 'Card source', 'perego-site' ) } value={ mode }
				options={ [
					{ label: __( 'Automatic (current Service cards)', 'perego-site' ), value: 'automatic' },
					{ label: __( 'Manual (selected Services only)', 'perego-site' ), value: 'manual' },
					{ label: __( 'Hybrid (selected first, then automatic)', 'perego-site' ), value: 'hybrid' },
				] }
				onChange={ ( servicesMode ) => setAttributes( { servicesMode } ) } />
			{ mode !== 'automatic' && (
				<RecordPicker mode="manual" records={ services } order={ order }
					getLabel={ serviceLabel }
					selectedHeading={ __( 'Shown cards (in order)', 'perego-site' ) }
					addHeading={ __( 'Add a Service', 'perego-site' ) }
					noneSelectedLabel={ __( 'No Services selected yet.', 'perego-site' ) }
					emptyLabel={ __( 'No published Services are available in this language yet.', 'perego-site' ) }
					onChangeOrder={ ( serviceOrder ) => setAttributes( { serviceOrder } ) } />
			) }
			{ mode !== 'manual' && (
				<RecordPicker mode="automatic" records={ services } excluded={ excluded }
					getLabel={ serviceLabel }
					emptyLabel={ __( 'No published Services are available in this language yet.', 'perego-site' ) }
					onChangeExcluded={ ( serviceExcludeIds ) => setAttributes( { serviceExcludeIds } ) } />
			) }
			<p className="perego-editor-help">
				{ __( 'Each card’s label and image are edited on that Service’s own screen (Services in the admin menu).', 'perego-site' ) }
			</p>
		</PanelSection>
	);
}

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'perego-services-teaser__editor' } );
	// REST only allows a fixed orderby set (menu_order and per_page:-1 are rejected → a 400 and an
	// empty picker); order the pick list by title, as site-header/index.js already does. The teaser's
	// own card order comes from the composer's serviceOrder and ServiceCatalog, not from this list.
	const services = useSelect(
		( select ) => select( 'core' ).getEntityRecords( 'postType', 'perego_service', {
			per_page: 100,
			status: 'publish',
			orderby: 'title',
			order: 'asc',
		} ) || [],
		[]
	);

	const headingNode = (
		<RichText tagName="span"
			value={ attributes.headingEn }
			onChange={ ( headingEn ) => setAttributes( { headingEn } ) }
			placeholder={ SEED_HEADING_EN } />
	);
	const seeAllNode = (
		<RichText tagName="span" allowedFormats={ [] }
			value={ attributes.seeAllEn }
			onChange={ ( seeAllEn ) => setAttributes( { seeAllEn } ) }
			placeholder={ SEED_SEE_ALL_EN } />
	);

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelSection title={ __( 'Heading & link — Arabic', 'perego-site' ) }>
					<TextControl __nextHasNoMarginBottom label={ __( 'Arabic heading', 'perego-site' ) }
						value={ attributes.headingAr }
						onChange={ ( headingAr ) => setAttributes( { headingAr } ) }
						placeholder={ SEED_HEADING_AR } />
					<TextControl __nextHasNoMarginBottom label={ __( 'Arabic “See All” label', 'perego-site' ) }
						value={ attributes.seeAllAr }
						onChange={ ( seeAllAr ) => setAttributes( { seeAllAr } ) }
						placeholder={ SEED_SEE_ALL_AR } />
				</PanelSection>
				<PanelSection title={ __( '“See all” link', 'perego-site' ) }>
					<LinkPicker
						showLabel={ false }
						link={ linkFromAttributes( attributes, 'seeAll' ) }
						hrefText={ __( 'Links to', 'perego-site' ) }
						hrefHelp={ __( 'Leave empty for the services archive. A path such as /work is localized automatically.', 'perego-site' ) }
						onChange={ ( next ) => setAttributes( linkToAttributes( next, 'seeAll' ) ) } />
				</PanelSection>
				<ServicesComposer attributes={ attributes } services={ services } setAttributes={ setAttributes } />
			</InspectorControls>
			<div className="perego-services-teaser__preview" onClick={ ( event ) => {
				// Neutralize the preview's real links so a click never navigates the editor away; the
				// in-canvas RichText heading + link text manage their own clicks.
				const link = event.target.closest( 'a' );
				if ( link && ! link.isContentEditable ) {
					event.preventDefault();
				}
			} }>
				<ServicesTeaserSkeleton heading={ attributes.headingEn || SEED_HEADING_EN }
					seeAll={ attributes.seeAllEn || SEED_SEE_ALL_EN }
					headingNode={ headingNode } seeAllNode={ seeAllNode } />
			</div>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
