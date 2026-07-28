/**
 * LinkControl — a label + URL pair for a single editable link in the Perego block Inspector (spec 021).
 *
 * Used for structured link lists (footer legal links, nav items, …). The `href` help text documents the
 * renderer's shared rule: an internal path is localized, while an external URL, `mailto:`/`tel:`, or a
 * `#anchor` is used verbatim (mirrors `SiteHeaderRenderer::ctaHref()`), so editors know what a value does.
 */
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export function LinkControl( { label, href, onChangeLabel, onChangeHref, labelText, hrefText, hrefHelp } ) {
	return (
		<div className="perego-editor-linkcontrol">
			<TextControl __nextHasNoMarginBottom label={ labelText || __( 'Label', 'perego-site' ) }
				value={ label } onChange={ onChangeLabel } />
			<TextControl __nextHasNoMarginBottom label={ hrefText || __( 'Link', 'perego-site' ) }
				value={ href } onChange={ onChangeHref } help={ hrefHelp } />
		</div>
	);
}
