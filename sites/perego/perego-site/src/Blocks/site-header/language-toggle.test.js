/**
 * Jest — perego/site-header language switcher (spec Phase 5). The switcher is now real navigation:
 * server-rendered anchors to each locale's actual URL. The view-script's only job is client-side
 * state in the *fallback* (non-URL-managed) mode — mirror the persisted choice into <html> on load
 * and persist each switch-link click into the cookie so the choice carries across pages. In
 * URL-managed mode (Polylang, whose URLs already carry the language) the script stays out of it and
 * must never apply a stale cookie. General header interactivity is covered in view.test.js.
 */

function loadStore( { context, element } ) {
	jest.resetModules();
	const wpInteractivity = require( '@wordpress/interactivity' );
	wpInteractivity.__setMockContext( context );
	wpInteractivity.__setMockElement( element );
	require( './view.js' );

	return wpInteractivity.__getStore( 'perego/site-header' );
}

function buildHeader( { urlManaged = false } = {} ) {
	document.body.innerHTML = `
		<header class="site-header">
			<nav id="mainNav" class="main-nav"></nav>
			<div class="lang-toggle" data-lang-url-managed="${ urlManaged ? '1' : '0' }">
				<span aria-current="true" lang="en">EN</span>
				<a class="lang-toggle__btn" data-locale="ar" hreflang="ar" href="#ar">AR</a>
			</div>
		</header><div id="navBackdrop" class="nav-backdrop"></div>`;

	return document.querySelector( '.site-header' );
}

function initHeader( ref ) {
	const { callbacks } = loadStore( {
		context: { isMenuOpen: false, isScrolled: false },
		element: { ref },
	} );
	callbacks.init();
}

beforeEach( () => {
	document.body.innerHTML = '';
	document.cookie = 'perego_lang=; max-age=0; path=/';
	document.documentElement.lang = 'en';
	document.documentElement.dir = 'ltr';
	document.documentElement.classList.remove( 'lang-ar' );
	window.matchMedia = jest.fn().mockReturnValue( { matches: false } );
} );

describe( 'fallback (non-URL-managed) mode', () => {
	test( 're-applies a previously persisted Arabic cookie to <html> on init', () => {
		document.cookie = 'perego_lang=ar; path=/';

		initHeader( buildHeader( { urlManaged: false } ) );

		expect( document.documentElement.lang ).toBe( 'ar' );
		expect( document.documentElement.dir ).toBe( 'rtl' );
		expect( document.documentElement.classList.contains( 'lang-ar' ) ).toBe( true );
	} );

	test( 'leaves <html> untouched when no cookie is persisted', () => {
		initHeader( buildHeader( { urlManaged: false } ) );

		expect( document.documentElement.lang ).toBe( 'en' );
		expect( document.documentElement.dir ).toBe( 'ltr' );
	} );

	test( 'persists the cookie when a switch link is clicked (choice carries across pages)', () => {
		const ref = buildHeader( { urlManaged: false } );
		initHeader( ref );

		ref.querySelector( 'a[data-locale="ar"]' ).click();

		expect( document.cookie ).toContain( 'perego_lang=ar' );
	} );
} );

describe( 'URL-managed mode (Polylang)', () => {
	test( 'ignores a stale cookie on init — the URL is authoritative', () => {
		document.cookie = 'perego_lang=ar; path=/';

		initHeader( buildHeader( { urlManaged: true } ) );

		expect( document.documentElement.lang ).toBe( 'en' );
		expect( document.documentElement.dir ).toBe( 'ltr' );
	} );

	test( 'does not persist a cookie when a switch link is clicked', () => {
		const ref = buildHeader( { urlManaged: true } );
		initHeader( ref );

		ref.querySelector( 'a[data-locale="ar"]' ).click();

		expect( document.cookie ).not.toContain( 'perego_lang=ar' );
	} );
} );
