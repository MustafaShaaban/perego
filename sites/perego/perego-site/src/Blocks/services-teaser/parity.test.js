/**
 * Markup-parity test for the services-teaser live-canvas block (spec 021 C4 / T013; DECISIONS 2026-07-22).
 *
 * Asserts that the editor's `ServicesTeaserSkeleton` renders the SAME tag + class + nesting skeleton that
 * the PHP `ServicesTeaserRenderer::render()` emits, so the "pixel-identical canvas" claim can't silently
 * drift from the real front end. The fixture `__fixtures__/front-services-teaser.html` is a captured live
 * render of the section on the homepage (default heading + four seed cards). Per `../../Editor/parity.js`,
 * only element tags, their class hooks, and nesting are compared — editable text and volatile attributes
 * (href, src, data-delay) are ignored.
 */
import { renderToString } from '@wordpress/element';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizeMarkup } from '../../Editor/parity';
import { SEED_CARDS, SEED_HEADING_EN, SEED_SEE_ALL_EN, ServicesTeaserSkeleton } from './preview';

/** The design surface both sides own: the whole services-teaser section (head + cards). */
function section( html ) {
	const template = document.createElement( 'template' );
	template.innerHTML = html.trim();
	const node = template.content.querySelector( '.services-teaser' );
	if ( ! node ) {
		throw new Error( 'No .services-teaser found in markup' );
	}
	return node.outerHTML;
}

const fixtureHtml = readFileSync( join( __dirname, '__fixtures__', 'front-services-teaser.html' ), 'utf8' );

describe( 'services-teaser markup parity', () => {
	test( 'the editor skeleton matches the PHP front-end section structure', () => {
		const editorHtml = renderToString(
			<ServicesTeaserSkeleton heading={ SEED_HEADING_EN } seeAll={ SEED_SEE_ALL_EN } cards={ SEED_CARDS } />
		);

		expect( normalizeMarkup( section( editorHtml ) ) ).toEqual( normalizeMarkup( section( fixtureHtml ) ) );
	} );

	test( 'a drifted skeleton (an extra card) is detected as a mismatch', () => {
		const drifted = renderToString(
			<ServicesTeaserSkeleton heading={ SEED_HEADING_EN } seeAll={ SEED_SEE_ALL_EN }
				cards={ [ ...SEED_CARDS, { slug: 'extra', name: 'Extra', image: 'card-extra', alt: 'Extra' } ] } />
		);

		expect( normalizeMarkup( section( drifted ) ) ).not.toEqual( normalizeMarkup( section( fixtureHtml ) ) );
	} );
} );
