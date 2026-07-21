/**
 * Perego site-header block — editor registration. Nav links, the logo, and sticky-vs-static are real
 * block attributes, editable via Inspector controls (spec 020 round 4) — previously all three were
 * hardcoded PHP with zero admin UI. This block lives in the shared `header.html` FSE template part,
 * so — matching `footer-careers`'/`hero-slider`'s pattern for the same problem — nav items are an
 * En/Ar attribute pair (each a JSON string of `{label, href, children?}`); `SiteHeaderRenderer` picks
 * the current-locale variant, falling back to the original hardcoded nav when empty/invalid, so
 * existing pages render unchanged until an editor uses these controls. Server-rendered (save returns
 * null); the front-end sticky-scroll/hamburger/dropdown interactivity is unchanged, driven by view.js.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, MediaUpload, RichText, useBlockProps } from '@wordpress/block-editor';
import { Button, CheckboxControl, Flex, FlexBlock, FlexItem, PanelBody, SelectControl, TextControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { chevronDown, chevronUp, closeSmall, plus } from '@wordpress/icons';
import { MediaField } from '../../Editor/MediaField';
import { RepeaterControls } from '../../Editor/RepeaterControls';
import { moveItem } from '../../Editor/collection';
import { HeaderSkeleton, SEED_AR, SEED_EN, defaultLogoUrl, parseNavItems } from './preview';
import metadata from './block.json';
import './style.scss';

function NavItemEditor( { label, items, onChange } ) {
	const update = ( index, patch ) => {
		const next = items.map( ( item, i ) => ( i === index ? { ...item, ...patch } : item ) );
		onChange( next );
	};
	const add = () => onChange( [ ...items, { label: __( 'New link', 'perego-site' ), href: '/' } ] );
	const updateChild = ( itemIndex, childIndex, patch ) => {
		const children = items[ itemIndex ].children.map( ( child, i ) => ( i === childIndex ? { ...child, ...patch } : child ) );
		update( itemIndex, { children } );
	};
	const removeChild = ( itemIndex, childIndex ) => {
		update( itemIndex, { children: items[ itemIndex ].children.filter( ( _, i ) => i !== childIndex ) } );
	};
	const addChild = ( itemIndex ) => {
		const children = [ ...( items[ itemIndex ].children || [] ), { label: __( 'New dropdown link', 'perego-site' ), href: '/' } ];
		update( itemIndex, { children } );
	};

	return (
		<fieldset className="perego-site-header__nav-editor">
			<legend>{ label }</legend>
			{ items.map( ( item, index ) => (
				<div className="perego-site-header__nav-item" key={ index }>
					<TextControl label={ __( 'Label', 'perego-site' ) } value={ item.label }
						onChange={ ( value ) => update( index, { label: value } ) } />
					<TextControl label={ __( 'Link', 'perego-site' ) } value={ item.href }
						onChange={ ( value ) => update( index, { href: value } ) } />
					<RepeaterControls items={ items } index={ index } onChange={ onChange }
						itemLabel={ __( 'navigation link', 'perego-site' ) }
						createCopy={ ( item ) => ( { ...item, children: item.children?.map( ( child ) => ( { ...child } ) ) } ) } />
					{ item.children && (
						<div className="perego-site-header__nav-children">
							{ item.children.map( ( child, childIndex ) => (
								<div className="perego-site-header__nav-item" key={ childIndex }>
									<TextControl label={ __( 'Dropdown label', 'perego-site' ) } value={ child.label }
										onChange={ ( value ) => updateChild( index, childIndex, { label: value } ) } />
									<TextControl label={ __( 'Dropdown link', 'perego-site' ) } value={ child.href }
										onChange={ ( value ) => updateChild( index, childIndex, { href: value } ) } />
									<Button size="small" isDestructive onClick={ () => removeChild( index, childIndex ) }>{ __( 'Remove', 'perego-site' ) }</Button>
								</div>
							) ) }
							<Button size="small" variant="secondary" onClick={ () => addChild( index ) }>{ __( '+ Dropdown link', 'perego-site' ) }</Button>
						</div>
					) }
				</div>
			) ) }
			<Button variant="primary" onClick={ add }>{ __( '+ Add nav link', 'perego-site' ) }</Button>
		</fieldset>
	);
}

const serviceTitle = ( service ) => decodeEntities( service?.title?.rendered || '' ) || __( 'Untitled Service', 'perego-site' );

const pickerRowStyle = { padding: '6px 0', borderBlockEnd: '1px solid #f0f0f0' };
const pickerTitleStyle = { overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' };
const pickerHeadingStyle = { fontWeight: 600, margin: '12px 0 4px' };
const pickerMutedStyle = { margin: '0 0 8px', color: '#757575' };

/**
 * Professional one-by-one picker for the Services dropdown links. Manual mode shows a chosen list
 * (reorder / remove) plus an add list of the remaining Services; automatic mode lists every published
 * Service with a per-item show/hide. No raw IDs — each row is the Service's own title.
 */
