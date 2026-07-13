function loadChooser() {
	jest.resetModules();
	require( './view.js' );
}

beforeEach( () => {
	document.body.innerHTML = `
		<section class="contact-hero">
			<form data-corex-form="perego-project-brief"><select name="services[]" multiple>
				<option value="video-editing"></option><option value="graphic-design"></option>
			</select></form>
			<div data-perego-service-chooser>
				<button class="svc-choice" data-service="video-editing" aria-pressed="false"></button>
				<button class="svc-choice" data-service="graphic-design" aria-pressed="false"></button>
			</div>
		</section>`;
} );

test( 'synchronizes each handoff choice button with the submitted CoreX multi-select', () => {
	loadChooser();
	const form = document.querySelector( 'form' );
	const select = form.querySelector( 'select' );
	const video = document.querySelector( '[data-service="video-editing"]' );
	const graphic = document.querySelector( '[data-service="graphic-design"]' );
	const changes = jest.fn();
	select.addEventListener( 'change', changes );

	video.click();
	graphic.click();
	video.click();

	expect( select.options[ 0 ].selected ).toBe( false );
	expect( select.options[ 1 ].selected ).toBe( true );
	expect( video.getAttribute( 'aria-pressed' ) ).toBe( 'false' );
	expect( graphic.getAttribute( 'aria-pressed' ) ).toBe( 'true' );
	expect( graphic.classList.contains( 'is-selected' ) ).toBe( true );
	expect( changes ).toHaveBeenCalledTimes( 3 );
} );
