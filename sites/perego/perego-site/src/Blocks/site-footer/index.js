/**
 * Perego site-footer block — editor registration. Contact channels, social links, and the blurb are
 * real block attributes, editable via Inspector controls (spec 020 round 4) — previously hardcoded
 * PHP consts/strings with zero admin UI, and a closed list with no way to add more entries. Contact
 * channels/social links are JSON-string attributes (locale-neutral facts/URLs); the blurb is an En/Ar
 * RichText pair, matching `footer-careers`'/`site-header`'s pattern for text in this shared
 * `footer.html` FSE template part. Server-rendered (save returns null); forms/careers columns are
 * unchanged, driven by their own blocks.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, SelectControl, TextControl, TextareaControl, ToggleControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { FooterSkeleton, SEED_BLURB_AR, SEED_BLURB_EN, SEED_CONTACT_CHANNELS, SEED_SOCIAL_LINKS, SOCIAL_NETWORKS, parseList } from './preview';
import metadata from './block.json';

function ContactChannelsEditor( { channels, onChange } ) {
	const update = ( index, patch ) => onChange( channels.map( ( c, i ) => ( i === index ? { ...c, ...patch } : c ) ) );
	const remove = ( index ) => onChange( channels.filter( ( _, i ) => i !== index ) );
	const add = () => onChange( [ ...channels, { label: '', href: '' } ] );

	return (
		<PanelBody title={ __( 'Contact channels (email/phone)', 'perego-site' ) } initialOpen={ false }>
			{ channels.map( ( channel, index ) => (
				<div className="perego-site-footer__repeater-row" key={ index }>
					<TextControl label={ __( 'Display text', 'perego-site' ) } value={ channel.label }
						onChange={ ( label ) => update( index, { label } ) } />
					<TextControl label={ __( 'Link (mailto:… or tel:…)', 'perego-site' ) } value={ channel.href }
						onChange={ ( href ) => update( index, { href } ) } />
					<Button size="small" isDestructive onClick={ () => remove( index ) }>{ __( 'Remove', 'perego-site' ) }</Button>
				</div>
			) ) }
			<Button variant="primary" onClick={ add }>{ __( '+ Add contact channel', 'perego-site' ) }</Button>
		</PanelBody>
	);
}

function SocialLinksEditor( { links, onChange } ) {
	const update = ( index, patch ) => onChange( links.map( ( l, i ) => ( i === index ? { ...l, ...patch } : l ) ) );
	const remove = ( index ) => onChange( links.filter( ( _, i ) => i !== index ) );
	const add = () => onChange( [ ...links, { network: SOCIAL_NETWORKS[ 0 ], href: '' } ] );
	const usedNetworks = links.map( ( l ) => l.network );
	const availableFor = ( current ) => SOCIAL_NETWORKS.filter( ( n ) => n === current || ! usedNetworks.includes( n ) );

	return (
		<PanelBody title={ __( 'Social media links', 'perego-site' ) } initialOpen={ false }>
			{ links.map( ( link, index ) => (
				<div className="perego-site-footer__repeater-row" key={ index }>
					<SelectControl label={ __( 'Network', 'perego-site' ) } value={ link.network }
						options={ availableFor( link.network ).map( ( n ) => ( { label: n, value: n } ) ) }
						onChange={ ( network ) => update( index, { network } ) } />
					<TextControl label={ __( 'Profile URL', 'perego-site' ) } value={ link.href }
						onChange={ ( href ) => update( index, { href } ) } />
					<Button size="small" isDestructive onClick={ () => remove( index ) }>{ __( 'Remove', 'perego-site' ) }</Button>
				</div>
			) ) }
			{ links.length < SOCIAL_NETWORKS.length && (
				<Button variant="primary" onClick={ add }>{ __( '+ Add social link', 'perego-site' ) }</Button>
			) }
		</PanelBody>
	);
}

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'perego-site-footer__editor' } );
	const [ channels, setChannelsState ] = useState( () => parseList( attributes.contactChannels, SEED_CONTACT_CHANNELS ) );
	const [ socialLinks, setSocialLinksState ] = useState( () => parseList( attributes.socialLinks, SEED_SOCIAL_LINKS ) );

	const setChannels = ( next ) => {
		setChannelsState( next );
		setAttributes( { contactChannels: JSON.stringify( next ) } );
	};
	const setSocialLinks = ( next ) => {
		setSocialLinksState( next );
		setAttributes( { socialLinks: JSON.stringify( next ) } );
	};

	// The canvas renders the real footer for the English blurb (edited in place below). The Arabic blurb,
	// contact channels, social links, and the flat variant are edited in the Inspector; the two form
	// columns are dynamic (front-end `do_blocks()`), so they show as labelled locked placeholders.
	const blurb = (
		<RichText tagName="p" className="footer-blurb"
			value={ attributes.blurbEn || SEED_BLURB_EN }
			onChange={ ( blurbEn ) => setAttributes( { blurbEn } ) }
			placeholder={ SEED_BLURB_EN } />
	);

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<ContactChannelsEditor channels={ channels } onChange={ setChannels } />
				<SocialLinksEditor links={ socialLinks } onChange={ setSocialLinks } />
				<PanelBody title={ __( 'Blurb — Arabic', 'perego-site' ) } initialOpen={ false }>
					<TextareaControl label={ __( 'Arabic footer blurb', 'perego-site' ) }
						value={ attributes.blurbAr }
						onChange={ ( blurbAr ) => setAttributes( { blurbAr } ) }
						placeholder={ SEED_BLURB_AR } />
				</PanelBody>
				<PanelBody title={ __( 'Contact-page variant', 'perego-site' ) } initialOpen={ false }>
					<ToggleControl label={ __( 'Flat 2-column layout (drops the quick-message column)', 'perego-site' ) }
						checked={ !! attributes.flat }
						onChange={ ( flat ) => setAttributes( { flat } ) } />
				</PanelBody>
			</InspectorControls>
			<div className="perego-site-footer__preview" onClick={ ( event ) => {
				// Neutralize the preview's real links so a click never navigates the editor away; the
				// in-canvas RichText blurb manages its own clicks.
				const link = event.target.closest( 'a' );
				if ( link && ! link.isContentEditable ) {
					event.preventDefault();
				}
			} }>
				<FooterSkeleton channels={ channels } socialLinks={ socialLinks }
					flat={ !! attributes.flat } blurb={ blurb } />
			</div>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
