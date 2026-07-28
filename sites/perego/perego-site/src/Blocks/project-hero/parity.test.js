/**
 * Markup-parity test for the project-hero live-canvas block (spec 021 C8; DECISIONS 2026-07-22).
 *
 * Asserts that the editor's `ProjectHeroSkeleton` renders the SAME tag + class + nesting skeleton that the
 * PHP `ProjectHeroRenderer::render()` emits. The fixture `__fixtures__/front-project-hero.html` is a captured
 * live render from `/work/2d-motion-graphics-example-03/` — a project with a category, a featured image, and
 * all four meta values, i.e. every optional part present. Per `../../Editor/parity.js`, only element tags,
 * their class hooks, and nesting are compared, so the renderer's inline `style` attributes and the
 * href/src/srcset values are ignored.
 */
import { renderToString } from '@wordpress/element';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizeMarkup } from '../../Editor/parity';
import { ProjectHeroSkeleton, metaRows } from './preview';

function hero( html ) {
	const template = document.createElement( 'template' );
	template.innerHTML = html.trim();
	const node = template.content.querySelector( '.post-hero' );
	if ( ! node ) {
		throw new Error( 'No .post-hero found in markup' );
	}
	return node.outerHTML;
}

const fullRows = metaRows( {
	client: 'Sample Client',
	year: '2026',
	role: 'Example role',
	deliverables: 'Example deliverables',
} );

const fixtureHtml = readFileSync( join( __dirname, '__fixtures__', 'front-project-hero.html' ), 'utf8' );

describe( 'project-hero markup parity', () => {
	test( 'the editor skeleton matches the PHP front-end hero structure', () => {
		const editorHtml = renderToString(
			<ProjectHeroSkeleton title="2D Motion Graphics Example 03" category="2D Motion Graphics"
				featuredUrl="http://example.test/img.png" rows={ fullRows } />
		);

		expect( normalizeMarkup( hero( editorHtml ) ) ).toEqual( normalizeMarkup( hero( fixtureHtml ) ) );
	} );

	test( 'a drifted skeleton (missing the featured image) is detected as a mismatch', () => {
		const drifted = renderToString(
			<ProjectHeroSkeleton title="2D Motion Graphics Example 03" category="2D Motion Graphics"
				rows={ fullRows } />
		);

		expect( normalizeMarkup( hero( drifted ) ) ).not.toEqual( normalizeMarkup( hero( fixtureHtml ) ) );
	} );

	test( 'metaRows drops empty values, matching the renderer’s metaItem() guard', () => {
		expect( metaRows( { client: 'Acme', year: '', role: 'Design', deliverables: '' } ) ).toEqual( [
			{ label: 'Client', value: 'Acme' },
			{ label: 'Our role', value: 'Design' },
		] );
		expect( metaRows( {} ) ).toEqual( [] );
	} );
} );
