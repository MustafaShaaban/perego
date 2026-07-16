function loadFooterView() {
	jest.resetModules();
	require( './view.js' );
}

beforeEach( () => {
	document.body.innerHTML = `
		<footer class="site-footer">
			<form class="footer-form corex-form" data-corex-form="perego-quick-message">
				<div data-corex-field="message"><textarea name="message" maxlength="1200"></textarea></div>
			</form>
		</footer>`;
} );

test( 'projects the handoff "n / 1200" counter onto the quick-message field', () => {
	loadFooterView();
	const textarea = document.querySelector( 'textarea[name="message"]' );
	const field = document.querySelector( '[data-corex-field="message"]' );

	expect( field.getAttribute( 'data-perego-counter' ) ).toBe( '0 / 1200' );

	textarea.value = 'Hello';
	textarea.dispatchEvent( new Event( 'input', { bubbles: true } ) );

	expect( field.getAttribute( 'data-perego-counter' ) ).toBe( '5 / 1200' );
} );

test( 'derives the limit from the textarea maxlength so markup and counter agree', () => {
	document.querySelector( 'textarea[name="message"]' ).setAttribute( 'maxlength', '500' );
	loadFooterView();

	expect( document.querySelector( '[data-corex-field="message"]' ).getAttribute( 'data-perego-counter' ) )
		.toBe( '0 / 500' );
} );

test( 'does nothing without a maxlength (no phantom "0 / NaN" counter)', () => {
	document.querySelector( 'textarea[name="message"]' ).removeAttribute( 'maxlength' );
	loadFooterView();

	expect( document.querySelector( '[data-corex-field="message"]' ).hasAttribute( 'data-perego-counter' ) )
		.toBe( false );
} );

test( 'resyncs to zero when the shared CoreX runtime resets the form after a successful submission', () => {
	jest.useFakeTimers();
	loadFooterView();
	const textarea = document.querySelector( 'textarea[name="message"]' );
	const field = document.querySelector( '[data-corex-field="message"]' );

	textarea.value = 'Hello';
	textarea.dispatchEvent( new Event( 'input', { bubbles: true } ) );
	expect( field.getAttribute( 'data-perego-counter' ) ).toBe( '5 / 1200' );

	document.querySelector( 'form' ).reset();
	jest.runAllTimers();

	expect( field.getAttribute( 'data-perego-counter' ) ).toBe( '0 / 1200' );
	jest.useRealTimers();
} );
