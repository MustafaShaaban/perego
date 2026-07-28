/**
 * Markup-parity tests for the project-navigation live-canvas block (spec 021 C10; DECISIONS 2026-07-22).
 *
 * Asserts that the editor's two skeletons render the SAME tag + class + nesting structure that the PHP
 * `ProjectNavigationRenderer` emits for each `surface`, so the canvas can't silently drift from the real front
 * end. Both fixtures are captured live renders from `/work/visual-identity-system/`. Per `../../Editor/parity.js`,
 * only element tags, their class hooks, and nesting are compared — the renderer's inline `style` attributes and
 * the volatile href/src values are ignored.
 */
import { renderToString } from '@wordpress/element';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizeMarkup } from '../../Editor/parity';
import {
	ProjectAdjacentSkeleton,
	ProjectRelatedSkeleton,
	RELATED_CARD_COUNT,
	placeholderCards,
} from './preview';

const fixture = ( name ) => readFileSync( join( __dirname, '__fixtures__', name ), 'utf8' );

/** The fixture's three related cards, values stubbed — parity ignores copy, structure is what matters. */
const threeCards = Array.from( { length: RELATED_CARD_COUNT }, ( _, index ) => ( {
	id: index,
	title: `Project ${ index }`,
	categoryLabel: 'Graphic Design',
	excerpt: 'Client: Sample Client · 2026',
	thumbUrl: `https://example.test/${ index }.png`,
	thumbAlt: `Project ${ index }`,
} ) );

describe( 'project-navigation markup parity', () => {
	test( 'the adjacent skeleton matches the PHP prev/next nav', () => {
		const editorHtml = renderToString( <ProjectAdjacentSkeleton /> );

		expect( normalizeMarkup( editorHtml ) )
			.toEqual( normalizeMarkup( fixture( 'front-project-navigation-adjacent.html' ) ) );
	} );

	test( 'the related skeleton matches the PHP related-projects surface', () => {
		const editorHtml = renderToString( <ProjectRelatedSkeleton cards={ threeCards } /> );

		expect( normalizeMarkup( editorHtml ) )
			.toEqual( normalizeMarkup( fixture( 'front-project-navigation-related.html' ) ) );
	} );

	test( 'a drifted related skeleton (card missing its body) is detected as a mismatch', () => {
		const drifted = '<h2 class="section-title">Related projects</h2><div class="blog-grid">'
			+ '<a class="post-card" href="#"><div class="post-card__media"><img src="" alt="" /></div></a>'
			+ '</div><div><a class="btn btn--accent" href="#">Start a Project</a></div>';

		expect( normalizeMarkup( drifted ) )
			.not.toEqual( normalizeMarkup( fixture( 'front-project-navigation-related.html' ) ) );
	} );

	test( 'a missing neighbour renders the spacer span the renderer emits, not a dangling link', () => {
		const editorHtml = renderToString( <ProjectAdjacentSkeleton hasPrevious={ false } /> );

		expect( editorHtml ).toContain( '<span></span>' );
		expect( editorHtml ).not.toContain( 'rel="prev"' );
		expect( editorHtml ).toContain( 'rel="next"' );
	} );

	test( 'placeholder cards keep the three-card grid for the shared template', () => {
		const editorHtml = renderToString( <ProjectRelatedSkeleton cards={ placeholderCards() } /> );

		expect( editorHtml.match( /class="post-card"/g ) ).toHaveLength( RELATED_CARD_COUNT );
	} );
} );
