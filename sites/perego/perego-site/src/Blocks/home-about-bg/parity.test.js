/**
 * Markup-parity test for the home-about-bg live-canvas block (spec 021 C5 / T014; DECISIONS 2026-07-22).
 *
 * Asserts that the editor's `HomeAboutBgSkeleton` renders the SAME tag + class + nesting skeleton that the
 * PHP `HomeAboutBgRenderer::render()` emits, so the canvas can't silently drift from the real front end.
 * The fixture `__fixtures__/front-home-about-bg.html` is a captured live render of the background from the
 * homepage. Per `../../Editor/parity.js`, only element tags, their class hooks, and nesting are compared —
 * the volatile `src` is ignored.
 */
import { renderToString } from '@wordpress/element';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizeMarkup } from '../../Editor/parity';
import { HomeAboutBgSkeleton } from './preview';

const fixtureHtml = readFileSync( join( __dirname, '__fixtures__', 'front-home-about-bg.html' ), 'utf8' );

describe( 'home-about-bg markup parity', () => {
	test( 'the editor skeleton matches the PHP front-end background structure', () => {
		const editorHtml = renderToString( <HomeAboutBgSkeleton /> );

		expect( normalizeMarkup( editorHtml ) ).toEqual( normalizeMarkup( fixtureHtml ) );
	} );

	test( 'a drifted skeleton (missing image) is detected as a mismatch', () => {
		const drifted = '<div class="home-about__bg" aria-hidden="true"></div>';

		expect( normalizeMarkup( drifted ) ).not.toEqual( normalizeMarkup( fixtureHtml ) );
	} );
} );
