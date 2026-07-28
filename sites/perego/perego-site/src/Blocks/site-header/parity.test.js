/**
 * Markup-parity test for the site-header live-canvas block (spec 021 C1 / T007; DECISIONS 2026-07-21).
 *
 * Asserts that the editor's `HeaderSkeleton` renders the SAME tag + class + nesting skeleton that the
 * PHP `SiteHeaderRenderer::render()` emits, so the "pixel-identical canvas" claim can't silently drift
 * from the real front end. The fixture `__fixtures__/front-header.html` is a captured live render of the
 * header on a route-neutral page (`/contact/`, where no top-level nav item is active), so its structure
 * matches the stateless editor preview. Per `../../Editor/parity.js`, only element tags, their class
 * hooks, and nesting are compared — editable text and volatile attributes (href/src/data-*) are ignored.
 */
import { renderToString } from '@wordpress/element';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizeMarkup } from '../../Editor/parity';
import { HeaderSkeleton, SEED_EN } from './preview';

/** The design surface both sides own: the header's inner bar (logo, nav, CTA, lang toggle, hamburger). */
function innerBar( html ) {
	const template = document.createElement( 'template' );
	template.innerHTML = html.trim();
	const inner = template.content.querySelector( '.site-header__inner' );
	if ( ! inner ) {
		throw new Error( 'No .site-header__inner found in markup' );
	}
	return inner.outerHTML;
}

const fixtureHtml = readFileSync( join( __dirname, '__fixtures__', 'front-header.html' ), 'utf8' );

describe( 'site-header markup parity', () => {
	test( 'the editor skeleton matches the PHP front-end header structure', () => {
		const editorHtml = renderToString( <HeaderSkeleton navItems={ SEED_EN } ctaLabel="Start a Project" /> );

		expect( normalizeMarkup( innerBar( editorHtml ) ) ).toEqual( normalizeMarkup( innerBar( fixtureHtml ) ) );
	} );

	test( 'a drifted skeleton (extra nav item) is detected as a mismatch', () => {
		const drifted = renderToString(
			<HeaderSkeleton navItems={ [ ...SEED_EN, { label: 'Extra', href: '/extra' } ] } ctaLabel="Start a Project" />
		);

		expect( normalizeMarkup( innerBar( drifted ) ) ).not.toEqual( normalizeMarkup( innerBar( fixtureHtml ) ) );
	} );
} );
