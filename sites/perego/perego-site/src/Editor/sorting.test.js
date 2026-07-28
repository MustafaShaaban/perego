import {
	DRAG_THRESHOLD_PX,
	indexFromPoint,
	keyboardTarget,
	passedThreshold,
	rectsFor,
	targetIndex,
} from './sorting';

const rect = ( left, top, width = 100, height = 100 ) => ( { left, top, width, height } );

/** Three tiles in a row, centres at x = 50, 200, 350. */
const row = [ rect( 0, 0 ), rect( 150, 0 ), rect( 300, 0 ) ];

describe( 'indexFromPoint', () => {
	test( 'picks the tile the pointer is over', () => {
		expect( indexFromPoint( row, 50, 50 ) ).toBe( 0 );
		expect( indexFromPoint( row, 200, 50 ) ).toBe( 1 );
		expect( indexFromPoint( row, 350, 50 ) ).toBe( 2 );
	} );

	/*
	 * The reason this is nearest-centre rather than a containment test. Between two tiles, in the
	 * mosaic's row gaps, or past the end of the last row, the pointer is inside nothing — and a drag
	 * that stops responding in the gaps reads as broken rather than as precise.
	 */
	test( 'still resolves in the gap between two tiles', () => {
		expect( indexFromPoint( row, 120, 50 ) ).toBe( 0 );
		expect( indexFromPoint( row, 130, 50 ) ).toBe( 1 );
	} );

	test( 'resolves past the end of the row', () => {
		expect( indexFromPoint( row, 9000, 50 ) ).toBe( 2 );
	} );

	test( 'measures both axes, so a grid behaves like a row', () => {
		const grid = [ rect( 0, 0 ), rect( 150, 0 ), rect( 0, 150 ), rect( 150, 150 ) ];

		expect( indexFromPoint( grid, 50, 200 ) ).toBe( 2 );
		expect( indexFromPoint( grid, 200, 200 ) ).toBe( 3 );
	} );

	/*
	 * The mosaic case: DOM order is not visual order there, because .m1-.m15 carry explicit grid
	 * placement. The hit test must follow the rectangles, not the array.
	 */
	test( 'follows the rectangles even when they are out of array order on screen', () => {
		const placed = [ rect( 300, 0 ), rect( 0, 0 ), rect( 150, 0 ) ];

		expect( indexFromPoint( placed, 50, 50 ) ).toBe( 1 );
		expect( indexFromPoint( placed, 350, 50 ) ).toBe( 0 );
	} );

	test( 'answers -1 when there is nothing to compare', () => {
		expect( indexFromPoint( [], 10, 10 ) ).toBe( -1 );
	} );
} );

describe( 'rectsFor', () => {
	const element = ( width, height ) => ( {
		getBoundingClientRect: () => ( { left: 0, top: 0, width, height } ),
	} );

	/*
	 * Index alignment is the whole contract: an unpainted item keeps its slot as null rather than being
	 * filtered out, because collapsing the array is how a drop lands on the wrong project.
	 */
	test( 'keeps unpainted items as null so indexes stay aligned', () => {
		expect( rectsFor( [ element( 10, 10 ), element( 0, 0 ), null, element( 5, 5 ) ] ) ).toEqual( [
			{ left: 0, top: 0, width: 10, height: 10 },
			null,
			null,
			{ left: 0, top: 0, width: 5, height: 5 },
		] );
	} );
} );

describe( 'targetIndex', () => {
	test( 'maps back to the original index across gaps', () => {
		const rects = [ null, rect( 0, 0 ), null, rect( 150, 0 ) ];

		expect( targetIndex( rects, 50, 50 ) ).toBe( 1 );
		expect( targetIndex( rects, 200, 50 ) ).toBe( 3 );
	} );

	test( 'answers -1 when nothing is painted', () => {
		expect( targetIndex( [ null, null ], 10, 10 ) ).toBe( -1 );
	} );
} );

describe( 'passedThreshold', () => {
	/*
	 * Without this a plain click on a card would enter sort mode and the block would never select
	 * normally — the card is a real control on the front end, and the editor has to stay usable.
	 */
	test( 'a small movement is still a click', () => {
		expect( passedThreshold( 100, 100, 102, 101 ) ).toBe( false );
	} );

	test( 'movement on either axis is enough', () => {
		expect( passedThreshold( 100, 100, 100 + DRAG_THRESHOLD_PX, 100 ) ).toBe( true );
		expect( passedThreshold( 100, 100, 100, 100 - DRAG_THRESHOLD_PX ) ).toBe( true );
	} );
} );

describe( 'keyboardTarget', () => {
	test( 'moves one position at a time', () => {
		expect( keyboardTarget( 2, 'earlier', 5 ) ).toBe( 1 );
		expect( keyboardTarget( 2, 'later', 5 ) ).toBe( 3 );
	} );

	test( 'jumps to either end', () => {
		expect( keyboardTarget( 3, 'first', 5 ) ).toBe( 0 );
		expect( keyboardTarget( 1, 'last', 5 ) ).toBe( 4 );
	} );

	test( 'refuses to move past either end, or to stay put', () => {
		expect( keyboardTarget( 0, 'earlier', 5 ) ).toBe( -1 );
		expect( keyboardTarget( 4, 'later', 5 ) ).toBe( -1 );
		expect( keyboardTarget( 0, 'first', 5 ) ).toBe( -1 );
		expect( keyboardTarget( 4, 'last', 5 ) ).toBe( -1 );
	} );

	test( 'refuses an unknown action and an empty list', () => {
		expect( keyboardTarget( 0, 'sideways', 5 ) ).toBe( -1 );
		expect( keyboardTarget( 0, 'later', 0 ) ).toBe( -1 );
	} );
} );
