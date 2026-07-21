/**
 * Perego site-footer block — editor registration. The contact channels, social links, blurb, and bottom
 * bar (copyright + legal links) are all real block attributes; the canvas renders the real footer markup
 * (see preview.js), with the English blurb and copyright edited in place. Contact channels / social links /
 * legal links are per-locale JSON attributes; the blurb and copyright are En/Ar pairs. Server-rendered
 * (save returns null); the quick-message and careers columns stay dynamic (front-end `do_blocks()`). The
 * settings controls use the shared `../../Editor` Inspector primitives (spec 021).
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';
import { SelectControl, TextControl, TextareaControl, ToggleControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { LabeledRepeater } from '../../Editor/LabeledRepeater';
import { LanguagePair } from '../../Editor/LanguagePair';
import { LinkControl } from '../../Editor/LinkControl';
import { PanelSection } from '../../Editor/PanelSection';
import { FooterSkeleton, SEED_BLURB_AR, SEED_BLURB_EN, SEED_CONTACT_CHANNELS, SEED_LEGAL_LINKS, SEED_SOCIAL_LINKS, SOCIAL_NETWORKS, defaultCopyright, parseList } from './preview';
import metadata from './block.json';

function ContactChannelsEditor( { channels, onChange } ) {
	const update = ( index, patch ) => onChange( channels.map( ( channel, i ) => ( i === index ? { ...channel, ...patch } : channel ) ) );

	return (
		<PanelSection title={ __( 'Contact channels (email/phone)', 'perego-site' ) }>
			<LabeledRepeater
				items={ channels }
				onChange={ onChange }
				itemLabel={ __( 'contact channel', 'perego-site' ) }
				addLabel={ __( 'Add contact channel', 'perego-site' ) }
				createItem={ () => ( { label: '', href: '' } ) }
				createCopy={ ( channel ) => ( { ...channel } ) }
				renderItem={ ( channel, index ) => (
					<LinkControl label={ channel.label } href={ channel.href }
						labelText={ __( 'Display text', 'perego-site' ) }
						hrefText={ __( 'Link (mailto:… or tel:…)', 'perego-site' ) }
						onChangeLabel={ ( label ) => update( index, { label } ) }
						onChangeHref={ ( href ) => update( index, { href } ) } />
				) }
			/>
		</PanelSection>
	);
}

function SocialLinksEditor( { links, onChange } ) {
	const update = ( index, patch ) => onChange( links.map( ( link, i ) => ( i === index ? { ...link, ...patch } : link ) ) );
	const usedNetworks = links.map( ( link ) => link.network );
	const availableFor = ( current ) => SOCIAL_NETWORKS.filter( ( network ) => network === current || ! usedNetworks.includes( network ) );
	const firstUnused = SOCIAL_NETWORKS.find( ( network ) => ! usedNetworks.includes( network ) );

	return (
		<PanelSection title={ __( 'Social media links', 'perego-site' ) }>
			<LabeledRepeater
				items={ links }
				onChange={ onChange }
				itemLabel={ __( 'social link', 'perego-site' ) }
				addLabel={ __( 'Add social link', 'perego-site' ) }
				createItem={ firstUnused ? () => ( { network: firstUnused, href: '' } ) : undefined }
				renderItem={ ( link, index ) => (
					<>
						<SelectControl __nextHasNoMarginBottom label={ __( 'Network', 'perego-site' ) } value={ link.network }
							options={ availableFor( link.network ).map( ( network ) => ( { label: network, value: network } ) ) }
							onChange={ ( network ) => update( index, { network } ) } />
						<TextControl __nextHasNoMarginBottom label={ __( 'Profile URL', 'perego-site' ) } value={ link.href }
							onChange={ ( href ) => update( index, { href } ) } />
					</>
				) }
			/>
		</PanelSection>
	);
}

/** A card list of bottom-bar legal links (label + URL), shared by the English and Arabic editors. */
function LegalLinksEditor( { links, onChange } ) {
	const update = ( index, patch ) => onChange( links.map( ( link, i ) => ( i === index ? { ...link, ...patch } : link ) ) );

	return (
		<LabeledRepeater
			items={ links }
			onChange={ onChange }
			itemLabel={ __( 'legal link', 'perego-site' ) }
			addLabel={ __( 'Add legal link', 'perego-site' ) }
			createItem={ () => ( { label: '', href: '' } ) }
			createCopy={ ( link ) => ( { ...link } ) }
			renderItem={ ( link, index ) => (
				<LinkControl label={ link.label } href={ link.href }
					onChangeLabel={ ( label ) => update( index, { label } ) }
					onChangeHref={ ( href ) => update( index, { href } ) } />
			) }
		/>
	);
}

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'perego-site-footer__editor' } );
	const [ channels, setChannelsState ] = useState( () => parseList( attributes.contactChannels, SEED_CONTACT_CHANNELS ) );
	const [ socialLinks, setSocialLinksState ] = useState( () => parseList( attributes.socialLinks, SEED_SOCIAL_LINKS ) );
	const [ legalEn, setLegalEnState ] = useState( () => parseList( attributes.legalLinksEn, SEED_LEGAL_LINKS ) );
	const [ legalAr, setLegalArState ] = useState( () => parseList( attributes.legalLinksAr, SEED_LEGAL_LINKS ) );

	const setChannels = ( next ) => {
		setChannelsState( next );
		setAttributes( { contactChannels: JSON.stringify( next ) } );
	};
	const setSocialLinks = ( next ) => {
		setSocialLinksState( next );
		setAttributes( { socialLinks: JSON.stringify( next ) } );
	};
	const setLegalEn = ( next ) => {
		setLegalEnState( next );
		setAttributes( { legalLinksEn: JSON.stringify( next ) } );
	};
	const setLegalAr = ( next ) => {
		setLegalArState( next );
		setAttributes( { legalLinksAr: JSON.stringify( next ) } );
	};

	// The canvas renders the real footer for the English blurb + copyright (edited in place). The Arabic
	// blurb/copyright, contact channels, social links, and legal links are edited in the Inspector; the
	// two form columns are dynamic (front-end `do_blocks()`), so they show as labelled locked placeholders.
	const blurb = (
		<RichText tagName="p" className="footer-blurb"
			value={ attributes.blurbEn || SEED_BLURB_EN }
			onChange={ ( blurbEn ) => setAttributes( { blurbEn } ) }
			placeholder={ SEED_BLURB_EN } />
	);
	const copyright = (
		<RichText tagName="p" value={ attributes.copyrightEn } allowedFormats={ [] }
			onChange={ ( copyrightEn ) => setAttributes( { copyrightEn } ) }
			placeholder={ defaultCopyright() } />
	);

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<ContactChannelsEditor channels={ channels } onChange={ setChannels } />
				<SocialLinksEditor links={ socialLinks } onChange={ setSocialLinks } />
				<PanelSection title={ __( 'Blurb — Arabic', 'perego-site' ) }>
					<TextareaControl label={ __( 'Arabic footer blurb', 'perego-site' ) }
						value={ attributes.blurbAr }
						onChange={ ( blurbAr ) => setAttributes( { blurbAr } ) }
						placeholder={ SEED_BLURB_AR } />
				</PanelSection>
				<PanelSection title={ __( 'Bottom bar', 'perego-site' ) }>
					<LanguagePair
						label={ __( 'Copyright', 'perego-site' ) }
						en={ attributes.copyrightEn } ar={ attributes.copyrightAr }
						onChangeEn={ ( copyrightEn ) => setAttributes( { copyrightEn } ) }
						onChangeAr={ ( copyrightAr ) => setAttributes( { copyrightAr } ) }
						placeholderEn={ defaultCopyright() }
						help={ __( 'Use {year} for the current year — it is filled in automatically on the live site.', 'perego-site' ) } />
					<p className="perego-editor-langpair__label">{ __( 'Legal links — English', 'perego-site' ) }</p>
					<LegalLinksEditor links={ legalEn } onChange={ setLegalEn } />
					<p className="perego-editor-langpair__label">{ __( 'Legal links — Arabic', 'perego-site' ) }</p>
					<LegalLinksEditor links={ legalAr } onChange={ setLegalAr } />
				</PanelSection>
				<PanelSection title={ __( 'Contact-page variant', 'perego-site' ) }>
					<ToggleControl label={ __( 'Flat 2-column layout (drops the quick-message column)', 'perego-site' ) }
						checked={ !! attributes.flat }
						onChange={ ( flat ) => setAttributes( { flat } ) } />
				</PanelSection>
			</InspectorControls>
			<div className="perego-site-footer__preview" onClick={ ( event ) => {
				// Neutralize the preview's real links so a click never navigates the editor away; the
				// in-canvas RichText blurb + copyright manage their own clicks.
				const link = event.target.closest( 'a' );
				if ( link && ! link.isContentEditable ) {
					event.preventDefault();
				}
			} }>
				<FooterSkeleton channels={ channels } socialLinks={ socialLinks } flat={ !! attributes.flat }
					blurb={ blurb } copyright={ copyright } legalLinks={ legalEn } />
			</div>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
