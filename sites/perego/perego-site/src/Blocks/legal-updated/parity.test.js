/**
 * Markup-parity test for the legal-updated live-canvas block (spec 021 C15).
 *
 * The fixture is the block's own PHP render, captured via `wp eval do_blocks()`. Per
 * `../../Editor/parity.js`, only element tags, their class hooks, and nesting are compared — the
 * volatile src/href values and the renderer's Interactivity API data-attributes are ignored.
 */
import { renderToString } from '@wordpress/element';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizeMarkup } from '../../Editor/parity';
import { LegalUpdatedSkeleton } from './preview';

const fixtureHtml = readFileSync( join( __dirname, '__fixtures__', 'front-legal-updated.html' ), 'utf8' );

describe( 'legal-updated markup parity', () => {
	test( 'the editor skeleton matches the PHP front-end markup', () => {
		expect( normalizeMarkup( renderToString( <LegalUpdatedSkeleton /> ) ) )
			.toEqual( normalizeMarkup( fixtureHtml ) );
	} );

	test( 'an emptied skeleton is detected as a mismatch', () => {
		expect( normalizeMarkup( '<div></div>' ) ).not.toEqual( normalizeMarkup( fixtureHtml ) );
	} );
} );
