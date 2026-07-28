/**
 * The editor's copy of the per-shape crop system (spec 023; owner 2026-07-28).
 *
 * WHY THIS EXISTS. Dragging a tile on the canvas changes its mosaic slot, and the slot decides which
 * crop the tile is drawn from — so a canvas that cannot resolve crops cannot show the consequence of
 * the drag, which is the whole reason for dragging on the canvas rather than in a sidebar list. The
 * front end resolves all of this in PHP ({@link ../PostTypes/ProjectThumbnails.php} and
 * {@link ../Blocks/ProjectTileImage.php}); this mirrors it so `edit()` can reach the same answer from
 * REST records.
 *
 * THE DUPLICATION IS DELIBERATE AND GUARDED. `projectSlots.test.js` parses the PHP and asserts every
 * value here matches, the same parse-the-other-language trick `EditorPanels/schema.test.js` and
 * `EditorPanels/slot-shapes.test.js` already use. Between them the three tests close the triangle:
 * PHP against the stylesheet, and JS against PHP.
 */

/** Mirror of `ProjectThumbnails::SHAPES`. `ratio` is width ÷ height. */
export const SHAPES = {
	hero: { meta: '_perego_thumb_hero', width: 578, height: 332, ratio: 1.74 },
	banner: { meta: '_perego_thumb_banner', width: 380, height: 158, ratio: 2.41 },
	tall: { meta: '_perego_thumb_tall', width: 182, height: 332, ratio: 0.55 },
	card: { meta: '_perego_thumb_card', width: 406, height: 254, ratio: 1.6 },
};

/** Mirror of `ProjectThumbnails::SLOT_SHAPES`. */
export const SLOT_SHAPES = {
	m1: 'hero',
	m2: 'banner',
	m3: 'tall',
	m4: 'banner',
	m5: 'card',
	m6: 'banner',
	m7: 'tall',
	m8: 'card',
	m9: 'card',
	m10: 'banner',
	m11: 'hero',
	m12: 'hero',
	m13: 'card',
	m14: 'tall',
	m15: 'banner',
};

export const DEFAULT_SHAPE = 'card';
export const MOBILE_SHAPE = 'hero';

/** Mirror of `ServiceSelectedWorkRenderer::MASONRY_TILES` and its two work caps. */
export const MASONRY_TILES = 15;
export const WORK_CAP_DEFAULT = 6;
export const WORK_CAP_WEB = 23;

/** Mirror of `ProjectTileImage`'s layout bands. */
export const MOBILE_MAX = 560;
export const TABLET_MAX = 900;

/** Mirror of `ProjectThumbnails::shapeForSlot()`. */
export function shapeForSlot( slot ) {
	return SLOT_SHAPES[ slot ] || DEFAULT_SHAPE;
}

/**
 * Mirror of `ProjectThumbnails::nearestShapes()` — the other shapes, closest aspect ratio first.
 *
 * The PHP sorts with `usort`, which is not stable in the way `Array.prototype.sort` is; the test
 * compares against the order PHP actually produces for each of the four shapes rather than assuming
 * the two agree.
 */
export function nearestShapes( shape ) {
	const target = ( SHAPES[ shape ] || SHAPES[ DEFAULT_SHAPE ] ).ratio;

	return Object.keys( SHAPES )
		.filter( ( candidate ) => candidate !== shape )
		.sort( ( a, b ) => Math.abs( SHAPES[ a ].ratio - target ) - Math.abs( SHAPES[ b ].ratio - target ) );
}

/**
 * Which crop a project would use for a shape, and whether it is really that shape's own.
 *
 * `getAttachmentId` is supplied by the caller (the canvas reads REST meta, the front end reads post
 * meta), keeping this module free of any data source. Returns the resolved attachment id plus the
 * shape it actually came from, because the editor has to be able to say *"no Tall crop — using Wide"*
 * rather than silently swapping the picture.
 */
export function resolveCrop( shape, getAttachmentId, featuredId = 0 ) {
	const own = getAttachmentId( SHAPES[ shape ]?.meta );
	if ( own > 0 ) {
		return { id: own, shape, borrowed: false };
	}

	for ( const candidate of nearestShapes( shape ) ) {
		const id = getAttachmentId( SHAPES[ candidate ].meta );
		if ( id > 0 ) {
			return { id, shape: candidate, borrowed: true };
		}
	}

	return { id: featuredId, shape: null, borrowed: featuredId > 0 };
}

/**
 * The three bands a tile resolves to, mirroring `ProjectTileImage::render()`.
 *
 * Returns `{ desktop, tablet, mobile, collapsed }`. `collapsed` is true when one crop serves every
 * band — the case where the PHP emits a plain `<img>` rather than a `<picture>`, which the parity
 * harness compares because it compares element tags.
 */
export function tileBands( slot, resolveUrl ) {
	const desktop = resolveUrl( shapeForSlot( slot ) );
	const tablet = resolveUrl( DEFAULT_SHAPE );
	const mobile = resolveUrl( MOBILE_SHAPE );

	return {
		desktop,
		tablet,
		mobile,
		collapsed: tablet === desktop && mobile === desktop,
	};
}

/** The mosaic slot an index lands in. Beyond the designed placements there is no slot at all. */
export function slotForIndex( index ) {
	return index < MASONRY_TILES ? `m${ index + 1 }` : '';
}

/** How many projects a service single asks for, by canonical service slug. */
export function workCapFor( serviceSlug ) {
	return serviceSlug === 'website-making' ? WORK_CAP_WEB : WORK_CAP_DEFAULT;
}
