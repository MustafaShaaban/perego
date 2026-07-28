/**
 * Markup-parity test for the services-overview live-canvas block (spec 021 C14).
 *
 * Pins the **fixed sections** — hero with its four service tabs, the two-column intro, and the process
 * rail — against the block's own PHP render, captured via `wp eval do_blocks()`. The selected-work
 * masonry is deliberately not pinned: it renders one tile per project (23 on this install, 9 KB), so a
 * fixture of it would pin today's content rather than the contract. Same split as search-results (C12).
 */
import { renderToString } from '@wordpress/element';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizeMarkup } from '../../Editor/parity';
import {
	SAMPLE_TILE_COUNT,
	SEED_STEPS,
	ServicesOverviewFixedSkeleton,
	ServicesOverviewWorkSkeleton,
} from './preview';

const fixtureHtml = readFileSync( join( __dirname, '__fixtures__', 'front-services-overview-fixed.html' ), 'utf8' );

describe( 'services-overview markup parity', () => {
	test( 'the editor skeleton matches the PHP hero, intro and process sections', () => {
		expect( normalizeMarkup( renderToString( <ServicesOverviewFixedSkeleton /> ) ) )
			.toEqual( normalizeMarkup( fixtureHtml ) );
	} );

	test( 'a drifted skeleton (process rail dropped) is detected as a mismatch', () => {
		const drifted = '<section class="services-overview"><section class="svc-hero" aria-labelledby="services-overview-title">'
			+ '<div class="svc-hero__inner"><h1 class="svc-hero__title">Our Services</h1></div></section></section>';

		expect( normalizeMarkup( drifted ) ).not.toEqual( normalizeMarkup( fixtureHtml ) );
	} );

	test( 'the process rail interleaves arrows between steps and never after the last', () => {
		const html = renderToString( <ServicesOverviewFixedSkeleton /> );

		expect( html.match( /class="process-step reveal"/g ) ).toHaveLength( SEED_STEPS.length );
		expect( html.match( /class="process-arrow"/g ) ).toHaveLength( SEED_STEPS.length - 1 );
	} );

	test( 'the selected-work masonry previews a sample of tiles', () => {
		const html = renderToString( <ServicesOverviewWorkSkeleton /> );

		expect( html ).toContain( 'work-masonry' );
		expect( html.match( /class="work-card m1 reveal"/g ) ).toHaveLength( SAMPLE_TILE_COUNT );
	} );
} );
