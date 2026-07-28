/**
 * The project slot map's contract with the stylesheet.
 *
 * `PostTypes\ProjectThumbnails::SLOT_SHAPES` says which crop each mosaic slot wants, but the slot's
 * actual shape is defined in the theme's `perego-reference.scss` as a `grid-column` / `grid-row` span.
 * Those two live in different languages, in different packages, and nothing makes them agree — so a
 * designer re-tuning the mosaic would silently start feeding portrait crops into landscape slots, and
 * the only symptom would be badly cropped images nobody traces back.
 *
 * This reads both and checks they still describe the same thing, the same parse-the-other-language
 * trick `schema.test.js` uses against the PHP constants.
 */
import { readFileSync } from 'node:fs';
import { join } from 'node:path';

const PHP = join( __dirname, '..', 'PostTypes', 'ProjectThumbnails.php' );
const SCSS = join( __dirname, '..', '..', '..', 'perego-theme', 'assets', 'src', 'scss', 'perego-reference.scss' );

const php = readFileSync( PHP, 'utf8' );
const scss = readFileSync( SCSS, 'utf8' );

/** The mosaic's column track list, as fr values — `1fr 1fr 1fr 0.5fr 1fr 1fr 1fr`. */
const columnTracks = () => {
	const match = scss.match( /\.work-masonry\s*\{[^}]*grid-template-columns:\s*([^;]+);/ );
	return match[ 1 ].trim().split( /\s+/ ).map( ( track ) => parseFloat( track ) );
};

/** Every `.m{n}` desktop placement: `{ colStart, colEnd, rowStart, rowEnd }` (1-based grid lines). */
const slotPlacements = () => {
	const placements = {};
	const re = /\.m(\d+)\s*\{\s*grid-column:\s*(\d+)\s*\/\s*(\d+);\s*grid-row:\s*(\d+)\s*\/\s*(\d+);/g;
	let match;
	while ( ( match = re.exec( scss ) ) !== null ) {
		placements[ `m${ match[ 1 ] }` ] = {
			colStart: +match[ 2 ], colEnd: +match[ 3 ], rowStart: +match[ 4 ], rowEnd: +match[ 5 ],
		};
	}
	return placements;
};

/** The PHP map, as `{ m1: 'hero', … }`. */
const slotShapes = () => {
	const block = php.match( /SLOT_SHAPES = \[([\s\S]*?)\];/ )[ 1 ];
	return Object.fromEntries(
		[ ...block.matchAll( /'(m\d+)'\s*=>\s*'(\w+)'/g ) ].map( ( m ) => [ m[ 1 ], m[ 2 ] ] )
	);
};

/** Each shape's declared aspect ratio from the PHP `SHAPES` table. */
const shapeRatios = () => {
	const block = php.match( /SHAPES = \[([\s\S]*?)\n    \];/ )[ 1 ];
	return Object.fromEntries(
		[ ...block.matchAll( /'(\w+)' => \[[^\]]*'ratio' => ([\d.]+)/g ) ].map( ( m ) => [ m[ 1 ], +m[ 2 ] ] )
	);
};

/**
 * A slot's real aspect ratio at the reference viewport.
 *
 * Uses the same numbers the layout does: 1fr ≈ 185px and a row ≈ 158px at 1440px, plus the ~16px gap
 * between tracks. Exact pixels do not matter — only that the ratio lands nearer the shape PHP picked
 * than any other.
 */
const slotRatio = ( placement, tracks ) => {
	const FR = 185, ROW = 158, GAP = 16;
	const cols = tracks.slice( placement.colStart - 1, placement.colEnd - 1 );
	const width = cols.reduce( ( sum, fr ) => sum + fr * FR, 0 ) + ( cols.length - 1 ) * GAP;
	const rows = placement.rowEnd - placement.rowStart;
	return width / ( rows * ROW + ( rows - 1 ) * GAP );
};

describe( 'project slot map ↔ the mosaic stylesheet', () => {
	test( 'the stylesheet still defines all 15 desktop placements', () => {
		expect( Object.keys( slotPlacements() ) ).toHaveLength( 15 );
	} );

	test( 'every slot PHP maps is a slot the stylesheet actually places', () => {
		const placements = slotPlacements();

		Object.keys( slotShapes() ).forEach( ( slot ) => expect( placements ).toHaveProperty( slot ) );
	} );

	test( 'every placed slot has a shape assigned', () => {
		const shapes = slotShapes();

		Object.keys( slotPlacements() ).forEach( ( slot ) => expect( shapes ).toHaveProperty( slot ) );
	} );

	/*
	 * The real check: the shape PHP chose must be the closest available ratio to the slot's actual
	 * geometry. If someone widens `.m3` from one column to three, this fails and names the slot.
	 */
	test( 'each slot is assigned the nearest available shape to its real geometry', () => {
		const tracks = columnTracks();
		const ratios = shapeRatios();
		const shapes = slotShapes();

		Object.entries( slotPlacements() ).forEach( ( [ slot, placement ] ) => {
			const actual = slotRatio( placement, tracks );
			const nearest = Object.keys( ratios ).reduce( ( best, shape ) =>
				Math.abs( ratios[ shape ] - actual ) < Math.abs( ratios[ best ] - actual ) ? shape : best
			);

			expect( `${ slot }:${ shapes[ slot ] }` ).toBe( `${ slot }:${ nearest }` );
		} );
	} );
} );
