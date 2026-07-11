/**
 * Jest — perego/site-header language toggle (spec 001 T023, US2, FR-008). Covers
 * actions.switchLanguage (applies lang/dir, persists a cookie, reloads) and callbacks.init
 * re-applying a previously persisted locale on mount. General header interactivity (sticky
 * scroll, mobile menu, focus trap) is covered in view.test.js.
 *
 * jsdom's `window.location` and `location.reload` are both non-configurable, so the real
 * reload() runs and jsdom logs its "Not implemented: navigation" console.error — that's
 * expected here (a real browser would actually reload the page) and is asserted explicitly via
 * `toHaveErrored()` rather than silenced, so an unrelated console.error would still fail the test.
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
			<nav class="perego-header__nav"></nav>
			<div class="perego-header__lang">
				<button data-locale="en" aria-pressed="true">EN</button>
				<button data-locale="ar" aria-pressed="false">AR</button>
			</div>
		</div>`;

	return document.querySelector( '.perego-header' );
}

beforeEach( () => {
	document.body.innerHTML = '';
	document.cookie = 'perego_lang=; max-age=0; path=/';
	document.documentElement.lang = 'en';
	document.documentElement.dir = 'ltr';
	document.documentElement.classList.remove( 'lang-ar' );
	window.matchMedia = jest.fn().mockReturnValue( { matches: false } );
} );

describe( 'actions.switchLanguage', () => {
	test( 'switching to Arabic flips lang/dir, adds the RTL class, persists a cookie, and reloads', () => {
		const ref = buildHeader();
		const { actions } = loadStore( {
			context: { isMenuOpen: false, isScrolled: false },
			element: { ref },
		} );
		const button = ref.querySelector( '[data-locale="ar"]' );

		actions.switchLanguage( { target: button } );

		expect( document.documentElement.lang ).toBe( 'ar' );
		expect( document.documentElement.dir ).toBe( 'rtl' );
		expect(
			document.documentElement.classList.contains( 'lang-ar' )
		).toBe( true );
		expect( document.cookie ).toContain( 'perego_lang=ar' );
		expect( console ).toHaveErrored();
	} );

	test( 'switching back to English clears the RTL state', () => {
		document.documentElement.lang = 'ar';
		document.documentElement.dir = 'rtl';
		document.documentElement.classList.add( 'lang-ar' );
		const ref = buildHeader();
		const { actions } = loadStore( {
			context: { isMenuOpen: false, isScrolled: false },
			element: { ref },
		} );
		const button = ref.querySelector( '[data-locale="en"]' );

		actions.switchLanguage( { target: button } );

		expect( document.documentElement.lang ).toBe( 'en' );
		expect( document.documentElement.dir ).toBe( 'ltr' );
		expect(
			document.documentElement.classList.contains( 'lang-ar' )
		).toBe( false );
		expect( document.cookie ).toContain( 'perego_lang=en' );
		expect( console ).toHaveErrored();
	} );

	test( 'is a no-op when the click target has no data-locale (never reaches reload)', () => {
		const ref = buildHeader();
		const { actions } = loadStore( {
			context: { isMenuOpen: false, isScrolled: false },
			element: { ref },
		} );

		actions.switchLanguage( { target: ref } );

		expect( document.cookie ).not.toContain( 'perego_lang=' );
	} );
} );

describe( 'callbacks.init — persisted language on mount', () => {
	test( 're-applies a previously persisted Arabic cookie', () => {
		document.cookie = 'perego_lang=ar; path=/';
		const ref = buildHeader();
		const { callbacks } = loadStore( {
			context: { isMenuOpen: false, isScrolled: false },
			element: { ref },
		} );

		callbacks.init();

		expect( document.documentElement.lang ).toBe( 'ar' );
		expect( document.documentElement.dir ).toBe( 'rtl' );
	} );

	test( 'leaves lang/dir untouched when no cookie is persisted', () => {
		const ref = buildHeader();
		const { callbacks } = loadStore( {
			context: { isMenuOpen: false, isScrolled: false },
			element: { ref },
		} );

		callbacks.init();

		expect( document.documentElement.lang ).toBe( 'en' );
		expect( document.documentElement.dir ).toBe( 'ltr' );
	} );
} );
