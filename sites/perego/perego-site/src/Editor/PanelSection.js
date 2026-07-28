/**
 * PanelSection — a thin, consistent wrapper over `PanelBody` for the Perego block Inspector (spec 021).
 *
 * Standardizes the panel class hook (so the shared editor stylesheet can style every section the same
 * way) and the default collapsed state, so blocks group their controls into a predictable
 * Content / Layout & behavior / Advanced structure instead of ad-hoc `PanelBody` calls.
 */
import { PanelBody } from '@wordpress/components';

export function PanelSection( { title, initialOpen = false, children } ) {
	return (
		<PanelBody className="perego-editor-section" title={ title } initialOpen={ initialOpen }>
			{ children }
		</PanelBody>
	);
}