function ServicesMenuEditor( { attributes, services, setAttributes } ) {
	const mode = attributes.servicesMenuMode || 'manual';
	const order = attributes.servicesMenuOrder || [];
	const excluded = attributes.servicesMenuExcludeIds || [];
	const serviceById = new Map( services.map( ( service ) => [ service.id, service ] ) );

	const selected = order.map( ( id ) => serviceById.get( id ) ).filter( Boolean );
	const available = services.filter( ( service ) => ! order.includes( service.id ) );

	const add = ( id ) => setAttributes( { servicesMenuOrder: [ ...order, id ] } );
	const remove = ( id ) => setAttributes( { servicesMenuOrder: order.filter( ( selectedId ) => selectedId !== id ) } );
	const move = ( index, delta ) => setAttributes( { servicesMenuOrder: moveItem( order, index, index + delta ) } );
	const setShown = ( id, isShown ) => setAttributes( {
		servicesMenuExcludeIds: isShown ? excluded.filter( ( excludedId ) => excludedId !== id ) : [ ...excluded, id ],
	} );

	return (
		<PanelBody title={ __( 'Services dropdown', 'perego-site' ) } initialOpen={ false }>
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

			{ services.length === 0 && (
				<p style={ pickerMutedStyle }>{ __( 'No published Services in this language yet.', 'perego-site' ) }</p>
			) }

			{ mode === 'manual' && services.length > 0 && (
				<>
					<p style={ pickerHeadingStyle }>{ __( 'Shown in the dropdown', 'perego-site' ) }</p>
					{ selected.length === 0 && (
						<p style={ pickerMutedStyle }>{ __( 'None yet — the current dropdown stays until you add one.', 'perego-site' ) }</p>
					) }
					{ selected.map( ( service, index ) => (
						<Flex key={ service.id } align="center" style={ pickerRowStyle }>
							<FlexBlock style={ pickerTitleStyle }>{ serviceTitle( service ) }</FlexBlock>
							<FlexItem>
								<Button size="small" icon={ chevronUp } label={ __( 'Move up', 'perego-site' ) }
									disabled={ index === 0 } onClick={ () => move( index, -1 ) } />
								<Button size="small" icon={ chevronDown } label={ __( 'Move down', 'perego-site' ) }
									disabled={ index === selected.length - 1 } onClick={ () => move( index, 1 ) } />
								<Button size="small" icon={ closeSmall } isDestructive label={ __( 'Remove', 'perego-site' ) }
									onClick={ () => remove( service.id ) } />
							</FlexItem>
						</Flex>
					) ) }

					{ available.length > 0 && (
						<>
							<p style={ pickerHeadingStyle }>{ __( 'Add a Service', 'perego-site' ) }</p>
							{ available.map( ( service ) => (
								<Flex key={ service.id } align="center" style={ { padding: '4px 0' } }>
									<FlexBlock style={ pickerTitleStyle }>{ serviceTitle( service ) }</FlexBlock>
									<FlexItem>
										<Button size="small" variant="secondary" icon={ plus }
											onClick={ () => add( service.id ) }>{ __( 'Add', 'perego-site' ) }</Button>
									</FlexItem>
								</Flex>
							) ) }
						</>
					) }
				</>
			) }

			{ mode === 'automatic' && services.map( ( service ) => (
				<CheckboxControl __nextHasNoMarginBottom key={ service.id }
					label={ serviceTitle( service ) }
					checked={ ! excluded.includes( service.id ) }
					onChange={ ( isShown ) => setShown( service.id, isShown ) } />
			) ) }
		</PanelBody>
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
				<PanelBody title={ __( 'Logo', 'perego-site' ) }>
					<MediaField value={ attributes.logoId } media={ logoMedia }
						label={ __( 'Header logo', 'perego-site' ) }
						onSelect={ ( media ) => setAttributes( { logoId: media.id } ) }
						onRemove={ () => setAttributes( { logoId: 0 } ) } />
				</PanelBody>
				<PanelBody title={ __( 'Header behavior', 'perego-site' ) }>
					<ToggleControl
						label={ __( 'Sticky header (stays fixed while scrolling)', 'perego-site' ) }
						checked={ attributes.isSticky }
						onChange={ ( isSticky ) => setAttributes( { isSticky } ) }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Start a Project button', 'perego-site' ) } initialOpen={ false }>
						<TextControl label={ __( 'Button text — English', 'perego-site' ) }
							value={ attributes.ctaLabelEn }
							onChange={ ( ctaLabelEn ) => setAttributes( { ctaLabelEn } ) }
							placeholder={ __( 'Start a Project', 'perego-site' ) } />
						<TextControl label={ __( 'Button text — Arabic', 'perego-site' ) }
							value={ attributes.ctaLabelAr }
							onChange={ ( ctaLabelAr ) => setAttributes( { ctaLabelAr } ) }
							placeholder="ابدأ الآن" />
						<TextControl label={ __( 'Button link', 'perego-site' ) }
							value={ attributes.ctaUrl }
							onChange={ ( ctaUrl ) => setAttributes( { ctaUrl } ) }
							placeholder="/contact"
							help={ __( 'A path such as /contact is localized automatically; a full URL (https://…) or #anchor is used as-is.', 'perego-site' ) } />
					</PanelBody>
					<ServicesMenuEditor attributes={ attributes } services={ services } setAttributes={ setAttributes } />
				<PanelBody title={ __( 'Navigation — English', 'perego-site' ) } initialOpen={ false }>
					<NavItemEditor label={ __( 'English navigation', 'perego-site' ) } items={ navEn } onChange={ setNavEn } />
				</PanelBody>
				<PanelBody title={ __( 'Navigation — Arabic', 'perego-site' ) } initialOpen={ false }>
					<NavItemEditor label={ __( 'Arabic navigation', 'perego-site' ) } items={ navAr } onChange={ setNavAr } />
				</PanelBody>
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
