/**
 * Jest — perego/hero-slider Interactivity API store (spec 002 / M2, US1). Covers the auto-advance
 * timer + reduced-motion gate, dot navigation with stop-on-interaction, hover + visibility
 * suspension, and the derived live-region label. Reuses the spec-001 @wordpress/interactivity test
 * double.
 */

function loadStore( context ) {
	jest.resetModules();
	const wpInteractivity = require( '@wordpress/interactivity' );
	wpInteractivity.__setMockContext( context );
	require( './view.js' );

	return wpInteractivity.__getStore( 'perego/hero-slider' );
}

function baseContext( overrides = {} ) {
	return {
		activeIndex: 0,
		isPlaying: true,
		count: 3,
		index: 0,
		announce: 'Slide %1$s of %2$s',
		...overrides,
	};
}

beforeEach( () => {
	window.matchMedia = jest.fn().mockReturnValue( { matches: false } );
	jest.useRealTimers();
} );

describe( 'auto-advance', () => {
	test( 'init starts a 6.5s timer that advances and wraps the active slide', () => {
		jest.useFakeTimers();
		const context = baseContext();
		const { callbacks } = loadStore( context );

		callbacks.init();
		expect( context.activeIndex ).toBe( 0 );

		jest.advanceTimersByTime( 6500 );
		expect( context.activeIndex ).toBe( 1 );

		jest.advanceTimersByTime( 6500 * 2 );
		expect( context.activeIndex ).toBe( 0 ); // wrapped past slide 2
	} );

	test( 'never auto-advances under reduced motion and clears isPlaying', () => {
		window.matchMedia = jest.fn().mockReturnValue( { matches: true } );
		jest.useFakeTimers();
		const context = baseContext();
		const { callbacks } = loadStore( context );

		callbacks.init();
		jest.advanceTimersByTime( 6500 * 3 );

		expect( context.activeIndex ).toBe( 0 );
		expect( context.isPlaying ).toBe( false );
	} );

	test( 'stops advancing while the tab is hidden and resumes when visible', () => {
		jest.useFakeTimers();
		const context = baseContext();
		const { callbacks } = loadStore( context );
		callbacks.init();

		Object.defineProperty( document, 'hidden', {
			configurable: true,
			get: () => true,
		} );
		document.dispatchEvent( new Event( 'visibilitychange' ) );
		jest.advanceTimersByTime( 6500 * 2 );
		expect( context.activeIndex ).toBe( 0 );

		Object.defineProperty( document, 'hidden', {
			configurable: true,
			get: () => false,
		} );
		document.dispatchEvent( new Event( 'visibilitychange' ) );
		jest.advanceTimersByTime( 6500 );
		expect( context.activeIndex ).toBe( 1 );
	} );
} );

describe( 'manual navigation stops autoplay (WCAG 2.2.2)', () => {
	test( 'goTo jumps to the clicked dot index and halts the timer', () => {
		const context = baseContext( { index: 2 } );
		const { actions } = loadStore( context );

		actions.goTo();

		expect( context.activeIndex ).toBe( 2 );
		expect( context.isPlaying ).toBe( false );
	} );
} );

describe( 'swipe navigation', () => {
	test( 'next and prev advance and wrap the active slide', () => {
		const context = baseContext( { activeIndex: 2, count: 3 } );
		const { actions } = loadStore( context );

		actions.next();
		expect( context.activeIndex ).toBe( 0 ); // wrapped past the last slide

		actions.prev();
		expect( context.activeIndex ).toBe( 2 ); // wrapped back to the last slide
	} );

	test( 'a left swipe past the threshold advances and stops autoplay (LTR)', () => {
		const context = baseContext( { activeIndex: 0, count: 3 } );
		const { actions } = loadStore( context );

		actions.pointerDown( { clientX: 200 } );
		actions.pointerUp( { clientX: 120 } ); // dragged 80px left

		expect( context.activeIndex ).toBe( 1 );
		expect( context.isPlaying ).toBe( false );
	} );

	test( 'a right swipe goes to the previous slide', () => {
		const context = baseContext( { activeIndex: 1, count: 3 } );
		const { actions } = loadStore( context );

		actions.pointerDown( { clientX: 100 } );
		actions.pointerUp( { clientX: 190 } ); // dragged 90px right

		expect( context.activeIndex ).toBe( 0 );
	} );

	test( 'a short drag under the threshold is a tap, not a swipe', () => {
		jest.useFakeTimers(); // pointerUp resumes autoplay for a tap
		const context = baseContext( { activeIndex: 1, count: 3 } );
		const { actions } = loadStore( context );

		actions.pointerDown( { clientX: 100 } );
		actions.pointerUp( { clientX: 120 } ); // 20px — below the 45px threshold

		expect( context.activeIndex ).toBe( 1 );
	} );

	test( 'swipe direction is reading-aware in RTL', () => {
		document.documentElement.dir = 'rtl';
		const context = baseContext( { activeIndex: 0, count: 3 } );
		const { actions } = loadStore( context );

		actions.pointerDown( { clientX: 100 } );
		actions.pointerUp( { clientX: 190 } ); // right swipe is "forward" in RTL

		expect( context.activeIndex ).toBe( 1 );
		document.documentElement.dir = '';
	} );
} );

describe( 'hover suspend/resume does not change play intent', () => {
	test( 'pause halts the timer without clearing isPlaying; resume restarts it', () => {
		jest.useFakeTimers();
		const context = baseContext();
		const { actions } = loadStore( context );
		actions.startAutoplay();

		actions.pause();
		expect( context.isPlaying ).toBe( true );
		jest.advanceTimersByTime( 6500 * 2 );
		expect( context.activeIndex ).toBe( 0 );

		actions.resume();
		jest.advanceTimersByTime( 6500 );
		expect( context.activeIndex ).toBe( 1 );
	} );
} );

describe( 'derived state', () => {
	test( 'currentSlideLabel substitutes the active slide numbers into the template', () => {
		const context = baseContext( { activeIndex: 1, count: 3 } );
		const { state } = loadStore( context );

		expect( state.currentSlideLabel ).toBe( 'Slide 2 of 3' );
	} );

	test( 'slideHidden and dotSelected compare the element index to the active index', () => {
		const context = baseContext( { activeIndex: 1, index: 1 } );
		const { callbacks } = loadStore( context );

		expect( callbacks.slideHidden() ).toBe( false );
		expect( callbacks.dotSelected() ).toBe( true );

		context.index = 0;
		expect( callbacks.slideHidden() ).toBe( true );
		expect( callbacks.dotSelected() ).toBe( false );
	} );
} );
