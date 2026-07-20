function loadModule() {
	jest.resetModules();
	require( './view.js' );
}

function sliderMarkup( trackClass ) {
	return `<div class="${ trackClass === 'corp-track' ? 'corp-slider' : 'indiv-slider' }">
		<button class="corp-arrow--prev"></button>
		<div class="${ trackClass }"><button class="corp-card" data-gallery="a.jpg,b.jpg"></button></div>
		<button class="corp-arrow--next"></button>
	</div>`;
}

function scrollTo( track, left ) {
	track.scrollLeft = left;
	track.dispatchEvent( new Event( 'scroll' ) );
}

beforeEach( () => {
	document.body.innerHTML = '';
	Object.defineProperty( HTMLElement.prototype, 'clientWidth', { configurable: true, value: 400 } );
	Object.defineProperty( HTMLElement.prototype, 'scrollWidth', { configurable: true, value: 1200 } );
	HTMLElement.prototype.scrollBy = jest.fn();
} );

test.each( [ 'corp-track', 'indiv-track' ] )( 'scrolls the %s track from its reference controls', ( trackClass ) => {
	document.body.innerHTML = sliderMarkup( trackClass );
	loadModule();

	// Mid-scroll: both directions available.
	scrollTo( document.querySelector( `.${ trackClass }` ), 100 );

	document.querySelector( '.corp-arrow--next' ).click();
	document.querySelector( '.corp-arrow--prev' ).click();

	const calls = document.querySelector( `.${ trackClass }` ).scrollBy.mock.calls;
	expect( calls ).toEqual( [ [ { left: 340, behavior: 'smooth' } ], [ { left: -340, behavior: 'smooth' } ] ] );
} );

test( 'marks the prototype edge states and disables the arrow at its extreme', () => {
	document.body.innerHTML = sliderMarkup( 'corp-track' );
	loadModule();

	const slider = document.querySelector( '.corp-slider' );
	const track = document.querySelector( '.corp-track' );
	const prev = document.querySelector( '.corp-arrow--prev' );
	const next = document.querySelector( '.corp-arrow--next' );

	// At the start (initial state): at-start, prev disabled.
	expect( slider.classList.contains( 'at-start' ) ).toBe( true );
	expect( prev.disabled ).toBe( true );
	expect( next.disabled ).toBe( false );

	// Mid-track: neither edge.
	scrollTo( track, 300 );
	expect( slider.classList.contains( 'at-start' ) ).toBe( false );
	expect( slider.classList.contains( 'at-end' ) ).toBe( false );
	expect( prev.disabled ).toBe( false );

	// At the end (scrollWidth 1200 − clientWidth 400): at-end, next disabled.
	scrollTo( track, 800 );
	expect( slider.classList.contains( 'at-end' ) ).toBe( true );
	expect( next.disabled ).toBe( true );
} );

test( 'treats RTL negative scroll positions as distance from the start', () => {
	document.body.innerHTML = sliderMarkup( 'corp-track' );
	loadModule();

	const slider = document.querySelector( '.corp-slider' );

	// RTL browsers report negative scrollLeft as the user moves away from the start.
	scrollTo( document.querySelector( '.corp-track' ), -300 );

	expect( slider.classList.contains( 'at-start' ) ).toBe( false );
	expect( slider.classList.contains( 'at-end' ) ).toBe( false );
} );

test( 'a drag pans the track and swallows the click so it never opens a card lightbox', () => {
	document.body.innerHTML = sliderMarkup( 'corp-track' );
	loadModule();

	const track = document.querySelector( '.corp-track' );
	const card = track.querySelector( '.corp-card' );
	const cardClick = jest.fn();
	card.addEventListener( 'click', cardClick );

	track.dispatchEvent( new MouseEvent( 'pointerdown', { clientX: 200, bubbles: true } ) );
	track.dispatchEvent( new MouseEvent( 'pointermove', { clientX: 140, bubbles: true } ) );
	track.dispatchEvent( new MouseEvent( 'pointerup', { bubbles: true } ) );

	expect( track.scrollLeft ).toBe( 60 ); // panned by the 60px drag

	card.dispatchEvent( new MouseEvent( 'click', { bubbles: true, cancelable: true } ) );
	expect( cardClick ).not.toHaveBeenCalled();

	// A plain click (no drag) still reaches the card.
	card.dispatchEvent( new MouseEvent( 'click', { bubbles: true, cancelable: true } ) );
	expect( cardClick ).toHaveBeenCalledTimes( 1 );
} );
