/**
 * The per-card sorting affordance (spec 023; owner 2026-07-28).
 *
 * Two Move buttons beside the card, and the pointer/keyboard wiring from {@link useCanvasSort} spread
 * onto the card itself. The buttons are not a fallback — WCAG 2.2 **2.5.7 Dragging Movements** requires
 * a single-pointer alternative that is not a drag, which a keyboard shortcut does not provide, and at
 * 24×24 CSS px they also satisfy **2.5.8 Target Size**.
 *
 * "Earlier" and "later" rather than left/right or up/down: correct on the Arabic page without
 * translating direction, and honest in the mosaic, where moving a tile one position changes the slot
 * shape it is cropped to rather than sliding it one column across.
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { chevronLeft, chevronRight } from '@wordpress/icons';

export function SortableItem( {
	index,
	length,
	sort,
	isEnabled = true,
	label,
	className = '',
	children,
} ) {
	const controls = isEnabled && (
		<span className="perego-sortable__controls" contentEditable={ false }>
			<Button
				size="small"
				icon={ chevronLeft }
				disabled={ index === 0 }
				label={ label
					? /* translators: %s: project name. */ __( 'Move earlier', 'perego-site' ) + ` — ${ label }`
					: __( 'Move earlier', 'perego-site' ) }
				onClick={ () => sort.moveBy( index, 'earlier' ) }
			/>
			<Button
				size="small"
				icon={ chevronRight }
				disabled={ index === length - 1 }
				label={ label
					? __( 'Move later', 'perego-site' ) + ` — ${ label }`
					: __( 'Move later', 'perego-site' ) }
				onClick={ () => sort.moveBy( index, 'later' ) }
			/>
		</span>
	);

	return (
		<div
			className={ `perego-sortable ${ className }`.trim() }
			{ ...( isEnabled ? sort.itemProps( index ) : {} ) }
		>
			{ children }
			{ controls }
		</div>
	);
}
