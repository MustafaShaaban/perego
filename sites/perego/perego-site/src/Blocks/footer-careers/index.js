/**
 * Editor registration for perego-theme/footer-careers — the footer "Join us" careers heading and
 * blurb, stored bilingually in block attributes (headingEn/headingAr/blurbEn/blurbAr). The frontend
 * (PHP render_callback) emits ONLY the current Polylang language; the editor shows BOTH languages so an
 * author edits each variant in place — no hidden record, no "edit elsewhere" placeholder.
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

registerBlockType( 'perego-theme/footer-careers', {
	edit( { attributes, setAttributes } ) {
		const { headingEn, headingAr, blurbEn, blurbAr } = attributes;
		const blockProps = useBlockProps( { className: 'perego-footer-careers__editor' } );

		return (
			<div { ...blockProps }>
				<fieldset className="perego-footer-careers__lang">
					<legend>{ __( 'English', 'perego-site' ) }</legend>
					<RichText
						tagName="h2"
						className="footer-heading"
						value={ headingEn }
						allowedFormats={ [] }
						onChange={ ( value ) => setAttributes( { headingEn: value } ) }
						placeholder={ __( 'Careers heading (EN)', 'perego-site' ) }
					/>
					<RichText
						tagName="p"
						className="footer-blurb"
						value={ blurbEn }
						allowedFormats={ [ 'core/bold', 'core/italic' ] }
						onChange={ ( value ) => setAttributes( { blurbEn: value } ) }
						placeholder={ __( 'Careers blurb (EN)', 'perego-site' ) }
					/>
				</fieldset>
				<fieldset className="perego-footer-careers__lang" dir="rtl">
					<legend>{ __( 'Arabic', 'perego-site' ) }</legend>
					<RichText
						tagName="h2"
						className="footer-heading"
						value={ headingAr }
						allowedFormats={ [] }
						onChange={ ( value ) => setAttributes( { headingAr: value } ) }
						placeholder={ __( 'Careers heading (AR)', 'perego-site' ) }
					/>
					<RichText
						tagName="p"
						className="footer-blurb"
						value={ blurbAr }
						allowedFormats={ [ 'core/bold', 'core/italic' ] }
						onChange={ ( value ) => setAttributes( { blurbAr: value } ) }
						placeholder={ __( 'Careers blurb (AR)', 'perego-site' ) }
					/>
				</fieldset>
			</div>
		);
	},
	save() {
		return null;
	},
} );
