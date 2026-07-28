/**
 * Markup-parity test for the project-gallery-lightbox live-canvas block (spec 021 C9; DECISIONS 2026-07-22).
 *
 * Asserts that the editor's `ProjectGallerySkeleton` renders the SAME tag + class + nesting skeleton that the
 * PHP `ProjectGalleryLightboxRenderer::render()` emits, so the canvas can't silently drift from the real front
 * end. The fixture `__fixtures__/front-project-gallery-lightbox.html` is a captured live render of the gallery
 * on `/work/visual-identity-system/` (three images). Per `../../Editor/parity.js`, only element tags, their
 * class hooks, and nesting are compared — the volatile `src`/`data-gallery` values and the renderer's inline
 * `style` attributes are ignored.
 */
import { renderToString } from '@wordpress/element';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizeMarkup } from '../../Editor/parity';
import { PLACEHOLDER_TILE_COUNT, ProjectGallerySkeleton } from './preview';

const fixtureHtml = readFileSync( join( __dirname, '__fixtures__', 'front-project-gallery-lightbox.html' ), 'utf8' );

/** The fixture's three images, values stubbed — parity ignores src/alt, structure is what matters. */
const threeImages = [
	{ id: 1, url: 'https://example.test/one.png', alt: 'One' },
	{ id: 2, url: 'https://example.test/two.png', alt: 'Two' },
	{ id: 3, url: 'https://example.test/three.png', alt: 'Three' },
];

describe( 'project-gallery-lightbox markup parity', () => {
	test( 'the editor skeleton matches the PHP front-end gallery structure', () => {
		const editorHtml = renderToString( <ProjectGallerySkeleton images={ threeImages } /> );

		expect( normalizeMarkup( editorHtml ) ).toEqual( normalizeMarkup( fixtureHtml ) );
	} );

	test( 'a drifted skeleton (thumb missing its overlay spans) is detected as a mismatch', () => {
		const drifted = '<section class="portfolio project-gallery" aria-labelledby="pjGallery">'
			+ '<h2 id="pjGallery" class="section-title">Project gallery</h2>'
			+ '<div class="work-masonry"><button type="button" class="work-card reveal"><img src="" alt="" /></button></div>'
			+ '</section>';

		expect( normalizeMarkup( drifted ) ).not.toEqual( normalizeMarkup( fixtureHtml ) );
	} );

	test( 'an empty gallery still previews the masonry grid rather than an empty box', () => {
		const editorHtml = renderToString( <ProjectGallerySkeleton images={ [] } /> );

		expect( editorHtml ).toContain( 'work-masonry' );
		expect( editorHtml.match( /class="work-card reveal"/g ) ).toHaveLength( PLACEHOLDER_TILE_COUNT );
	} );
} );
