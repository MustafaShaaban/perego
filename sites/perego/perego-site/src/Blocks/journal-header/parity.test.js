/**
 * Markup-parity test for the journal-header live-canvas block (spec 021 C12; DECISIONS 2026-07-22).
 *
 * Asserts that the editor's `JournalHeaderSkeleton` renders the SAME tag + class + nesting skeleton that
 * the PHP `JournalHeaderRenderer::render()` emits, so the canvas can't silently drift from the real front
 * end. The fixture is a captured live render of `/journal/`. Per `../../Editor/parity.js`, only element
 * tags, their class hooks, and nesting are compared — the renderer's inline `style` attributes and the
 * volatile href values are ignored.
 */
import { renderToString } from '@wordpress/element';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizeMarkup } from '../../Editor/parity';
import { JournalHeaderSkeleton } from './preview';

const fixtureHtml = readFileSync( join( __dirname, '__fixtures__', 'front-journal-header.html' ), 'utf8' );

describe( 'journal-header markup parity', () => {
	test( 'the editor skeleton matches the PHP front-end header structure', () => {
		const editorHtml = renderToString( <JournalHeaderSkeleton /> );

		expect( normalizeMarkup( editorHtml ) ).toEqual( normalizeMarkup( fixtureHtml ) );
	} );

	test( 'a drifted skeleton (lead paragraph dropped) is detected as a mismatch', () => {
		const drifted = '<div class="post-hero__inner"><nav class="page-crumb" aria-label="Breadcrumb">'
			+ '<a href="#">Home</a><span aria-hidden="true">/</span><span aria-current="page">Journal</span>'
			+ '</nav><h1 class="post-title">Journal</h1></div>';

		expect( normalizeMarkup( drifted ) ).not.toEqual( normalizeMarkup( fixtureHtml ) );
	} );

	test( 'editor copy replaces the seed in the canvas, and clearing it restores the seed', () => {
		expect( renderToString( <JournalHeaderSkeleton title="Studio Notes" /> ) )
			.toContain( 'Studio Notes' );

		expect( renderToString( <JournalHeaderSkeleton title="" /> ) )
			.toContain( 'The Perego Journal' );
	} );
} );
