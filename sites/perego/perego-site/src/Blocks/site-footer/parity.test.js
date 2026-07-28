/**
 * Markup-parity test for the site-footer live-canvas block (spec 021 C2 / T009; DECISIONS 2026-07-21).
 *
 * The footer is a hybrid block, so parity is asserted only on its STATIC surfaces — the contact column
 * (`.footer-contact`) and the bottom bar (`.site-footer__bottom`) — which `FooterSkeleton` renders as
 * real markup. The dynamic quick-message and careers columns are labelled placeholders in the editor and
 * are intentionally excluded. The fixture `__fixtures__/front-footer.html` is a captured live render of
 * the standard footer. Per `../../Editor/parity.js`, only tags, class hooks, and nesting are compared.
 */
import { renderToString } from '@wordpress/element';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizeMarkup } from '../../Editor/parity';
import { FooterSkeleton, SEED_CONTACT_CHANNELS, SEED_SOCIAL_LINKS } from './preview';

function subtree( html, selector ) {
	const template = document.createElement( 'template' );
	template.innerHTML = html.trim();
	const node = template.content.querySelector( selector );
	if ( ! node ) {
		throw new Error( `No ${ selector } found in markup` );
	}
	return node.outerHTML;
}

const fixtureHtml = readFileSync( join( __dirname, '__fixtures__', 'front-footer.html' ), 'utf8' );
const editorHtml = renderToString(
	<FooterSkeleton channels={ SEED_CONTACT_CHANNELS } socialLinks={ SEED_SOCIAL_LINKS } />
);

describe( 'site-footer markup parity', () => {
	test.each( [ '.footer-contact', '.site-footer__bottom' ] )(
		'the editor skeleton matches the PHP front-end structure for %s',
		( selector ) => {
			expect( normalizeMarkup( subtree( editorHtml, selector ) ) )
				.toEqual( normalizeMarkup( subtree( fixtureHtml, selector ) ) );
		}
	);

	test( 'a drifted contact column (missing social list) is detected as a mismatch', () => {
		const drifted = renderToString(
			<FooterSkeleton channels={ SEED_CONTACT_CHANNELS } socialLinks={ [] } />
		);

		expect( normalizeMarkup( subtree( drifted, '.footer-contact' ) ) )
			.not.toEqual( normalizeMarkup( subtree( fixtureHtml, '.footer-contact' ) ) );
	} );
} );
