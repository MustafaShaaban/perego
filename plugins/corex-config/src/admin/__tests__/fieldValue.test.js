/**
 * FieldValue — a captured value an operator can act on.
 *
 * The careers form stores the applicant's CV as a URL. Every admin surface stringified it, so the
 * reviewer had a document they could see but not open (client report 2026-07-27). These pin the
 * link behaviour and, just as importantly, that a visitor-supplied `javascript:` string never
 * becomes an anchor.
 *
 * No @testing-library in this repo, so the component is driven through a real jsdom root.
 */
import { createRoot } from '@wordpress/element';
// eslint-disable-next-line import/no-extraneous-dependencies
import { act } from 'react';

import FieldValue from '../components/FieldValue.js';

function render( props ) {
	const container = document.createElement( 'div' );
	document.body.appendChild( container );
	act( () => {
		createRoot( container ).render( <FieldValue { ...props } /> );
	} );
	return container;
}

beforeAll( () => {
	global.IS_REACT_ACT_ENVIRONMENT = true;
} );

afterEach( () => {
	document.body.innerHTML = '';
} );

it( 'renders an http(s) value as a link that opens away from the admin session', () => {
	const container = render( { value: 'https://perego.local/wp-content/uploads/cv.pdf' } );
	const link = container.querySelector( 'a' );

	expect( link ).not.toBeNull();
	expect( link.getAttribute( 'href' ) ).toBe( 'https://perego.local/wp-content/uploads/cv.pdf' );
	expect( link.getAttribute( 'target' ) ).toBe( '_blank' );
	// Without noopener the opened tab can reach back into the admin session through window.opener.
	expect( link.getAttribute( 'rel' ) ).toBe( 'noopener noreferrer' );
} );

it.each( [
	[ 'javascript:alert(1)' ],
	[ 'data:text/html,<script>alert(1)</script>' ],
	[ 'ftp://example.test/file' ],
] )( 'leaves %s as inert text rather than an anchor', ( value ) => {
	const container = render( { value } );

	expect( container.querySelector( 'a' ) ).toBeNull();
	expect( container.textContent ).toBe( value );
} );

it( 'does not link a sentence that merely contains a URL', () => {
	const container = render( { value: 'see https://example.test for details' } );

	// Anchoring the whole string would produce a broken href; the value is prose, not a link.
	expect( container.querySelector( 'a' ) ).toBeNull();
} );

it( 'shows the empty marker for a missing value, and honours an override', () => {
	expect( render( { value: '' } ).textContent ).toBe( '—' );
	expect( render( { value: null } ).textContent ).toBe( '—' );
	expect( render( { value: undefined, empty: '' } ).textContent ).toBe( '' );
} );

it( 'keeps an object as JSON, because the structure is the value', () => {
	const container = render( { value: { source: 'newsletter' } } );

	expect( container.textContent ).toBe( '{"source":"newsletter"}' );
} );

it( 'renders a plain scalar unchanged', () => {
	expect( render( { value: 'Mustafa Shaaban' } ).textContent ).toBe( 'Mustafa Shaaban' );
	expect( render( { value: 42 } ).textContent ).toBe( '42' );
} );
