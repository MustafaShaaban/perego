/**
 * Perego services-teaser block — editor registration. The heading + "See All Services" link text are
 * RichText-editable directly in the canvas (spec 020 round 4) — previously 100% hardcoded with zero
 * admin UI. This block instance lives in the shared `front-page.html` FSE template, so — matching
 * `footer-careers`'/`clients-carousel`'s pattern for the same problem — each string is edited as an
 * En/Ar pair; `ServicesTeaserRenderer` picks the current-locale variant. The four service cards
 * themselves stay a Service-CPT-driven preview note (spec 012 — editing a Service updates its card).
 * Server-rendered (save returns null).
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

const SEED_EN = { headingEn: 'Services we can help you with', seeAllEn: 'See All Services' };
const SEED_AR = { headingAr: 'خدمات يمكننا مساعدتك بها', seeAllAr: 'عرض كل الخدمات' };

function LangGroup( { dir, seed, headingKey, seeAllKey, attributes, setAttributes } ) {
	return (
		<fieldset className="perego-services-teaser__lang" dir={ dir }>
			<RichText tagName="h2" className="services-teaser__title"
				value={ attributes[ headingKey ] || seed[ headingKey ] }
				onChange={ ( value ) => setAttributes( { [ headingKey ]: value } ) }
				placeholder={ seed[ headingKey ] } />
			<RichText tagName="span" className="services-teaser__link"
				value={ attributes[ seeAllKey ] || seed[ seeAllKey ] }
				onChange={ ( value ) => setAttributes( { [ seeAllKey ]: value } ) }
				placeholder={ seed[ seeAllKey ] } />
		</fieldset>
	);
}

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'perego-services-teaser__editor' } );

	return (
		<div { ...blockProps }>
			<fieldset className="perego-services-teaser__lang-group">
				<legend>{ __( 'English', 'perego-site' ) }</legend>
				<LangGroup dir="ltr" seed={ SEED_EN } headingKey="headingEn" seeAllKey="seeAllEn"
					attributes={ attributes } setAttributes={ setAttributes } />
			</fieldset>
			<fieldset className="perego-services-teaser__lang-group">
				<legend>{ __( 'Arabic', 'perego-site' ) }</legend>
				<LangGroup dir="rtl" seed={ SEED_AR } headingKey="headingAr" seeAllKey="seeAllAr"
					attributes={ attributes } setAttributes={ setAttributes } />
			</fieldset>
			<p className="perego-services-teaser__note">
				{ __( 'The four service cards are managed on each Service’s own edit screen (Services in the admin menu).', 'perego-site' ) }
			</p>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
