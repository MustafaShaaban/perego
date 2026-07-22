/**
 * Markup-parity test for the contact-service-chooser live-canvas block (spec 021 C13).
 *
 * The fixture is a captured live render of `/contact/`. Per `../../Editor/parity.js`, only element
 * tags, their class hooks, and nesting are compared — the volatile watermark `src` is ignored.
 */
import { renderToString } from '@wordpress/element';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizeMarkup } from '../../Editor/parity';
import { ContactServiceChooserSkeleton, SEED_SERVICES } from './preview';

const fixtureHtml = readFileSync( join( __dirname, '__fixtures__', 'front-contact-service-chooser.html' ), 'utf8' );

describe( 'contact-service-chooser markup parity', () => {
	test( 'the editor skeleton matches the PHP front-end chooser', () => {
		expect( normalizeMarkup( renderToString( <ContactServiceChooserSkeleton /> ) ) )
			.toEqual( normalizeMarkup( fixtureHtml ) );
	} );

	test( 'a drifted skeleton (help line and watermark dropped) is detected as a mismatch', () => {
		const drifted = '<div class="contact-choose reveal"><h2 class="section-title">Choose your service</h2>'
			+ '<div class="svc-choice-list" role="group"><button type="button" class="svc-choice"></button></div></div>';

		expect( normalizeMarkup( drifted ) ).not.toEqual( normalizeMarkup( fixtureHtml ) );
	} );

	test( 'a preselected service renders the pressed state the renderer emits', () => {
		const html = renderToString( <ContactServiceChooserSkeleton selected="graphic-design" /> );

		expect( html ).toContain( 'svc-choice is-selected' );
		expect( html.match( /aria-pressed="true"/g ) ).toHaveLength( 1 );
		expect( html.match( /class="svc-choice"/g ) ).toHaveLength( SEED_SERVICES.length - 1 );
	} );
} );
