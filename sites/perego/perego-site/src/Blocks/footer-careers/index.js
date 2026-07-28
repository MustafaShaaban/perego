/**
 * Perego footer-careers block — editor registration (spec 021 C15; DECISIONS 2026-07-22). The footer
 * "Join us" heading and blurb, stored bilingually (headingEn/headingAr/blurbEn/blurbAr); the PHP
 * renderer emits only the current Polylang language.
 *
 * This block already had bilingual editing, but through two stacked `<fieldset>`s that looked nothing
 * like the footer. It now follows the standard shared with every other spec 021 block: the canvas
 * renders the REAL markup (`FooterCareersSkeleton` in preview.js) with the English copy edited in
 * place, and Arabic moves to the Inspector on the shared `LanguagePair` primitive.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';
import { TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { LanguagePair } from '../../Editor/LanguagePair';
import { PanelSection } from '../../Editor/PanelSection';
import { FooterCareersSkeleton, SEED } from './preview';

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'perego-footer-careers__editor' } );

	const headingNode = (
		<RichText tagName="span" allowedFormats={ [] }
			value={ attributes.headingEn }
			onChange={ ( headingEn ) => setAttributes( { headingEn } ) }
			placeholder={ SEED.heading } />
	);
	const blurbNode = (
		<RichText tagName="span" allowedFormats={ [ 'core/bold', 'core/italic' ] }
			value={ attributes.blurbEn }
			onChange={ ( blurbEn ) => setAttributes( { blurbEn } ) }
			placeholder={ SEED.blurb } />
	);

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelSection title={ __( 'Careers column — Arabic', 'perego-site' ) } initialOpen>
					<LanguagePair
						label={ __( 'Heading', 'perego-site' ) }
						en={ attributes.headingEn } ar={ attributes.headingAr }
						onChangeEn={ ( headingEn ) => setAttributes( { headingEn } ) }
						onChangeAr={ ( headingAr ) => setAttributes( { headingAr } ) }
						placeholderEn={ SEED.heading } placeholderAr="انضم إلينا" />
					<LanguagePair
						label={ __( 'Blurb', 'perego-site' ) }
						Control={ TextareaControl }
						en={ attributes.blurbEn } ar={ attributes.blurbAr }
						onChangeEn={ ( blurbEn ) => setAttributes( { blurbEn } ) }
						onChangeAr={ ( blurbAr ) => setAttributes( { blurbAr } ) }
						placeholderEn={ SEED.blurb } />
				</PanelSection>
			</InspectorControls>
			<FooterCareersSkeleton headingNode={ headingNode } blurbNode={ blurbNode } />
		</div>
	);
}

registerBlockType( 'perego-theme/footer-careers', { edit: Edit, save: () => null } );
