/**
 * Markup-parity test for the post-breadcrumb live-canvas block (spec 021 C12; DECISIONS 2026-07-22).
 *
 * The fixture is a captured live render from an English journal single. Per `../../Editor/parity.js`,
 * only element tags, their class hooks, and nesting are compared.
 */
import { renderToString } from '@wordpress/element';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizeMarkup } from '../../Editor/parity';
import { PostBreadcrumbSkeleton } from './preview';

const fixtureHtml = readFileSync( join( __dirname, '__fixtures__', 'front-post-breadcrumb.html' ), 'utf8' );

describe( 'post-breadcrumb markup parity', () => {
	test( 'the editor skeleton matches the PHP front-end breadcrumb on a post', () => {
		const editorHtml = renderToString( <PostBreadcrumbSkeleton title="What a week" /> );

		expect( normalizeMarkup( editorHtml ) ).toEqual( normalizeMarkup( fixtureHtml ) );
	} );

	test( 'a page drops the Journal step, exactly as the renderer does', () => {
		const editorHtml = renderToString( <PostBreadcrumbSkeleton title="About" isPage /> );

		expect( normalizeMarkup( editorHtml ) ).not.toEqual( normalizeMarkup( fixtureHtml ) );
		expect( editorHtml.match( /<a /g ) ).toHaveLength( 1 );
	} );
} );
