/**
 * LanguagePair — an English/Arabic paired input for the Perego block Inspector (spec 021).
 *
 * Every bilingual field (headings, blurbs, labels) is entered the same way: one control per locale under a
 * shared label. Defaults to `TextControl`; pass `Control={ TextareaControl }` for multi-line prose. The
 * caller owns each locale's value + change handler, so this stays presentational and schema-agnostic.
 */
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export function LanguagePair( {
	label,
	en,
	ar,
	onChangeEn,
	onChangeAr,
	Control = TextControl,
	placeholderEn,
	placeholderAr,
	help,
} ) {
	return (
		<div className="perego-editor-langpair">
			{ label && <p className="perego-editor-langpair__label">{ label }</p> }
			<Control __nextHasNoMarginBottom label={ __( 'English', 'perego-site' ) }
				value={ en } onChange={ onChangeEn } placeholder={ placeholderEn } />
			<Control __nextHasNoMarginBottom label={ __( 'Arabic', 'perego-site' ) }
				value={ ar } onChange={ onChangeAr } placeholder={ placeholderAr } />
			{ help && <p className="perego-editor-help">{ help }</p> }
		</div>
	);
}
