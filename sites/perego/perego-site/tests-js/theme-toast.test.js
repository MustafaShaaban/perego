/**
 * Jest — the theme's form-outcome toast (`perego-theme/assets/src/js/toast.js`).
 *
 * The module lives in the theme, not this package, but this is the only Jest runner the site has and
 * relative imports resolve outside `rootDir` (that setting scopes test *discovery*). Covered here are
 * the three behaviours the client's 2026-07-29 report turned into rules: one toast per outcome even
 * when two announcers fire, one banner at a time on a phone, and a stack that follows the visual
 * viewport so the iOS keyboard cannot strand it. The look itself is CSS and is verified in a browser
 * — jsdom computes no layout.
 */

const TOAST = '.perego-toast';
const COPY = { toastDismiss: 'Dismiss', toastSuccessTitle: 'Sent', toastErrorTitle: 'Not sent' };

/**
 * jsdom's matchMedia is not implemented, so every test states the width it is testing. The stub is
 * installed before the module loads, because the stack is built on the first toast.
 */
function viewport( { phone } ) {
	window.matchMedia = jest.fn( ( query ) => ( {
		matches: phone && query.includes( 'max-width: 620px' ),
		media: query,
	} ) );
}

/**
 * Initialised once for the file, deliberately.
 *
 * `initFormToasts()` binds its two listeners to `document`, and nothing about resetting
 * `document.body` unbinds them — calling it per test left the previous test's listener in place, so
 * the third announcement in the file produced four toasts. Both `matchMedia` and `visualViewport`
 * are read lazily (at the moment a toast is shown, and when the stack is built), so a single
 * initialisation still sees whatever each test sets up.
 */
let initialised = false;

function setup( { phone = false, visualViewport = null } = {} ) {
	// Removing the form also removes the stack, so the next toast rebuilds it against this test's
	// stubs rather than reusing the previous test's.
	document.body.innerHTML = '<form class="corex-form"><p class="corex-form__status"></p></form>';
	viewport( { phone } );

	if ( visualViewport ) {
		Object.defineProperty( window, 'visualViewport', {
			configurable: true,
			value: visualViewport,
		} );
	}

	window.peregoTheme = COPY;

	if ( ! initialised ) {
		require( '../../perego-theme/assets/src/js/toast.js' ).initFormToasts();
		initialised = true;
	}

	return document.querySelector( 'form' );
}

/** What the runtime does: write the status paragraph, then announce it. */
function announce( form, message, kind = 'error' ) {
	form.querySelector( '.corex-form__status' ).textContent = message;
	form.dispatchEvent( new CustomEvent( `corex:form:${ kind }`, { bubbles: true } ) );
}

function messages() {
	return Array.from( document.querySelectorAll( '.perego-toast__message' ) ).map(
		( node ) => node.textContent
	);
}

afterEach( () => {
	delete window.visualViewport;
	delete window.peregoTheme;
} );

describe( 'form outcome toasts', () => {
	it( 'shows one toast per announced outcome', () => {
		const form = setup();

		announce( form, 'Please review the highlighted fields.' );

		expect( document.querySelectorAll( TOAST ) ).toHaveLength( 1 );
	} );

	it( 'collapses a second announcement of the same outcome into one toast', () => {
		const form = setup();

		announce( form, 'Please review the highlighted fields.' );
		announce( form, 'Please review the highlighted fields.' );

		expect( document.querySelectorAll( TOAST ) ).toHaveLength( 1 );
	} );

	it( 'still shows a different kind of outcome that arrives straight after', () => {
		const form = setup();

		announce( form, 'Please review the highlighted fields.' );
		announce( form, 'Thanks — we will be in touch.', 'success' );

		expect( messages() ).toEqual( [
			'Please review the highlighted fields.',
			'Thanks — we will be in touch.',
		] );
	} );

	// Paired with the test above: together they pin the repeat guard to kind *and* message. Keyed on
	// either alone, one of the two would start swallowing a real second outcome.
	it( 'still shows a different message of the same kind', () => {
		const form = setup( { phone: false } );

		announce( form, 'First problem.' );
		announce( form, 'Second problem.' );

		expect( messages() ).toEqual( [ 'First problem.', 'Second problem.' ] );
	} );

	it( 'keeps only the newest banner on a phone', () => {
		const form = setup( { phone: true } );

		announce( form, 'First problem.' );
		announce( form, 'Second problem.' );

		expect( messages() ).toEqual( [ 'Second problem.' ] );
	} );

	it( 'says nothing when the outcome carries no message', () => {
		const form = setup();

		announce( form, '   ' );

		expect( document.querySelectorAll( TOAST ) ).toHaveLength( 0 );
	} );
} );

describe( 'the stack and the visual viewport', () => {
	/** Just enough of `window.visualViewport`: a readable offset and the two events toast.js binds. */
	function fakeViewport( offsetTop ) {
		const listeners = {};

		return {
			offsetTop,
			listeners,
			addEventListener: ( type, handler ) => {
				listeners[ type ] = handler;
			},
		};
	}

	it( 'writes the current visual-viewport offset onto the stack', () => {
		const viewportStub = fakeViewport( 0 );
		const form = setup( { phone: true, visualViewport: viewportStub } );

		announce( form, 'Please review the highlighted fields.' );

		expect(
			document.getElementById( 'perego-toasts' ).style.getPropertyValue(
				'--perego-toast-vv-offset'
			)
		).toBe( '0px' );
	} );

	it( 'follows the offset when the keyboard moves the visible area', () => {
		const viewportStub = fakeViewport( 0 );
		const form = setup( { phone: true, visualViewport: viewportStub } );
		announce( form, 'Please review the highlighted fields.' );

		viewportStub.offsetTop = 240;
		viewportStub.listeners.resize( new Event( 'resize' ) );

		expect(
			document.getElementById( 'perego-toasts' ).style.getPropertyValue(
				'--perego-toast-vv-offset'
			)
		).toBe( '240px' );
	} );

	it( 'shows the toast unchanged when the browser has no visual viewport', () => {
		const form = setup( { phone: true } );

		announce( form, 'Please review the highlighted fields.' );

		const stack = document.getElementById( 'perego-toasts' );
		expect( document.querySelectorAll( TOAST ) ).toHaveLength( 1 );
		expect( stack.style.getPropertyValue( '--perego-toast-vv-offset' ) ).toBe( '' );
	} );
} );
