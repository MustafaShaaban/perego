/**
 * The arithmetic behind canvas drag-sorting (spec 023; owner 2026-07-28).
 *
 * Pure and DOM-free so it can be reasoned about and tested without a browser: `useCanvasSort` supplies
 * measured rectangles and a pointer position, this decides where the dragged item would land.
 *
 * WHY HIT-TESTING RATHER THAN SIBLING GAPS. The two grids this serves lay out differently. `.blog-grid`
 * is `repeat(3, 1fr)` with no explicit placement, so DOM order is visual order and a naive
 * insert-before-my-neighbour would work. `.work-masonry` is not: `.m1`–`.m15` carry explicit
 * `grid-column`/`grid-row`, so a tile's visual position comes from its slot class and the element
 * *after* it in the DOM can be anywhere on screen. Measuring real rectangles is the only model that
 * serves both, and it is direction-agnostic, which matters on an RTL page.
 */

/**
 * Which index the pointer is currently over.
 *
 * Nearest-centre rather than "inside the box": between two tiles, over the mosaic's row gaps, or past
 * the end of the last row, the pointer is inside nothing at all — and a drag that stops responding in
 * the gaps feels broken. Distance is measured on both axes so a grid works the same as a row.
 *
 * @param {Array<{left:number,top:number,width:number,height:number}>} rects one per sortable item, in array order
 * @param {number} x pointer clientX
 * @param {number} y pointer clientY
 * @return {number} the index whose centre is nearest, or -1 when there is nothing to compare
 */
export function indexFromPoint( rects, x, y ) {
	let best = -1;
	let bestDistance = Infinity;

	rects.forEach( ( rect, index ) => {
		const centreX = rect.left + rect.width / 2;
		const centreY = rect.top + rect.height / 2;
		const distance = ( centreX - x ) ** 2 + ( centreY - y ) ** 2;

		if ( distance < bestDistance ) {
			bestDistance = distance;
			best = index;
		}
	} );

	return best;
}

/**
 * Measure a list of elements, skipping anything not currently painted.
 *
 * Returns `null` entries for unpainted items so the array stays index-aligned with the item list —
 * losing that alignment is how a drop lands on the wrong project.
 */
export function rectsFor( elements ) {
	return elements.map( ( element ) => {
		if ( ! element ) {
			return null;
		}

		const rect = element.getBoundingClientRect();

		return rect.width === 0 && rect.height === 0
			? null
			: { left: rect.left, top: rect.top, width: rect.width, height: rect.height };
	} );
}

/**
 * The nearest painted index to a point, given the possibly-sparse output of {@link rectsFor}.
 *
 * @return {number} index into the ORIGINAL list, or -1
 */
export function targetIndex( rects, x, y ) {
	const painted = [];
	rects.forEach( ( rect, index ) => {
		if ( rect ) {
			painted.push( { rect, index } );
		}
	} );

	if ( painted.length === 0 ) {
		return -1;
	}

	const nearest = indexFromPoint( painted.map( ( entry ) => entry.rect ), x, y );

	return nearest === -1 ? -1 : painted[ nearest ].index;
}

/** Whether the pointer has travelled far enough to mean "drag" rather than "click". */
export const DRAG_THRESHOLD_PX = 5;

export function passedThreshold( startX, startY, x, y, threshold = DRAG_THRESHOLD_PX ) {
	return Math.abs( x - startX ) >= threshold || Math.abs( y - startY ) >= threshold;
}

/**
 * Where a keyboard move lands.
 *
 * "earlier"/"later" rather than left/right or up/down: correct in RTL without translating anything,
 * and honest in the mosaic, where moving a tile one position changes which slot shape it takes rather
 * than sliding it one column across.
 */
export function keyboardTarget( index, action, length ) {
	if ( length === 0 ) {
		return -1;
	}

	const next = {
		earlier: index - 1,
		later: index + 1,
		first: 0,
		last: length - 1,
	}[ action ];

	if ( next === undefined || next < 0 || next > length - 1 || next === index ) {
		return -1;
	}

	return next;
}
