/**
 * Guards `projectSlots.js` against the PHP it mirrors, by parsing the PHP.
 *
 * `EditorPanels/slot-shapes.test.js` already guards the PHP against the stylesheet. This is the other
 * edge of the same triangle: if someone retunes a shape's ratio, adds a slot, or changes a work cap in
 * PHP, the canvas would go on drawing the old arrangement and silently lie about what visitors see.
 */
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import {
	DEFAULT_SHAPE,
	MASONRY_TILES,
	MOBILE_MAX,
	MOBILE_SHAPE,
	SHAPES,
	SLOT_SHAPES,
	TABLET_MAX,
	WORK_CAP_DEFAULT,
	WORK_CAP_WEB,
	nearestShapes,
	resolveCrop,
	slotForIndex,
	tileBands,
	workCapFor,
} from './projectSlots';

const SRC = join( __dirname, '..' );
const php = ( file ) => readFileSync( join( SRC, file ), 'utf8' );

const thumbnails = () => php( 'PostTypes/ProjectThumbnails.php' );

describe( 'shape table ↔ ProjectThumbnails::SHAPES', () => {
	const phpShapes = () => {
		const block = thumbnails().match( /public const SHAPES\s*=\s*\[([\s\S]*?)\n    \];/ )[ 1 ];

		return Object.fromEntries(
			[ ...block.matchAll(
				/'(\w+)'\s*=>\s*\['meta'\s*=>\s*ProjectPostType::(\w+),\s*'width'\s*=>\s*(\d+),\s*'height'\s*=>\s*(\d+),\s*'ratio'\s*=>\s*([\d.]+)\]/g
			) ].map( ( m ) => [ m[ 1 ], { width: +m[ 3 ], height: +m[ 4 ], ratio: +m[ 5 ] } ] )
		);
	};

	test( 'the same four shapes exist, in the same order', () => {
		expect( Object.keys( SHAPES ) ).toEqual( Object.keys( phpShapes() ) );
	} );

	test( 'every shape has the same dimensions and ratio', () => {
		Object.entries( phpShapes() ).forEach( ( [ name, spec ] ) => {
			expect( SHAPES[ name ].width ).toBe( spec.width );
			expect( SHAPES[ name ].height ).toBe( spec.height );
			expect( SHAPES[ name ].ratio ).toBe( spec.ratio );
		} );
	} );

	test( 'every shape names a meta key that really exists on the CPT', () => {
		const constants = php( 'PostTypes/ProjectPostType.php' );

		Object.values( SHAPES ).forEach( ( spec ) => expect( constants ).toContain( `'${ spec.meta }'` ) );
	} );
} );

describe( 'slot map ↔ ProjectThumbnails::SLOT_SHAPES', () => {
	test( 'every slot maps to the same shape as the PHP', () => {
		const block = thumbnails().match( /public const SLOT_SHAPES\s*=\s*\[([\s\S]*?)\n    \];/ )[ 1 ];
		const phpSlots = Object.fromEntries(
			[ ...block.matchAll( /'(m\d+)'\s*=>\s*'(\w+)'/g ) ].map( ( m ) => [ m[ 1 ], m[ 2 ] ] )
		);

		expect( Object.keys( phpSlots ) ).toHaveLength( 15 );
		expect( SLOT_SHAPES ).toEqual( phpSlots );
	} );

	test( 'the uniform and mobile shapes match', () => {
		expect( thumbnails() ).toContain( `DEFAULT_SHAPE = '${ DEFAULT_SHAPE }'` );
		expect( thumbnails() ).toContain( `MOBILE_SHAPE = '${ MOBILE_SHAPE }'` );
	} );
} );

