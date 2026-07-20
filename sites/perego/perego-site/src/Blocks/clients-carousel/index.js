/**
 * Perego clients-carousel block — editor registration. The four section headings
 * (corporate/individual title + subtitle) are RichText-editable directly in the canvas (spec 020
 * round 4). This block instance lives in the shared `front-page.html` FSE template (not per-language
 * post content), so — matching `footer-careers`' already-proven pattern for the same problem — each
 * heading is edited as an En/Ar pair in one view; `ClientsCarouselRenderer` picks the current-locale
 * variant at render time. The client cards themselves stay a CPT-driven preview note, since they come
 * from many Client posts, not this one block instance (see DECISIONS.md).
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

const SEED_EN = {
	corporateHeadingEn: 'Corporate Clients',
	corporateSubtitleEn: "Organizations and companies we've collaborated with on professional projects.",
	individualHeadingEn: 'Individual Clients',
	individualSubtitleEn: "Professionals and individuals we've worked with.",
};

const SEED_AR = {
	corporateHeadingAr: 'عملاء الشركات',
	corporateSubtitleAr: 'مؤسسات وشركات تعاونّا معها في مشاريع احترافية.',
	individualHeadingAr: 'عملاء أفراد',
	individualSubtitleAr: 'محترفون وأفراد عملنا معهم.',
};

function LangGroup( { dir, seed, attributes, setAttributes, headingKey, subtitleKey, note } ) {
	return (
		<fieldset className="perego-clients-carousel__lang" dir={ dir }>
			<RichText tagName="h2" className="section-title"
				value={ attributes[ headingKey ] || seed[ headingKey ] }
				onChange={ ( value ) => setAttributes( { [ headingKey ]: value } ) }
				placeholder={ seed[ headingKey ] } />
			<RichText tagName="p" className="section-subtitle"
				value={ attributes[ subtitleKey ] || seed[ subtitleKey ] }
				onChange={ ( value ) => setAttributes( { [ subtitleKey ]: value } ) }
				placeholder={ seed[ subtitleKey ] } />
			{ note && <p className="perego-clients-carousel__note">{ note }</p> }
		</fieldset>
	);
}

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'perego-clients-carousel__editor' } );
	const cardsNote = __( 'Client tiles/cards are managed on each Client’s own edit screen (Clients in the admin menu).', 'perego-site' );

	return (
		<div { ...blockProps }>
			<fieldset className="perego-clients-carousel__lang-group">
				<legend>{ __( 'English', 'perego-site' ) }</legend>
				<LangGroup dir="ltr" seed={ SEED_EN } attributes={ attributes } setAttributes={ setAttributes }
					headingKey="corporateHeadingEn" subtitleKey="corporateSubtitleEn" />
				<LangGroup dir="ltr" seed={ SEED_EN } attributes={ attributes } setAttributes={ setAttributes }
					headingKey="individualHeadingEn" subtitleKey="individualSubtitleEn" note={ cardsNote } />
			</fieldset>
			<fieldset className="perego-clients-carousel__lang-group">
				<legend>{ __( 'Arabic', 'perego-site' ) }</legend>
				<LangGroup dir="rtl" seed={ SEED_AR } attributes={ attributes } setAttributes={ setAttributes }
					headingKey="corporateHeadingAr" subtitleKey="corporateSubtitleAr" />
				<LangGroup dir="rtl" seed={ SEED_AR } attributes={ attributes } setAttributes={ setAttributes }
					headingKey="individualHeadingAr" subtitleKey="individualSubtitleAr" note={ cardsNote } />
			</fieldset>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
