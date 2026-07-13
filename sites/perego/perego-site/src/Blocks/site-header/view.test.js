/**
 * Jest — perego/site-header Interactivity API store (spec 001 T013, FR-002–FR-004, FR-008).
 * Covers sticky-scroll state, the mobile slide-in nav (open/close, focus trap, scroll lock,
 * backdrop/link-click close, focus restore), the mobile tap-accordion, and the reduced-motion
 * class. Language-toggle behavior has its own coverage in language-toggle.test.js.
 */

function loadStore( { context, element } ) {
	jest.resetModules();
	const wpInteractivity = require( '@wordpress/interactivity' );
	wpInteractivity.__setMockContext( context );
	wpInteractivity.__setMockElement( element );
	require( './view.js' );

	return wpInteractivity.__getStore( 'perego/site-header' );
}

function buildHeader() {
	document.body.innerHTML = `
		<div class="perego-header">
			<div class="perego-header__nav-backdrop"></div>
			<nav class="perego-header__nav">
				<a href="#one">One</a>
				<a href="#two">Two</a>
			</nav>
		</div>`;

	return document.querySelector( '.perego-header' );
}

beforeEach( () => {
	document.body.innerHTML = '';
	document.cookie = 'perego_lang=; max-age=0; path=/';
	window.matchMedia = jest.fn().mockReturnValue( { matches: false } );
	window.scrollY = 0;
} );

describe( 'callbacks.init — sticky scroll', () => {
	test( 'context.isScrolled tracks window.scrollY past the threshold', () => {
		const ref = buildHeader();
		const context = { isMenuOpen: false, isScrolled: false };
		const { callbacks } = loadStore( { context, element: { ref } } );

		callbacks.init();
		expect( context.isScrolled ).toBe( false );

		window.scrollY = 30;
		window.dispatchEvent( new Event( 'scroll' ) );
		expect( context.isScrolled ).toBe( true );

		window.scrollY = 0;
		window.dispatchEvent( new Event( 'scroll' ) );
		expect( context.isScrolled ).toBe( false );
	} );

	test( 'adds is-reduced-motion when the visitor prefers reduced motion', () => {
		window.matchMedia = jest.fn().mockReturnValue( { matches: true } );
		const ref = buildHeader();
		const { callbacks } = loadStore( {
			context: { isMenuOpen: false, isScrolled: false },
			element: { ref },
		} );

		callbacks.init();

		expect( ref.classList.contains( 'is-reduced-motion' ) ).toBe( true );
	} );

	test( 'does not add is-reduced-motion by default', () => {
		const ref = buildHeader();
		const { callbacks } = loadStore( {
			context: { isMenuOpen: false, isScrolled: false },
			element: { ref },
		} );

		callbacks.init();

		expect( ref.classList.contains( 'is-reduced-motion' ) ).toBe( false );
	} );
} );

describe( 'callbacks.init — backdrop and nav-link close', () => {
	test( 'clicking the backdrop closes an open menu and clears the scroll lock', () => {
		const ref = buildHeader();
		const context = { isMenuOpen: true, isScrolled: false };
		const { callbacks } = loadStore( { context, element: { ref } } );

		callbacks.init();
		document.body.style.overflow = 'hidden';

		ref.querySelector( '.perego-header__nav-backdrop' ).dispatchEvent(
			new Event( 'click' )
		);

		expect( context.isMenuOpen ).toBe( false );
		expect( document.body.style.overflow ).toBe( '' );
	} );

	test( 'clicking a nav link closes an open menu', () => {
		const ref = buildHeader();
		const context = { isMenuOpen: true, isScrolled: false };
		const { callbacks } = loadStore( { context, element: { ref } } );

		callbacks.init();
		ref.querySelector( '.perego-header__nav a' ).dispatchEvent(
			new Event( 'click' )
		);

		expect( context.isMenuOpen ).toBe( false );
	} );
} );

describe( 'actions.toggleMenu / closeMenu — scroll lock + focus restore', () => {
	test( 'opening locks body scroll and remembers the previously focused element', () => {
		const ref = buildHeader();
		const trigger = document.createElement( 'button' );
		document.body.appendChild( trigger );
		trigger.focus();
		const context = { isMenuOpen: false, isScrolled: false };
		const { actions } = loadStore( { context, element: { ref } } );

		actions.toggleMenu();

		expect( context.isMenuOpen ).toBe( true );
		expect( document.body.style.overflow ).toBe( 'hidden' );
	} );

	test( 'opening moves focus into the nav panel so the trap + Escape work', () => {
		const ref = buildHeader();
		const trigger = document.createElement( 'button' );
		document.body.appendChild( trigger );
		trigger.focus();
		const context = { isMenuOpen: false, isScrolled: false };
		const { actions } = loadStore( { context, element: { ref } } );

		actions.toggleMenu();

		const firstLink = ref.querySelector( '.perego-header__nav a[href]' );
		expect( document.activeElement ).toBe( firstLink );
	} );

	test( 'closing restores focus to the element that opened the menu', () => {
		const ref = buildHeader();
		const trigger = document.createElement( 'button' );
		document.body.appendChild( trigger );
		trigger.focus();
		const context = { isMenuOpen: false, isScrolled: false };
		const { actions } = loadStore( { context, element: { ref } } );

		actions.toggleMenu();
		actions.toggleMenu();

		expect( context.isMenuOpen ).toBe( false );
		expect( document.body.style.overflow ).toBe( '' );
		expect( document.activeElement ).toBe( trigger );
	} );

	test( 'closeMenu() is a no-op when the menu is not open', () => {
		const ref = buildHeader();
		const context = { isMenuOpen: false, isScrolled: false };
		const { actions } = loadStore( { context, element: { ref } } );

		actions.closeMenu();

		expect( context.isMenuOpen ).toBe( false );
		expect( document.body.style.overflow ).toBe( '' );
	} );
} );