describe( 'caps and bands ↔ their PHP constants', () => {
	test( 'the mosaic size and both work caps match', () => {
		const renderer = php( 'Blocks/ServiceSelectedWorkRenderer.php' );

		expect( renderer ).toContain( `MASONRY_TILES = ${ MASONRY_TILES }` );
		expect( renderer ).toContain( `WORK_CAP_DEFAULT = ${ WORK_CAP_DEFAULT }` );
		expect( renderer ).toContain( `WORK_CAP_WEB = ${ WORK_CAP_WEB }` );
	} );

	/*
	 * The cap is what stops the canvas rendering fifteen tiles where a service single renders six — a
	 * preview that shows more work than the page does is worse than no preview.
	 */
	test( 'only Website Making gets the larger cap', () => {
		expect( workCapFor( 'website-making' ) ).toBe( WORK_CAP_WEB );
		[ 'video-editing', 'motion-graphics', 'graphic-design', '' ].forEach( ( slug ) =>
			expect( workCapFor( slug ) ).toBe( WORK_CAP_DEFAULT )
		);
	} );

	test( 'the layout bands match ProjectTileImage', () => {
		const tile = php( 'Blocks/ProjectTileImage.php' );

		expect( tile ).toContain( `MOBILE_MAX = ${ MOBILE_MAX }` );
		expect( tile ).toContain( `TABLET_MAX = ${ TABLET_MAX }` );
	} );
} );

describe( 'nearest-shape fallback', () => {
	/*
	 * Compared against the order PHP's usort actually produces, not against an assumption that the two
	 * sorts agree — the ratios are what decide it, and a wrong order silently hands a tile the least
	 * suitable crop it owns rather than the closest.
	 */
	test.each( [
		[ 'hero', [ 'card', 'banner', 'tall' ] ],
		[ 'banner', [ 'hero', 'card', 'tall' ] ],
		[ 'tall', [ 'card', 'hero', 'banner' ] ],
		[ 'card', [ 'hero', 'banner', 'tall' ] ],
	] )( '%s falls back through %s', ( shape, expected ) => {
		expect( nearestShapes( shape ) ).toEqual( expected );
	} );

	test( 'a shape uses its own crop when it has one', () => {
		const crop = resolveCrop( 'tall', ( key ) => ( key === '_perego_thumb_tall' ? 42 : 0 ) );

		expect( crop ).toEqual( { id: 42, shape: 'tall', borrowed: false } );
	} );

	test( 'a shape with no crop borrows the nearest, and says so', () => {
		const crop = resolveCrop( 'tall', ( key ) => ( key === '_perego_thumb_card' ? 7 : 0 ) );

		// `borrowed` is what lets the editor say "no Tall crop — using Standard card" instead of
		// silently swapping the picture under the cursor.
		expect( crop ).toEqual( { id: 7, shape: 'card', borrowed: true } );
	} );

	test( 'a project with no crops at all falls back to its featured image', () => {
		expect( resolveCrop( 'hero', () => 0, 99 ) ).toEqual( { id: 99, shape: null, borrowed: true } );
	} );

	test( 'a project with nothing at all resolves to nothing', () => {
		expect( resolveCrop( 'hero', () => 0, 0 ) ).toEqual( { id: 0, shape: null, borrowed: false } );
	} );
} );

describe( 'tile bands', () => {
	/*
	 * The collapse rule is part of the parity contract, not an optimization: `ProjectTileImage` emits a
	 * plain <img> when one crop serves every band, and the parity harness compares element tags — so a
	 * canvas that always wrapped in <picture> would fail against the real markup.
	 */
	test( 'one crop for every band collapses to a single image', () => {
		expect( tileBands( 'm1', () => 'same.png' ).collapsed ).toBe( true );
	} );

	test( 'distinct crops keep all three bands', () => {
		const bands = tileBands( 'm3', ( shape ) => `${ shape }.png` );

		expect( bands.collapsed ).toBe( false );
		expect( bands.desktop ).toBe( 'tall.png' );
		expect( bands.tablet ).toBe( 'card.png' );
		expect( bands.mobile ).toBe( 'hero.png' );
	} );
} );

describe( 'slot assignment', () => {
	test( 'the first fifteen positions take the designed placements', () => {
		expect( slotForIndex( 0 ) ).toBe( 'm1' );
		expect( slotForIndex( 14 ) ).toBe( 'm15' );
	} );

	test( 'anything past the mosaic has no slot, and so takes the uniform card', () => {
		expect( slotForIndex( 15 ) ).toBe( '' );
		expect( SLOT_SHAPES[ slotForIndex( 15 ) ] ).toBeUndefined();
	} );
} );
