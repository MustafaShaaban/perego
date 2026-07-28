/**
 * Markup-parity tests for the service mosaic's live canvas (spec 023; owner 2026-07-28).
 *
 * The fixture is a live capture from `/services/video-editing/`, which renders three tiles — the whole
 * of what the Video Editing category currently holds.
 *
 * This test earns more than the usual parity test does. `../../Editor/parity.js` compares class hooks,
 * and the mosaic's placement IS a class (`.m1`…`.m15`) — so asserting the skeleton against the PHP
 * also asserts that array order maps to slot order, which is exactly what a drag changes. If the drag
 * ever writes an order the renderer reads differently, this fails.
 */
import { renderToString } from '@wordpress/element';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizeMarkup } from '../../Editor/parity';
import { ServiceMasonrySkeleton } from './preview';

const fixture = readFileSync( join( __dirname, '__fixtures__', 'front-service-selected-work.html' ), 'utf8' );

const project = ( id, overrides = {} ) => ( {
	id,
	title: `Project ${ id }`,
	thumbUrl: `https://example.test/${ id }.png`,
	thumbAlt: `Project ${ id }`,
	gallerySrcs: [],
	icon: 'none',
	bands: {
		desktop: `https://example.test/${ id }-desktop.webp`,
		tablet: `https://example.test/${ id }-card.webp`,
		mobile: `https://example.test/${ id }-hero.webp`,
		collapsed: false,
	},
	...overrides,
} );

describe( 'service mosaic markup parity', () => {
	test( 'three tiles plus the brand card match the PHP mosaic', () => {
		const html = renderToString(
			<ServiceMasonrySkeleton
				// The captured page: two tiles advertise a video, the third advertises nothing.
				projects={ [
					project( 1, { icon: 'play' } ),
					project( 2, { icon: 'play' } ),
					project( 3, { icon: 'none' } ),
				] }
				labels={ { galleryBadge: 'Gallery', loadMore: 'Load more' } }
			/>
		);

		expect( normalizeMarkup( html ) ).toEqual( normalizeMarkup( fixture ) );
	} );

	/*
	 * The drag's real contract. Slot classes are assigned by array position, so reordering the array
	 * must renumber the slots — a skeleton that pinned a tile to its slot would look right and order
	 * nothing.
	 */
	test( 'array order decides slot order', () => {
		const html = renderToString(
			<ServiceMasonrySkeleton projects={ [ project( 'a' ), project( 'b' ), project( 'c' ) ] } />
		);

		const slots = [ ...html.matchAll( /work-card (m\d+) reveal/g ) ].map( ( m ) => m[ 1 ] );
		expect( slots ).toEqual( [ 'm1', 'm2', 'm3' ] );
	} );

	test( 'the brand card sits immediately after the lead tile, and is not a project', () => {
		const html = renderToString(
			<ServiceMasonrySkeleton projects={ [ project( 1 ), project( 2 ) ] } />
		);

		expect( html.indexOf( 'work-brand' ) ).toBeGreaterThan( html.indexOf( 'work-card m1' ) );
		expect( html.indexOf( 'work-brand' ) ).toBeLessThan( html.indexOf( 'work-card m2' ) );
		// aria-hidden and a <div> rather than a <button>: it is decoration, not a tile you can open.
		expect( html ).toContain( '<div class="work-card work-brand" aria-hidden="true">' );
	} );

	test( 'a project with no usable media is not given a tile', () => {
		const html = renderToString(
			<ServiceMasonrySkeleton projects={ [ project( 1 ), project( 2, { thumbUrl: '', bands: null } ) ] } />
		);

		expect( [ ...html.matchAll( /work-card m\d+ reveal/g ) ] ).toHaveLength( 1 );
	} );

	test( 'nothing usable renders nothing at all, as the renderer returns ""', () => {
		const html = renderToString(
			<ServiceMasonrySkeleton projects={ [ project( 1, { thumbUrl: '', bands: null } ) ] } />
		);

		expect( html ).toBe( '' );
	} );

	/*
	 * Past the fifteen designed placements the overflow grid takes over, hidden behind Load more —
	 * so a sixteenth project must not be handed a sixteenth slot that the stylesheet has no rule for.
	 */
	test( 'the sixteenth project goes to the overflow grid, not to a sixteenth slot', () => {
		const html = renderToString(
			<ServiceMasonrySkeleton
				projects={ Array.from( { length: 16 }, ( _, i ) => project( i ) ) }
				labels={ { loadMore: 'Load more' } }
			/>
		);

		expect( html ).not.toContain( 'work-card m16' );
		expect( html ).toContain( 'work-more-grid' );
		expect( html ).toContain( 'load-more-btn' );
	} );

	test( 'a drifted tile (overlay missing) is detected as a mismatch', () => {
		const drifted = '<section class="portfolio page-section"><div class="container">'
			+ '<div class="work-masonry"><button class="work-card m1 reveal"><img /></button></div></div></section>';

		expect( normalizeMarkup( drifted ) ).not.toEqual( normalizeMarkup( fixture ) );
	} );
} );
