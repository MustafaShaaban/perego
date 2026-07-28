/**
 * Markup-parity test for the related-posts live-canvas block (spec 021 C12; DECISIONS 2026-07-22).
 *
 * The fixture is a captured live render from an English journal single with three related articles.
 * Per `../../Editor/parity.js`, only element tags, their class hooks, and nesting are compared — the
 * renderer's inline `style` attributes and the volatile href/src values are ignored.
 */
import { renderToString } from '@wordpress/element';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizeMarkup } from '../../Editor/parity';
import { RELATED_CARD_COUNT, RelatedPostsSkeleton, placeholderCards } from './preview';

const fixtureHtml = readFileSync( join( __dirname, '__fixtures__', 'front-related-posts.html' ), 'utf8' );

const threeCards = Array.from( { length: RELATED_CARD_COUNT }, ( _, index ) => ( {
	id: index,
	title: `Article ${ index }`,
	categoryLabel: 'Craft',
	meta: 'July 19, 2026 · 1 min read',
	thumbUrl: `https://example.test/${ index }.png`,
} ) );

describe( 'related-posts markup parity', () => {
	test( 'the editor skeleton matches the PHP front-end related section', () => {
		expect( normalizeMarkup( renderToString( <RelatedPostsSkeleton cards={ threeCards } /> ) ) )
			.toEqual( normalizeMarkup( fixtureHtml ) );
	} );

	test( 'a drifted skeleton (card meta dropped) is detected as a mismatch', () => {
		const drifted = '<section class="page-section post-related" aria-labelledby="relatedArticles">'
			+ '<div class="container"><h2 class="section-title" id="relatedArticles">Related articles</h2>'
			+ '<div class="blog-grid"><a class="post-card reveal" href="#"><div class="post-card__body">'
			+ '<span class="post-card__cat">Craft</span></div></a></div></div></section>';

		expect( normalizeMarkup( drifted ) ).not.toEqual( normalizeMarkup( fixtureHtml ) );
	} );

	test( 'placeholder cards keep the three-card grid before posts resolve', () => {
		const html = renderToString( <RelatedPostsSkeleton cards={ placeholderCards() } /> );

		expect( html.match( /class="post-card reveal"/g ) ).toHaveLength( RELATED_CARD_COUNT );
	} );
} );
