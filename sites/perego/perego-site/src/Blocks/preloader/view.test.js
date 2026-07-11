/**
 * Jest — perego/preloader Interactivity API store (spec 001 T031, US3, FR-009). Covers the
 * first-visit-per-session gate, the reduced-motion opt-out, the soft/hard hide timers (kept as
 * two independent timeouts — see view.js), and fail-safe behavior when sessionStorage throws.
 */

function loadCallbacks( context ) {
	jest.resetModules();
	const wpInteractivity = require( '@wordpress/interactivity' );
	wpInteractivity.__setMockContext( context );
	require( './view.js' );

	return wpInteractivity.__getStore( 'perego/preloader' ).callbacks;
}

beforeEach( () => {
	window.matchMedia = jest.fn().mockReturnValue( { matches: false } );
	window.sessionStorage.clear();
	jest.useRealTimers();
} );

describe( 'reduced motion', () => {
	test( 'hides immediately and never marks the session as shown', () => {
		window.matchMedia = jest.fn().mockReturnValue( { matches: true } );
		const context = { isHidden: false };
		const setItemSpy = jest.spyOn(
			window.sessionStorage.__proto__,
			'setItem'
		);
		const { init } = loadCallbacks( context );

		init();

		expect( context.isHidden ).toBe( true );
		expect( setItemSpy ).not.toHaveBeenCalled();
		setItemSpy.mockRestore();
	} );
} );

describe( 'already shown this session', () => {
	test( 'hides immediately without re-marking the session', () => {
		window.sessionStorage.setItem( 'perego-preloaded', '1' );
		const context = { isHidden: false };
		const setItemSpy = jest.spyOn(
			window.sessionStorage.__proto__,
			'setItem'
		);
		const { init } = loadCallbacks( context );

		init();

		expect( context.isHidden ).toBe( true );
		expect( setItemSpy ).not.toHaveBeenCalled();
		setItemSpy.mockRestore();
	} );
} );

describe( 'first visit this session', () => {
	test( 'marks the session as shown and stays visible until the soft-hide timer fires', () => {
		jest.useFakeTimers();
		const context = { isHidden: false };
		const { init } = loadCallbacks( context );

		init();

		expect( context.isHidden ).toBe( false );
		expect( window.sessionStorage.getItem( 'perego-preloaded' ) ).toBe(
			'1'
		);

		jest.advanceTimersByTime( 900 );
		expect( context.isHidden ).toBe( true );
	} );

	test( 'the hard-hide timer still clears the overlay even if something kept it open past 2.5s', () => {
		jest.useFakeTimers();
		const context = { isHidden: false };
		const { init } = loadCallbacks( context );

		init();
		jest.advanceTimersByTime( 900 );
		context.isHidden = false; // simulate something re-showing it after the soft-hide fired
		jest.advanceTimersByTime( 1600 ); // → 2500ms total

		expect( context.isHidden ).toBe( true );
	} );
} );

describe( 'sessionStorage unavailable (locked-down / private browsing)', () => {
	test( 'getItem throwing is treated as "already shown" and fails safe', () => {
		jest
			.spyOn( window.sessionStorage.__proto__, 'getItem' )
			.mockImplementation( () => {
				throw new Error( 'storage disabled' );
			} );
		const context = { isHidden: false };
		const { init } = loadCallbacks( context );

		init();

		expect( context.isHidden ).toBe( true );

		window.sessionStorage.__proto__.getItem.mockRestore();
	} );

	test( 'setItem throwing does not crash — the hide timers still run', () => {
		jest.useFakeTimers();
		jest
			.spyOn( window.sessionStorage.__proto__, 'setItem' )
			.mockImplementation( () => {
				throw new Error( 'storage disabled' );
			} );
		const context = { isHidden: false };
		const { init } = loadCallbacks( context );

		expect( () => init() ).not.toThrow();
		jest.advanceTimersByTime( 900 );
		expect( context.isHidden ).toBe( true );

		window.sessionStorage.__proto__.setItem.mockRestore();
	} );
} );
