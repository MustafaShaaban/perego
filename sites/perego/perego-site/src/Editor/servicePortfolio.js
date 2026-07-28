/**
 * The editor's copy of `Content\ServicePortfolioSelection::resolve()` (spec 023).
 *
 * A Service post decides which projects its mosaic shows — automatically from its category, manually
 * from a chosen list, or both — and the canvas has to reach the same set before it can draw anything
 * worth dragging. Fifteen lines of composition, mirrored rather than re-invented, and cross-checked
 * against `tests/Content/ServicePortfolioSelectionTest.php`.
 *
 * The block's own `projectOrder` is applied *after* this, then the work cap: composition decides who
 * is eligible, the drag decides who survives the cut.
 */

export const PORTFOLIO_MODES = [ 'automatic', 'manual', 'hybrid' ];

/** Mirror of `ServicePostType::sanitizePortfolioMode()` — anything unrecognised means automatic. */
export function sanitizeMode( mode ) {
	return PORTFOLIO_MODES.includes( mode ) ? mode : 'automatic';
}

/**
 * @param {Array}  automatic   the category query's results, in query order
 * @param {Array}  selected    the editor's chosen projects, in their chosen order
 * @param {string} mode        automatic | manual | hybrid
 * @param {Array}  excludedIds ids hidden from the automatic set
 * @param {Function} getId
 */
export function resolveSelection( automatic, selected, mode, excludedIds = [], getId = ( item ) => item.id ) {
	const resolved = sanitizeMode( mode );
	const excluded = new Set( ( excludedIds || [] ).map( Number ) );
	const withoutExcluded = ( automatic || [] ).filter( ( item ) => ! excluded.has( Number( getId( item ) ) ) );

	if ( resolved === 'automatic' ) {
		return withoutExcluded;
	}

	if ( resolved === 'manual' ) {
		return selected || [];
	}

	const selectedIds = new Set( ( selected || [] ).map( ( item ) => Number( getId( item ) ) ) );

	return [
		...( selected || [] ),
		...withoutExcluded.filter( ( item ) => ! selectedIds.has( Number( getId( item ) ) ) ),
	];
}
