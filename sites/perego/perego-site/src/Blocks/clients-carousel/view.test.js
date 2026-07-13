/**
 * Jest — perego-theme/clients-carousel Swiper enhancement (spec M6). The cards are server-rendered
 * and reachable without JS; this only verifies the progressive upgrade: every [data-clients-swiper]
 * becomes one Swiper wired with the a11y-relevant modules, its own nav/pagination elements, and the
 * responsive breakpoints. The `swiper` ESM package is mocked so no real module is loaded.
 */

const mockSwiperCtor = jest.fn();

jest.mock( 'swiper', () => ( {
	__esModule: true,
	// A constructable stub (view.js calls `new Swiper()`), forwarding to the spy.
	default: function ( ...args ) {
		mockSwiperCtor( ...args );
	},
} ) );

jest.mock( 'swiper/modules', () => ( {
	__esModule: true,
	Navigation: 'Navigation',
	Pagination: 'Pagination',
	Keyboard: 'Keyboard',
	A11y: 'A11y',
} ) );

function twoCarousels() {
	document.body.innerHTML =
		'<div class="swiper clients-swiper clients-swiper--corporate" data-clients-swiper>' +
		'<div class="swiper-wrapper"></div>' +
		'<button class="swiper-button-prev"></button>' +
		'<button class="swiper-button-next"></button>' +
		'<div class="swiper-pagination"></div></div>' +
		'<div class="swiper clients-swiper clients-swiper--individual" data-clients-swiper>' +
		'<div class="swiper-wrapper"></div>' +
		'<button class="swiper-button-prev"></button>' +
		'<button class="swiper-button-next"></button>' +
		'<div class="swiper-pagination"></div></div>';
}

function loadView() {
	jest.resetModules();
	mockSwiperCtor.mockClear();
	require( './view.js' );
}

test( 'initializes one Swiper per [data-clients-swiper] with the a11y modules', () => {
	twoCarousels();
	loadView();

	expect( mockSwiperCtor ).toHaveBeenCalledTimes( 2 );

	const [ el, options ] = mockSwiperCtor.mock.calls[ 0 ];
	expect( el.matches( '[data-clients-swiper]' ) ).toBe( true );
	expect( options.modules ).toEqual( [
		'Navigation',
		'Pagination',
		'Keyboard',
		'A11y',
	] );
	expect( options.keyboard.enabled ).toBe( true );
	expect( options.a11y.enabled ).toBe( true );
	expect( options.watchOverflow ).toBe( true );
} );

test( 'wires each carousel to its own navigation buttons and pagination', () => {
	twoCarousels();
	loadView();

	const carousels = document.querySelectorAll( '[data-clients-swiper]' );
	mockSwiperCtor.mock.calls.forEach( ( [ el, options ], i ) => {
		expect( el ).toBe( carousels[ i ] );
		expect( options.navigation.prevEl ).toBe(
			el.querySelector( '.swiper-button-prev' )
		);
		expect( options.navigation.nextEl ).toBe(
			el.querySelector( '.swiper-button-next' )
		);
		expect( options.pagination.el ).toBe(
			el.querySelector( '.swiper-pagination' )
		);
		expect( options.pagination.clickable ).toBe( true );
	} );
} );

test( 'sizes the corporate carousel as dense small tiles', () => {
	twoCarousels();
	loadView();

	const [ , options ] = mockSwiperCtor.mock.calls[ 0 ];
	expect( options.slidesPerView ).toBe( 3 );
	expect( options.breakpoints[ 640 ].slidesPerView ).toBe( 5 );
	expect( options.breakpoints[ 1024 ].slidesPerView ).toBe( 8 );
} );

test( 'sizes the individual carousel as a few wide cards per view', () => {
	twoCarousels();
	loadView();

	const [ , options ] = mockSwiperCtor.mock.calls[ 1 ];
	expect( options.slidesPerView ).toBe( 1.2 );
	expect( options.breakpoints[ 640 ].slidesPerView ).toBe( 2.2 );
	expect( options.breakpoints[ 1024 ].slidesPerView ).toBe( 4 );
} );

test( 'is a no-op when the page has no carousels', () => {
	document.body.innerHTML = '<main>no carousels here</main>';
	loadView();

	expect( mockSwiperCtor ).not.toHaveBeenCalled();
} );
