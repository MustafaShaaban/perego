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
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import { MediaField } from '../../Editor/MediaField';
import { RepeaterControls } from '../../Editor/RepeaterControls';
import metadata from './block.json';
import './style.scss';

const SEED_EN = [
	{ label: 'Home', href: '/' },
	{ label: 'About Us', href: '/#about' },
	{
		label: 'Services', href: '/#services',
		children: [
			{ label: 'Video Editing', href: '/services/video-editing' },
			{ label: '2D Motion Graphics', href: '/services/motion-graphics' },
			{ label: 'Graphic Design', href: '/services/graphic-design' },
			{ label: 'Website Making', href: '/services/website-making' },
		],
	},
	{ label: 'Work', href: '/work' },
	{ label: 'Journal', href: '/journal' },
	{ label: 'Clients', href: '/#clients' },
	{ label: 'Contact Us', href: '/contact' },
];

const SEED_AR = [
	{ label: 'الرئيسية', href: '/' },
	{ label: 'من نحن', href: '/#about' },
	{
		label: 'الخدمات', href: '/#services',
		children: [
			{ label: 'مونتاج الفيديو', href: '/services/video-editing' },
			{ label: 'موشن جرافيك ثنائي الأبعاد', href: '/services/motion-graphics' },
			{ label: 'تصميم جرافيك', href: '/services/graphic-design' },
			{ label: 'صناعة المواقع', href: '/services/website-making' },
		],
	},
	{ label: 'أعمالنا', href: '/work' },
	{ label: 'المدونة', href: '/journal' },
	{ label: 'العملاء', href: '/#clients' },
	{ label: 'تواصل معنا', href: '/contact' },
];

function parseNavItems( raw, seed ) {
	if ( ! raw ) {
		return seed;
	}
	try {
		const parsed = JSON.parse( raw );
		return Array.isArray( parsed ) && parsed.length ? parsed : seed;
	} catch ( e ) {
		return seed;
	}
}

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

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'perego-site-header__editor' } );
	const [ navEn, setNavEnState ] = useState( () => parseNavItems( attributes.navItemsEn, SEED_EN ) );
	const [ navAr, setNavArState ] = useState( () => parseNavItems( attributes.navItemsAr, SEED_AR ) );
	const logoMedia = useSelect(
		( select ) => attributes.logoId ? select( 'core' ).getMedia( attributes.logoId ) : null,
		[ attributes.logoId ]
	);

	const setNavEn = ( items ) => {
		setNavEnState( items );
		setAttributes( { navItemsEn: JSON.stringify( items ) } );
	};
	const setNavAr = ( items ) => {
		setNavArState( items );
		setAttributes( { navItemsAr: JSON.stringify( items ) } );
	};

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
				<PanelBody title={ __( 'Navigation — English', 'perego-site' ) } initialOpen={ false }>
					<NavItemEditor label={ __( 'English navigation', 'perego-site' ) } items={ navEn } onChange={ setNavEn } />
				</PanelBody>
				<PanelBody title={ __( 'Navigation — Arabic', 'perego-site' ) } initialOpen={ false }>
					<NavItemEditor label={ __( 'Arabic navigation', 'perego-site' ) } items={ navAr } onChange={ setNavAr } />
				</PanelBody>
			</InspectorControls>
			<div className="perego-site-header__preview" onClick={ ( event ) => {
				if ( event.target.closest( 'a' ) ) {
					event.preventDefault();
				}
			} }>
				<ServerSideRender block={ metadata.name } attributes={ attributes } />
			</div>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
