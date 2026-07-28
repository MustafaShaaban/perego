/**
 * Markup-parity test for the footer-careers live-canvas block (spec 021 C15).
 *
 * The fixture is the block's own PHP render, captured via `wp eval do_blocks()`.
 */
import { renderToString } from '@wordpress/element';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizeMarkup } from '../../Editor/parity';
import { FooterCareersSkeleton } from './preview';

const fixtureHtml = readFileSync( join( __dirname, '__fixtures__', 'front-footer-careers.html' ), 'utf8' );

describe( 'footer-careers markup parity', () => {
	test( 'the editor skeleton matches the PHP front-end careers column', () => {
		expect( normalizeMarkup( renderToString( <FooterCareersSkeleton /> ) ) )
			.toEqual( normalizeMarkup( fixtureHtml ) );
	} );

	test( 'a drifted skeleton (blurb dropped) is detected as a mismatch', () => {
		expect( normalizeMarkup( '<h2 class="wp-block-heading footer-heading">Join us</h2>' ) )
			.not.toEqual( normalizeMarkup( fixtureHtml ) );
	} );

	test( 'editor copy replaces the seed, and clearing it restores the seed', () => {
		expect( renderToString( <FooterCareersSkeleton heading="Careers" /> ) ).toContain( 'Careers' );
		expect( renderToString( <FooterCareersSkeleton heading="" /> ) ).toContain( 'Join us' );
	} );
} );
