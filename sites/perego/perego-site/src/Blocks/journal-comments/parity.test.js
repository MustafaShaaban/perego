/**
 * Markup-parity test for the journal-comments live-canvas block (spec 021 C12; DECISIONS 2026-07-22).
 *
 * The fixture is a captured live render from an English journal single with three approved comments
 * (two top-level, one reply). Per `../../Editor/parity.js`, only element tags, their class hooks, and
 * nesting are compared — the volatile nonce/endpoint data-attributes and comment copy are ignored.
 */
import { renderToString } from '@wordpress/element';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizeMarkup } from '../../Editor/parity';
import { JournalCommentsSkeleton, SEED_COMMENTS } from './preview';

const fixtureHtml = readFileSync( join( __dirname, '__fixtures__', 'front-journal-comments.html' ), 'utf8' );

describe( 'journal-comments markup parity', () => {
	test( 'the editor skeleton matches the PHP front-end comments section', () => {
		expect( normalizeMarkup( renderToString( <JournalCommentsSkeleton /> ) ) )
			.toEqual( normalizeMarkup( fixtureHtml ) );
	} );

	test( 'a drifted skeleton (comment form dropped) is detected as a mismatch', () => {
		const drifted = '<section class="comments"><h2 class="comments__title">3 Comments</h2>'
			+ '<ul class="comment-list"></ul></section>';

		expect( normalizeMarkup( drifted ) ).not.toEqual( normalizeMarkup( fixtureHtml ) );
	} );

	test( 'the reply variant keeps its own class hook, so nesting stays visible in the canvas', () => {
		const html = renderToString( <JournalCommentsSkeleton /> );

		expect( html ).toContain( 'comment comment--reply' );
		expect( SEED_COMMENTS.filter( ( c ) => c.isReply ) ).toHaveLength( 1 );
	} );
} );
