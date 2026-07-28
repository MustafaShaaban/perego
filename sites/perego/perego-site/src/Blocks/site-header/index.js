/**
 * Perego site-header block — editor registration. The logo, nav links, Services dropdown, CTA, and
 * sticky-vs-static are real block attributes edited in the Inspector; the canvas renders the real header
 * markup (see preview.js). This block lives in the shared `header.html` FSE template part, so nav items
 * are an En/Ar attribute pair (each a JSON string of `{label, href, children?}`); `SiteHeaderRenderer`
 * picks the current-locale variant, falling back to the original hardcoded nav when empty/invalid, so
 * existing pages render unchanged. Server-rendered (save returns null); the front-end
 * sticky-scroll/hamburger/dropdown interactivity is unchanged, driven by view.js. The settings controls
 * use the shared `../../Editor` Inspector primitives (spec 021).
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, MediaUpload, RichText, useBlockProps } from '@wordpress/block-editor';
import { SelectControl, TextControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { LabeledRepeater } from '../../Editor/LabeledRepeater';
import { LanguagePair } from '../../Editor/LanguagePair';
import { LinkPicker, linkFromAttributes, linkToAttributes } from '../../Editor/LinkPicker';
import { MediaField } from '../../Editor/MediaField';
import { PanelSection } from '../../Editor/PanelSection';
import { RecordPicker } from '../../Editor/RecordPicker';
import { HeaderSkeleton, SEED_AR, SEED_EN, defaultLogoUrl, parseNavItems } from './preview';
import metadata from './block.json';
import './style.scss';

const NAV_HREF_HELP = __( 'A path such as /work is localized automatically; a full URL (https://…) or #anchor is used as-is.', 'perego-site' );

/** Editor for one locale's nav: a card list of links, each of which may carry its own dropdown links. */
function NavItemEditor( { items, onChange } ) {
	const update = ( index, patch ) => onChange( items.map( ( item, i ) => ( i === index ? { ...item, ...patch } : item ) ) );

	return (
		<LabeledRepeater
			items={ items }
			onChange={ onChange }
			itemLabel={ __( 'navigation link', 'perego-site' ) }
			addLabel={ __( 'Add nav link', 'perego-site' ) }
			createItem={ () => ( { label: __( 'New link', 'perego-site' ), href: '/' } ) }
			createCopy={ ( item ) => ( { ...item, children: item.children?.map( ( child ) => ( { ...child } ) ) } ) }
			renderItem={ ( item, index ) => {
				const updateChild = ( childIndex, patch ) => update( index, {
					children: item.children.map( ( child, i ) => ( i === childIndex ? { ...child, ...patch } : child ) ),
				} );
				return (
					<>
						<LinkPicker link={ item } hrefHelp={ NAV_HREF_HELP }
							onChange={ ( next ) => update( index, next ) } />
						{ item.children && (
							<LabeledRepeater
								items={ item.children }
								onChange={ ( children ) => update( index, { children } ) }
								itemLabel={ __( 'dropdown link', 'perego-site' ) }
								addLabel={ __( 'Add dropdown link', 'perego-site' ) }
								createItem={ () => ( { label: __( 'New dropdown link', 'perego-site' ), href: '/' } ) }
								renderItem={ ( child, childIndex ) => (
									<LinkPicker link={ child }
										labelText={ __( 'Dropdown label', 'perego-site' ) }
										hrefText={ __( 'Dropdown link', 'perego-site' ) }
										onChange={ ( next ) => updateChild( childIndex, next ) } />
								) }
							/>
						) }
					</>
				);
			} }
		/>
	);
}

const serviceTitle = ( service ) => decodeEntities( service?.title?.rendered || '' ) || __( 'Untitled Service', 'perego-site' );

/**
 * The Services dropdown source: keep the legacy links (manual, none chosen), pick and order Services
 * (manual), or show every published Service with per-item hide (automatic). No raw IDs — each row is the
 * Service's own title, via the shared RecordPicker.
 */
