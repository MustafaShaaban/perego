/**
 * Markup-parity test for the join-form live-canvas block (spec 021 C13).
 *
 * The fixture is the block's own PHP render, captured via `wp eval do_blocks()`. Per
 * `../../Editor/parity.js`, only element tags, their class hooks, and nesting are compared — the
 * volatile nonce/endpoint data-attributes are ignored.
 */
import { renderToString } from '@wordpress/element';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizeMarkup } from '../../Editor/parity';
import { JoinFormSkeleton } from './preview';

const fixtureHtml = readFileSync( join( __dirname, '__fixtures__', 'front-join-form.html' ), 'utf8' );

describe( 'join-form markup parity', () => {
	test( 'the editor skeleton matches the PHP front-end form', () => {
		expect( normalizeMarkup( renderToString( <JoinFormSkeleton /> ) ) )
			.toEqual( normalizeMarkup( fixtureHtml ) );
	} );

	test( 'a drifted skeleton (CV file-drop dropped) is detected as a mismatch', () => {
		const drifted = '<form class="footer-form join-form"><div class="field join-form__field">'
			+ '<label for="jf-name">Full name</label><input type="text" id="jf-name" /></div>'
			+ '<button type="submit" class="btn btn--accent footer-form__submit join-form__submit">Apply now</button></form>';

		expect( normalizeMarkup( drifted ) ).not.toEqual( normalizeMarkup( fixtureHtml ) );
	} );

	test( 'the honeypot is present and hidden, matching the renderer anti-spam contract', () => {
		const html = renderToString( <JoinFormSkeleton /> );

		expect( html ).toContain( 'join-form__hp' );
		expect( html ).toContain( 'aria-hidden="true"' );
	} );
} );
