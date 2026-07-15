function loadModule() {
	jest.resetModules();
	require( './view.js' );
}

function sliderMarkup( trackClass ) {
	return `<div class="${ trackClass === 'corp-track' ? 'corp-slider' : 'indiv-slider' }">
		<button class="corp-arrow--prev"></button>
		<div class="${ trackClass }"></div>
		<button class="corp-arrow--next"></button>
	</div>`;
}

beforeEach( () => {
	document.body.innerHTML = '';
	Object.defineProperty( HTMLElement.prototype, 'clientWidth', { configurable: true, value: 400 } );
	HTMLElement.prototype.scrollBy = jest.fn();
} );

test.each( [ 'corp-track', 'indiv-track' ] )( 'scrolls the %s track from its reference controls', ( trackClass ) => {
	document.body.innerHTML = sliderMarkup( trackClass );
	loadModule();

	document.querySelector( '.corp-arrow--next' ).click();
	document.querySelector( '.corp-arrow--prev' ).click();

	const calls = document.querySelector( `.${ trackClass }` ).scrollBy.mock.calls;
	expect( calls ).toEqual( [ [ { left: 340, behavior: 'smooth' } ], [ { left: -340, behavior: 'smooth' } ] ] );
} );