function ServicesMenuEditor( { attributes, services, setAttributes } ) {
	const mode = attributes.servicesMenuMode || 'manual';

	return (
		<PanelSection title={ __( 'Services dropdown', 'perego-site' ) }>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Dropdown links', 'perego-site' ) }
				help={ mode === 'manual'
					? __( 'Pick and order the Services shown, one by one.', 'perego-site' )
					: __( 'Every published Service is shown; hide any individually.', 'perego-site' ) }
				value={ mode }
				options={ [
					{ label: __( 'Choose manually', 'perego-site' ), value: 'manual' },
					{ label: __( 'All published Services', 'perego-site' ), value: 'automatic' },
				] }
				onChange={ ( servicesMenuMode ) => setAttributes( { servicesMenuMode } ) }
			/>
			<RecordPicker
				records={ services }
				order={ attributes.servicesMenuOrder || [] }
				excluded={ attributes.servicesMenuExcludeIds || [] }
				mode={ mode }
				getLabel={ serviceTitle }
				onChangeOrder={ ( servicesMenuOrder ) => setAttributes( { servicesMenuOrder } ) }
				onChangeExcluded={ ( servicesMenuExcludeIds ) => setAttributes( { servicesMenuExcludeIds } ) }
				emptyLabel={ __( 'No published Services in this language yet.', 'perego-site' ) }
				selectedHeading={ __( 'Shown in the dropdown', 'perego-site' ) }
				addHeading={ __( 'Add a Service', 'perego-site' ) }
				noneSelectedLabel={ __( 'None yet — the current dropdown stays until you add one.', 'perego-site' ) }
			/>
		</PanelSection>
	);
}

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'perego-site-header__editor' } );
	const [ navEn, setNavEnState ] = useState( () => parseNavItems( attributes.navItemsEn, SEED_EN ) );
	const [ navAr, setNavArState ] = useState( () => parseNavItems( attributes.navItemsAr, SEED_AR ) );
	const logoMedia = useSelect(
		( select ) => attributes.logoId ? select( 'core' ).getMedia( attributes.logoId ) : null,
		[ attributes.logoId ]
	);
	// REST only allows a fixed orderby set (menu_order and per_page:-1 are rejected → an empty list);
	// order the pick list by title. The dropdown's display order is the editor's chosen order anyway.
	const services = useSelect(
		( select ) => select( 'core' ).getEntityRecords( 'postType', 'perego_service', {
			per_page: 100,
			status: 'publish',
			orderby: 'title',
			order: 'asc',
		} ) || [],
		[]
	);

	const setNavEn = ( items ) => {
		setNavEnState( items );
		setAttributes( { navItemsEn: JSON.stringify( items ) } );
	};
	const setNavAr = ( items ) => {
		setNavArState( items );
		setAttributes( { navItemsAr: JSON.stringify( items ) } );
	};

	const logoUrl = logoMedia?.source_url || defaultLogoUrl();

	// The canvas renders the real header for the English nav (its live source of truth is the
	// "Navigation — English" panel). Arabic nav, the Services-dropdown source, and the sticky/link
	// settings are edited in the Inspector; the front-end PHP renderer stays the single renderer.
	const logo = (
		<MediaUpload onSelect={ ( media ) => setAttributes( { logoId: media.id } ) }
			allowedTypes={ [ 'image' ] } value={ attributes.logoId }
			render={ ( { open } ) => (
				<a className="logo" href="#" aria-label={ __( 'Replace the header logo', 'perego-site' ) }
					onClick={ ( event ) => { event.preventDefault(); open(); } }>
					<img src={ logoUrl } alt="" className="logo__img" />
				</a>
			) } />
	);
	const cta = (
		<RichText tagName="a" className="btn btn--accent header-cta" value={ attributes.ctaLabelEn }
			allowedFormats={ [] } onChange={ ( ctaLabelEn ) => setAttributes( { ctaLabelEn } ) }
			placeholder={ __( 'Start a Project', 'perego-site' ) } />
	);

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelSection title={ __( 'Logo', 'perego-site' ) } initialOpen>
					<MediaField value={ attributes.logoId } media={ logoMedia }
						label={ __( 'Header logo', 'perego-site' ) }
						onSelect={ ( media ) => setAttributes( { logoId: media.id } ) }
						onRemove={ () => setAttributes( { logoId: 0 } ) } />
				</PanelSection>
				<PanelSection title={ __( 'Start a Project button', 'perego-site' ) }>
					<LanguagePair
						label={ __( 'Button text', 'perego-site' ) }
						en={ attributes.ctaLabelEn } ar={ attributes.ctaLabelAr }
						onChangeEn={ ( ctaLabelEn ) => setAttributes( { ctaLabelEn } ) }
						onChangeAr={ ( ctaLabelAr ) => setAttributes( { ctaLabelAr } ) }
						placeholderEn={ __( 'Start a Project', 'perego-site' ) }
						placeholderAr="ابدأ الآن" />
					{ /* The CTA is a single link, so its picker hides the label field — the button text
					     is the bilingual pair above. `ctaUrl` stays the custom-URL half (spec 021 T036). */ }
					<LinkPicker
						showLabel={ false }
						link={ linkFromAttributes( attributes ) }
						hrefText={ __( 'Button link', 'perego-site' ) }
						hrefHelp={ __( 'A path such as /contact is localized automatically; a full URL (https://…) or #anchor is used as-is.', 'perego-site' ) }
						onChange={ ( next ) => setAttributes( linkToAttributes( next ) ) } />
				</PanelSection>
				<ServicesMenuEditor attributes={ attributes } services={ services } setAttributes={ setAttributes } />
				<PanelSection title={ __( 'Navigation — English', 'perego-site' ) }>
					<NavItemEditor items={ navEn } onChange={ setNavEn } />
				</PanelSection>
				<PanelSection title={ __( 'Navigation — Arabic', 'perego-site' ) }>
					<NavItemEditor items={ navAr } onChange={ setNavAr } />
				</PanelSection>
				<PanelSection title={ __( 'Header behavior', 'perego-site' ) }>
					<ToggleControl
						label={ __( 'Sticky header (stays fixed while scrolling)', 'perego-site' ) }
						checked={ attributes.isSticky }
						onChange={ ( isSticky ) => setAttributes( { isSticky } ) }
					/>
				</PanelSection>
			</InspectorControls>
			<div className="perego-site-header__preview" onClick={ ( event ) => {
				// Neutralize the preview's real nav anchors so a click never navigates the editor
				// away; the RichText CTA and MediaUpload logo manage their own clicks above.
				const link = event.target.closest( 'a' );
				if ( link && ! link.isContentEditable && ! link.classList.contains( 'logo' ) ) {
					event.preventDefault();
				}
			} }>
				<HeaderSkeleton navItems={ navEn } logoUrl={ logoUrl } logo={ logo } cta={ cta } />
			</div>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