describe( 'actions.handleMenuKeydown — focus trap + Escape', () => {
	function buildPanel() {
		document.body.innerHTML = `
			<div class="panel">
				<a href="#a">A</a>
				<button>B</button>
			</div>`;

		return document.querySelector( '.panel' );
	}

	test( 'Escape closes the menu', () => {
		const ref = buildHeader();
		const context = { isMenuOpen: true, isScrolled: false };
		const { actions } = loadStore( { context, element: { ref } } );

		actions.handleMenuKeydown( { key: 'Escape', currentTarget: ref } );

		expect( context.isMenuOpen ).toBe( false );
	} );

	test( 'Tab on the last focusable element wraps to the first', () => {
		const ref = buildHeader();
		const context = { isMenuOpen: true, isScrolled: false };
		const { actions } = loadStore( { context, element: { ref } } );
		const panel = buildPanel();
		const first = panel.querySelector( 'a' );
		const last = panel.querySelector( 'button' );
		last.focus();
		const preventDefault = jest.fn();

		actions.handleMenuKeydown( {
			key: 'Tab',
			shiftKey: false,
			currentTarget: panel,
			preventDefault,
		} );

		expect( preventDefault ).toHaveBeenCalled();
		expect( document.activeElement ).toBe( first );
	} );

	test( 'Shift+Tab on the first focusable element wraps to the last', () => {
		const ref = buildHeader();
		const context = { isMenuOpen: true, isScrolled: false };
		const { actions } = loadStore( { context, element: { ref } } );
		const panel = buildPanel();
		const first = panel.querySelector( 'a' );
		const last = panel.querySelector( 'button' );
		first.focus();
		const preventDefault = jest.fn();

		actions.handleMenuKeydown( {
			key: 'Tab',
			shiftKey: true,
			currentTarget: panel,
			preventDefault,
		} );

		expect( preventDefault ).toHaveBeenCalled();
		expect( document.activeElement ).toBe( last );
	} );

	test( 'ignores keydown entirely while the menu is closed', () => {
		const ref = buildHeader();
		const context = { isMenuOpen: false, isScrolled: false };
		const { actions } = loadStore( { context, element: { ref } } );
		const panel = buildPanel();
		const preventDefault = jest.fn();

		actions.handleMenuKeydown( {
			key: 'Tab',
			shiftKey: false,
			currentTarget: panel,
			preventDefault,
		} );

		expect( preventDefault ).not.toHaveBeenCalled();
	} );
} );

describe( 'actions.toggleMobileDropdown — mobile tap-accordion', () => {
	function buildNavItem() {
		document.body.innerHTML = `
			<div class="perego-header__nav-item">
				<a class="perego-header__nav-toggle" href="#services">Services</a>
			</div>`;

		return document.querySelector( '.perego-header__nav-toggle' );
	}

	test( 'toggles is-open on mobile viewports', () => {
		window.matchMedia = jest.fn().mockReturnValue( { matches: true } );
		const ref = buildHeader();
		const { actions } = loadStore( {
			context: { isMenuOpen: false, isScrolled: false },
			element: { ref },
		} );
		const link = buildNavItem();
		const item = link.closest( '.perego-header__nav-item' );
		const preventDefault = jest.fn();

		actions.toggleMobileDropdown( {
			currentTarget: link,
			preventDefault,
		} );

		expect( preventDefault ).toHaveBeenCalled();
		expect( item.classList.contains( 'is-open' ) ).toBe( true );
	} );

	test( 'does nothing on desktop viewports (pure-CSS hover/focus handles it there)', () => {
		window.matchMedia = jest.fn().mockReturnValue( { matches: false } );
		const ref = buildHeader();
		const { actions } = loadStore( {
			context: { isMenuOpen: false, isScrolled: false },
			element: { ref },
		} );
		const link = buildNavItem();
		const item = link.closest( '.perego-header__nav-item' );
		const preventDefault = jest.fn();

		actions.toggleMobileDropdown( {
			currentTarget: link,
			preventDefault,
		} );

		expect( preventDefault ).not.toHaveBeenCalled();
		expect( item.classList.contains( 'is-open' ) ).toBe( false );
	} );
} );
