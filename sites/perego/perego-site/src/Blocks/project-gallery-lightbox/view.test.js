/**
 * Jest — perego/project-gallery Interactivity API store (spec 003 / M3, US2). Covers open/close with
 * focus restore + scroll lock, prev/next wraparound, keyboard (Esc/arrows), and the derived src/alt/
 * counter state. Reuses the spec-001 @wordpress/interactivity test double.
 */

function loadStore( context, element ) {
	jest.resetModules();
	const wp = require( '@wordpress/interactivity' );
	wp.__setMockContext( context );
	wp.__setMockElement( element || { ref: document.body } );
	require( './view.js' );

	return wp.__getStore( 'perego/project-gallery' );
}

function ctx( overrides = {} ) {
	return {
		isOpen: false,
		activeIndex: 0,
		count: 3,
		items: [
			{ src: 'a.jpg', alt: 'A' },
			{ src: 'b.jpg', alt: 'B' },
			{ src: 'c.jpg', alt: 'C' },
		],
		counter: '%1$s / %2$s',
		...overrides,
	};
}

beforeEach( () => {
	document.body.innerHTML =
		'<section class="project-gallery"><button class="opener"></button>' +
		'<div class="lightbox"><button class="lightbox__close"></button>' +
		'<button class="lightbox__nav--next"></button></div></section>';
	window.requestAnimationFrame = ( cb ) => cb();
} );

describe( 'open / close', () => {
	test( 'open sets the active index, opens, locks scroll', () => {
		const opener = document.querySelector( '.opener' );
		const context = ctx( { index: 2 } );
		const { actions } = loadStore( context, { ref: opener } );

		actions.open();

		expect( context.activeIndex ).toBe( 2 );
		expect( context.isOpen ).toBe( true );
		expect( document.body.style.overflow ).toBe( 'hidden' );
	} );

	test( 'close restores focus to the opener and clears the scroll lock', () => {
		const opener = document.querySelector( '.opener' );
		opener.focus();
		const context = ctx( { index: 0 } );
		const { actions } = loadStore( context, { ref: opener } );

		actions.open();
		actions.close();

		expect( context.isOpen ).toBe( false );
		expect( document.body.style.overflow ).toBe( '' );
		expect( document.activeElement ).toBe( opener );
	} );

	test( 'close is a no-op when already closed', () => {
		const context = ctx( { isOpen: false } );
		const { actions } = loadStore( context );

		expect( () => actions.close() ).not.toThrow();
		expect( context.isOpen ).toBe( false );
	} );
} );

describe( 'navigation', () => {
	test( 'next wraps past the last image', () => {
		const context = ctx( { activeIndex: 2 } );
		const { actions } = loadStore( context );

		actions.next();

		expect( context.activeIndex ).toBe( 0 );
	} );

	test( 'prev wraps before the first image', () => {
		const context = ctx( { activeIndex: 0 } );
		const { actions } = loadStore( context );

		actions.prev();

		expect( context.activeIndex ).toBe( 2 );
	} );
} );

describe( 'keyboard', () => {
	test( 'Escape closes, arrows navigate — only while open', () => {
		const context = ctx( { isOpen: true, activeIndex: 0 } );
		const { actions } = loadStore( context );
		const dialog = document.querySelector( '.lightbox' );

		actions.onKeydown( { key: 'ArrowRight', currentTarget: dialog } );
		expect( context.activeIndex ).toBe( 1 );

		actions.onKeydown( { key: 'ArrowLeft', currentTarget: dialog } );
		expect( context.activeIndex ).toBe( 0 );

		actions.onKeydown( { key: 'Escape', currentTarget: dialog } );
		expect( context.isOpen ).toBe( false );
	} );

	test( 'ignores keys entirely while closed', () => {
		const context = ctx( { isOpen: false, activeIndex: 0 } );
		const { actions } = loadStore( context );
		const dialog = document.querySelector( '.lightbox' );

		actions.onKeydown( { key: 'ArrowRight', currentTarget: dialog } );

		expect( context.activeIndex ).toBe( 0 );
	} );
} );

describe( 'derived state', () => {
	test( 'currentSrc/currentAlt reflect the active image', () => {
		const context = ctx( { activeIndex: 1 } );
		const { state } = loadStore( context );

		expect( state.currentSrc ).toBe( 'b.jpg' );
		expect( state.currentAlt ).toBe( 'B' );
	} );

	test( 'counterLabel is 1-based over the total', () => {
		const context = ctx( { activeIndex: 2, count: 3 } );
		const { state } = loadStore( context );

		expect( state.counterLabel ).toBe( '3 / 3' );
	} );

	test( 'lightboxHidden tracks the open flag', () => {
		const context = ctx( { isOpen: false } );
		const { callbacks } = loadStore( context );
		expect( callbacks.lightboxHidden() ).toBe( true );

		context.isOpen = true;
		expect( callbacks.lightboxHidden() ).toBe( false );
	} );
} );
