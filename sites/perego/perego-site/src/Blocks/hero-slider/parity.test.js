/**
 * Markup-parity test for the hero-slider live-canvas block (spec 021 C3 / T011; DECISIONS 2026-07-21).
 *
 * Asserts that the editor's `HeroSkeleton` renders the SAME tag + class + nesting skeleton that the PHP
 * `HeroSliderRenderer::render()` emits, so the "pixel-identical canvas" claim can't silently drift from
 * the real front end. The fixture `__fixtures__/front-hero.html` is a captured live render of the hero on
 * the homepage (three seed slides + CTA). Per `../../Editor/parity.js`, only element tags, their class
 * hooks, and nesting are compared — editable text and volatile attributes (href, src, data-*, hidden)
 * are ignored — so the pre-hydration `hidden` on the non-first slides never affects the comparison.
 */
import { renderToString } from '@wordpress/element';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizeMarkup } from '../../Editor/parity';
import { HeroSkeleton, SEED_CTA_EN, SEED_SLIDES_EN } from './preview';

/** The design surface both sides own: the whole hero section (prism, slides, CTA, dots, status). */
function heroSection( html ) {
	const template = document.createElement( 'template' );
	template.innerHTML = html.trim();
	const hero = template.content.querySelector( '.hero' );
	if ( ! hero ) {
		throw new Error( 'No .hero found in markup' );
	}
	return hero.outerHTML;
}

const displaySlides = SEED_SLIDES_EN.map( ( slide ) => ( { title: slide.title, text: slide.text } ) );
const fixtureHtml = readFileSync( join( __dirname, '__fixtures__', 'front-hero.html' ), 'utf8' );

describe( 'hero-slider markup parity', () => {
	test( 'the editor skeleton matches the PHP front-end hero structure', () => {
		const editorHtml = renderToString(
			<HeroSkeleton slides={ displaySlides } cta={ SEED_CTA_EN } />
		);

		expect( normalizeMarkup( heroSection( editorHtml ) ) ).toEqual( normalizeMarkup( heroSection( fixtureHtml ) ) );
	} );

	test( 'a drifted skeleton (an extra slide) is detected as a mismatch', () => {
		const drifted = renderToString(
			<HeroSkeleton slides={ [ ...displaySlides, { title: 'Extra', text: 'Extra' } ] } cta={ SEED_CTA_EN } />
		);

		expect( normalizeMarkup( heroSection( drifted ) ) ).not.toEqual( normalizeMarkup( heroSection( fixtureHtml ) ) );
	} );
} );
