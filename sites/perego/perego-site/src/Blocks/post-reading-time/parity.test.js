/**
 * Markup-parity test for the post-reading-time live-canvas block (spec 021 C12; DECISIONS 2026-07-22).
 *
 * The fixture is a captured live render from an English journal single.
 */
import { renderToString } from '@wordpress/element';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizeMarkup } from '../../Editor/parity';
import { PostReadingTimeSkeleton } from './preview';

const fixtureHtml = readFileSync( join( __dirname, '__fixtures__', 'front-post-reading-time.html' ), 'utf8' );

describe( 'post-reading-time markup parity', () => {
	test( 'the editor skeleton matches the PHP front-end estimate chip', () => {
		expect( normalizeMarkup( renderToString( <PostReadingTimeSkeleton /> ) ) )
			.toEqual( normalizeMarkup( fixtureHtml ) );
	} );

	test( 'a drifted skeleton (wrong class hook) is detected as a mismatch', () => {
		expect( normalizeMarkup( '<span class="readtime">1 min read</span>' ) )
			.not.toEqual( normalizeMarkup( fixtureHtml ) );
	} );
} );
