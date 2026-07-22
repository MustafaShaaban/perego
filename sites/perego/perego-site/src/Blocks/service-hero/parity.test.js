/**
 * Markup-parity test for the service-hero live-canvas block (spec 021 C7 / T025; DECISIONS 2026-07-22).
 *
 * Asserts that the editor's `ServiceHeroSkeleton` renders the SAME tag + class + nesting skeleton that the
 * PHP `ServiceHeroRenderer::render()` emits, so the canvas can't silently drift from the real front end. The
 * fixture `__fixtures__/front-service-hero.html` is a captured live render of the hero on
 * `/services/video-editing/` (first tab active). Per `../../Editor/parity.js`, only element tags, their class
 * hooks, and nesting are compared — copy and volatile attributes (href, src, data-delay, aria) are ignored.
 */
import { renderToString } from '@wordpress/element';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizeMarkup } from '../../Editor/parity';
import { SEED_TABS_EN, ServiceHeroSkeleton } from './preview';

function heroSection( html ) {
	const template = document.createElement( 'template' );
	template.innerHTML = html.trim();
	const node = template.content.querySelector( '.svc-hero' );
	if ( ! node ) {
		throw new Error( 'No .svc-hero found in markup' );
	}
	return node.outerHTML;
}

const fixtureHtml = readFileSync( join( __dirname, '__fixtures__', 'front-service-hero.html' ), 'utf8' );

describe( 'service-hero markup parity', () => {
	test( 'the editor skeleton matches the PHP front-end hero structure', () => {
		const editorHtml = renderToString(
			<ServiceHeroSkeleton title={ SEED_TABS_EN[ 0 ].fullName } activeSlug={ SEED_TABS_EN[ 0 ].slug } />
		);

		expect( normalizeMarkup( heroSection( editorHtml ) ) ).toEqual( normalizeMarkup( heroSection( fixtureHtml ) ) );
	} );

	test( 'a drifted skeleton (an extra service tab) is detected as a mismatch', () => {
		const drifted = renderToString(
			<ServiceHeroSkeleton title={ SEED_TABS_EN[ 0 ].fullName } activeSlug={ SEED_TABS_EN[ 0 ].slug }
				tabs={ [ ...SEED_TABS_EN, { slug: 'extra', name: 'Extra', fullName: 'Extra' } ] } />
		);

		expect( normalizeMarkup( heroSection( drifted ) ) ).not.toEqual( normalizeMarkup( heroSection( fixtureHtml ) ) );
	} );

	test( 'the active tab carries is-active, and only one tab does', () => {
		const editorHtml = renderToString(
			<ServiceHeroSkeleton title={ SEED_TABS_EN[ 2 ].fullName } activeSlug={ SEED_TABS_EN[ 2 ].slug } />
		);
		const template = document.createElement( 'template' );
		template.innerHTML = editorHtml;

		const active = template.content.querySelectorAll( '.svc-tab.is-active' );
		expect( active ).toHaveLength( 1 );
		expect( active[ 0 ].querySelector( '.svc-tab__label' ).textContent ).toBe( SEED_TABS_EN[ 2 ].name );
	} );
} );
