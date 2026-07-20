function loadFooterView() {
	jest.resetModules();
	require( './view.js' );
}

beforeEach( () => {
	document.body.innerHTML = `
		<footer class="site-footer">
			<form class="footer-form corex-form" data-corex-form="perego-quick-message">
				<div data-corex-field="message"><textarea name="message" data-max-words="200"></textarea></div>
			</form>
		</footer>`;
} );

test( 'projects a live "n / 200 words" counter onto the quick-message field', () => {
	loadFooterView();
	const textarea = document.querySelector( 'textarea[name="message"]' );
	const field = document.querySelector( '[data-corex-field="message"]' );

	expect( field.getAttribute( 'data-perego-counter' ) ).toBe( '0 / 200 words' );

	textarea.value = 'Hello there friend';
	textarea.dispatchEvent( new Event( 'input', { bubbles: true } ) );

	expect( field.getAttribute( 'data-perego-counter' ) ).toBe( '3 / 200 words' );
} );

test( 'counts words, not characters — one long word is one word, not many characters', () => {
	loadFooterView();
	const textarea = document.querySelector( 'textarea[name="message"]' );
	const field = document.querySelector( '[data-corex-field="message"]' );

	textarea.value = 'supercalifragilisticexpialidocious';
	textarea.dispatchEvent( new Event( 'input', { bubbles: true } ) );

	expect( field.getAttribute( 'data-perego-counter' ) ).toBe( '1 / 200 words' );
} );

test( 'collapses extra whitespace and does not count empty runs as words', () => {
	loadFooterView();
	const textarea = document.querySelector( 'textarea[name="message"]' );
	const field = document.querySelector( '[data-corex-field="message"]' );

	textarea.value = '  Hello   there   ';
	textarea.dispatchEvent( new Event( 'input', { bubbles: true } ) );

	expect( field.getAttribute( 'data-perego-counter' ) ).toBe( '2 / 200 words' );
} );

test( 'derives the limit from data-max-words so markup and counter agree', () => {
	document.querySelector( 'textarea[name="message"]' ).setAttribute( 'data-max-words', '50' );
	loadFooterView();

	expect( document.querySelector( '[data-corex-field="message"]' ).getAttribute( 'data-perego-counter' ) )
		.toBe( '0 / 50 words' );
} );

test( 'does nothing without a data-max-words limit (no phantom "0 / NaN" counter)', () => {
	document.querySelector( 'textarea[name="message"]' ).removeAttribute( 'data-max-words' );
	loadFooterView();

	expect( document.querySelector( '[data-corex-field="message"]' ).hasAttribute( 'data-perego-counter' ) )
		.toBe( false );
} );

test( 'resyncs to zero when the shared CoreX runtime resets the form after a successful submission', () => {
	jest.useFakeTimers();
	loadFooterView();
	const textarea = document.querySelector( 'textarea[name="message"]' );
	const field = document.querySelector( '[data-corex-field="message"]' );

	textarea.value = 'Hello there friend';
	textarea.dispatchEvent( new Event( 'input', { bubbles: true } ) );
	expect( field.getAttribute( 'data-perego-counter' ) ).toBe( '3 / 200 words' );

	document.querySelector( 'form' ).reset();
	jest.runAllTimers();

	expect( field.getAttribute( 'data-perego-counter' ) ).toBe( '0 / 200 words' );
	jest.useRealTimers();
} );
