/**
 * Markup-parity test for the search-results live-canvas block (spec 021 C12; DECISIONS 2026-07-22).
 *
 * Pins the **head** — breadcrumb, `#search-title`, result-count lead, and the search form — against a
 * live capture of `/?s=design`. The results list is deliberately not pinned: a real search renders one
 * card per match (the captured page has 25), so a fixture of it would pin today's content rather than
 * the contract and would fail the moment a post is published. See preview.js.
 */
import { renderToString } from '@wordpress/element';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizeMarkup } from '../../Editor/parity';
import { SAMPLE_CARD_COUNT, SearchHeadSkeleton, SearchResultsSkeleton } from './preview';

const fixtureHtml = readFileSync( join( __dirname, '__fixtures__', 'front-search-head.html' ), 'utf8' );

describe( 'search-results markup parity', () => {
	test( 'the editor head skeleton matches the PHP front-end search head', () => {
		expect( normalizeMarkup( renderToString( <SearchHeadSkeleton /> ) ) )
			.toEqual( normalizeMarkup( fixtureHtml ) );
	} );

	test( 'a drifted head (search form dropped) is detected as a mismatch', () => {
		const drifted = '<div class="post-hero__inner"><nav class="page-crumb" aria-label="Breadcrumb">'
			+ '<a href="#">Home</a></nav><h1 class="post-title" id="search-title">Search results</h1></div>';

		expect( normalizeMarkup( drifted ) ).not.toEqual( normalizeMarkup( fixtureHtml ) );
	} );

	test( 'the full section previews the result grid around the head', () => {
		const html = renderToString( <SearchResultsSkeleton /> );

		expect( html ).toContain( 'blog-grid' );
		expect( html.match( /class="post-card reveal"/g ) ).toHaveLength( SAMPLE_CARD_COUNT );
	} );
} );
