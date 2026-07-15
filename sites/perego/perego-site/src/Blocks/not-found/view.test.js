function loadNotFoundView() {
	jest.resetModules();
	require( './view.js' );
}

beforeEach( () => {
	window.matchMedia = jest.fn().mockReturnValue( { matches: false } );
	document.body.innerHTML = `
		<main class="error-page">
			<div class="error-page__inner"></div>
			<span class="orb"></span><span class="orb"></span>
		</main>`;
} );

test( 'applies the locked handoff pointer parallax to its decorative elements', () => {
	loadNotFoundView();
	window.dispatchEvent( new MouseEvent( 'pointermove', { clientX: window.innerWidth / 2, clientY: window.innerHeight / 2 } ) );

	const inner = document.querySelector( '.error-page__inner' );
	const [ firstOrb, secondOrb ] = document.querySelectorAll( '.orb' );

	expect( inner.style.transform ).toBe( 'translate3d(0px, 0px, 0)' );
	expect( firstOrb.style.marginLeft ).toBe( '0px' );
	expect( secondOrb.style.marginTop ).toBe( '0px' );
} );

test( 'does not register decorative motion when reduced motion is requested', () => {
	window.matchMedia.mockReturnValue( { matches: true } );
	loadNotFoundView();
	window.dispatchEvent( new MouseEvent( 'pointermove', { clientX: 0, clientY: 0 } ) );

	expect( document.querySelector( '.error-page__inner' ).style.transform ).toBe( '' );
} );
