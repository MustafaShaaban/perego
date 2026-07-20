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
import { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
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
	const remove = ( index ) => onChange( items.filter( ( _, i ) => i !== index ) );
	const move = ( index, delta ) => {
		const target = index + delta;
		if ( target < 0 || target >= items.length ) return;
		const next = [ ...items ];
		[ next[ index ], next[ target ] ] = [ next[ target ], next[ index ] ];
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
					<div className="perego-site-header__nav-item-actions">
						<Button size="small" onClick={ () => move( index, -1 ) } disabled={ index === 0 }>{ __( 'Move up', 'perego-site' ) }</Button>
						<Button size="small" onClick={ () => move( index, 1 ) } disabled={ index === items.length - 1 }>{ __( 'Move down', 'perego-site' ) }</Button>
						<Button size="small" isDestructive onClick={ () => remove( index ) }>{ __( 'Remove', 'perego-site' ) }</Button>
					</div>
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
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ ( media ) => setAttributes( { logoId: media.id } ) }
							allowedTypes={ [ 'image' ] }
							value={ attributes.logoId }
							render={ ( { open } ) => (
								<Button variant="secondary" onClick={ open }>
									{ attributes.logoId ? __( 'Change logo', 'perego-site' ) : __( 'Select logo', 'perego-site' ) }
								</Button>
							) }
						/>
					</MediaUploadCheck>
				</PanelBody>
				<PanelBody title={ __( 'Header behavior', 'perego-site' ) }>
					<ToggleControl
						label={ __( 'Sticky header (stays fixed while scrolling)', 'perego-site' ) }
						checked={ attributes.isSticky }
						onChange={ ( isSticky ) => setAttributes( { isSticky } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<p className="perego-site-header__editor-note">
				{ __( 'Site header — logo, sticky behavior, and nav links (below) are editable. Preview on the live site after saving.', 'perego-site' ) }
			</p>
			<NavItemEditor label={ __( 'Navigation — English', 'perego-site' ) } items={ navEn } onChange={ setNavEn } />
			<NavItemEditor label={ __( 'Navigation — Arabic', 'perego-site' ) } items={ navAr } onChange={ setNavAr } />
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
