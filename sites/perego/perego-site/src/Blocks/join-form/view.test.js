/**
 * Jest — perego-theme/join-form upload lifecycle. Covers client-side validation (missing fields,
 * wrong file type, oversize) reported per field, with a **distinct message per failure reason** (not
 * one sentence reused everywhere — spec 020 round 4) via the shared `corex-form__error` node +
 * `aria-invalid` (spec 020 round 2/3), live re-validation that clears a field's error as soon as it
 * becomes valid (blur once touched, then every keystroke/change — round 4), the busy/lock transition,
 * the submit-result banner's `is-success`/`is-error` class toggling (shared with the contact form's
 * `corex-form__status`), and mapping the endpoint's response to the localized aria-live status
 * (success, mapped error code, and unknown-error fallback). `fetch` is mocked so no real request is
 * made.
 */

const MESSAGES = {
	uploading: 'Uploading…',
	submitting: 'Sending…',
	success: 'Thanks!',
	name_required: 'Please enter your name.',
	email_invalid: 'Please enter a valid email address.',
	portfolio_invalid: 'Please enter a full link, starting with https://',
	cv_required: 'Please attach your CV.',
	form_has_errors: 'Please fix the highlighted fields and try again.',
	wrong_type: 'PDF/DOC/DOCX only.',
	too_large: 'Under 5 MB please.',
	rate_limit: 'Too many attempts.',
	server_error: 'Something went wrong.',
};

function setupForm() {
	document.body.innerHTML =
		'<form class="join-form" data-perego-join data-endpoint="/apply" data-nonce="n" ' +
		'data-max-bytes="5242880" data-messages=\'' + JSON.stringify( MESSAGES ) + '\'>' +
		'<input id="jf-name" /><span id="jf-name-error"></span>' +
		'<input id="jf-email" /><span id="jf-email-error"></span>' +
		'<input id="jf-portfolio" type="url" /><span id="jf-portfolio-error"></span>' +
		'<label class="file-drop"><span class="file-drop__text">Upload your CV here</span>' +
		'<span class="file-drop__filename" aria-live="polite"></span>' +
		'<input id="jf-cv" type="file" /></label><span id="jf-cv-error"></span>' +
		'<button type="submit" class="join-form__submit">Apply</button>' +
		'<p class="join-form__status corex-form__status"></p></form>';
	jest.resetModules();
	require( './view.js' );
	return document.querySelector( '.join-form' );
}

function fill( form, { name = 'Sara', email = 'sara@x.com', file } = {} ) {
	form.querySelector( '#jf-name' ).value = name;
	form.querySelector( '#jf-email' ).value = email;
	if ( file ) {
		Object.defineProperty( form.querySelector( '#jf-cv' ), 'files', { configurable: true, value: [ file ] } );
	}
}

function pdf( { name = 'cv.pdf', size = 1024 } = {} ) {
	const f = new File( [ 'x' ], name, { type: 'application/pdf' } );
	Object.defineProperty( f, 'size', { value: size } );
	return f;
}

async function submit( form ) {
	form.dispatchEvent( new Event( 'submit', { cancelable: true, bubbles: true } ) );
	await new Promise( ( r ) => setTimeout( r, 0 ) );
}

const status = () => document.querySelector( '.join-form__status' ).textContent;
const statusTone = () => ( document.querySelector( '.join-form__status' ).classList.contains( 'is-error' ) ? 'error'
	: document.querySelector( '.join-form__status' ).classList.contains( 'is-success' ) ? 'success' : null );
const fieldError = ( id ) => document.getElementById( id + '-error' ).textContent;
const isInvalid = ( id ) => document.getElementById( id ).getAttribute( 'aria-invalid' );

beforeEach( () => {
	global.fetch = jest.fn();
} );

test( 'rejects a submission missing the CV without calling the endpoint, flagging only the CV field with a CV-specific message', async () => {
	const form = setupForm();
	fill( form, {} ); // no file

	await submit( form );

	expect( status() ).toBe( MESSAGES.form_has_errors );
	expect( statusTone() ).toBe( 'error' );
	expect( fieldError( 'jf-cv' ) ).toBe( MESSAGES.cv_required );
	expect( isInvalid( 'jf-cv' ) ).toBe( 'true' );
	expect( fieldError( 'jf-name' ) ).toBe( '' );
	expect( isInvalid( 'jf-name' ) ).toBe( 'false' );
	expect( global.fetch ).not.toHaveBeenCalled();
} );

test( 'flags an empty name and an invalid email with their own distinct messages, not the CV', async () => {
	const form = setupForm();
	fill( form, { name: '', email: 'not-an-email', file: pdf() } );

	await submit( form );

	expect( fieldError( 'jf-name' ) ).toBe( MESSAGES.name_required );
	expect( isInvalid( 'jf-name' ) ).toBe( 'true' );
	expect( fieldError( 'jf-email' ) ).toBe( MESSAGES.email_invalid );
	expect( isInvalid( 'jf-email' ) ).toBe( 'true' );
	expect( fieldError( 'jf-name' ) ).not.toBe( fieldError( 'jf-email' ) ); // distinct, not the same sentence
	expect( fieldError( 'jf-cv' ) ).toBe( '' );
	expect( global.fetch ).not.toHaveBeenCalled();
} );

// Since round 7 the status paragraph is a screen-reader-only live region and the toast is the
// visual channel — so without these events a successful application looked, to a sighted visitor,
// exactly like a click that did nothing.
test( 'announces success with the event the theme toast listens for', async () => {
	const form = setupForm();
	const heard = jest.fn();
	document.addEventListener( 'corex:form:success', heard );
	fill( form, { file: pdf() } );
	global.fetch.mockResolvedValue( { ok: true, json: async () => ( { ok: true } ) } );

	await submit( form );

	expect( heard ).toHaveBeenCalledTimes( 1 );
	// The relay reads the status text, so it must already be written when the event fires.
	expect( status() ).toBe( MESSAGES.success );
	document.removeEventListener( 'corex:form:success', heard );
} );

test.each( [
	[ 'client-side validation', async ( form ) => {
		fill( form, {} ); // no CV
		await submit( form );
	} ],
	[ 'a mapped server rejection', async ( form ) => {
		fill( form, { file: pdf() } );
		global.fetch.mockResolvedValue( { ok: false, json: async () => ( { ok: false, error: 'rate_limit' } ) } );
		await submit( form );
	} ],
	[ 'a network failure', async ( form ) => {
		fill( form, { file: pdf() } );
		global.fetch.mockRejectedValue( new Error( 'offline' ) );
		await submit( form );
	} ],
] )( 'announces an error toast after %s', async ( _label, act ) => {
	const form = setupForm();
	const heard = jest.fn();
	document.addEventListener( 'corex:form:error', heard );

	await act( form );

	expect( heard ).toHaveBeenCalledTimes( 1 );
	expect( statusTone() ).toBe( 'error' );
	document.removeEventListener( 'corex:form:error', heard );
} );

test( 'stays silent while the upload is still in flight', async () => {
	const form = setupForm();
	const heard = jest.fn();
	document.addEventListener( 'corex:form:success', heard );
	document.addEventListener( 'corex:form:error', heard );
	fill( form, { file: pdf( { size: 900 * 1024 } ) } );
	// A promise that never settles: the form is left in its busy state.
	global.fetch.mockReturnValue( new Promise( () => {} ) );

	await submit( form );

	expect( status() ).toBe( MESSAGES.uploading );
	expect( heard ).not.toHaveBeenCalled();
	document.removeEventListener( 'corex:form:success', heard );
	document.removeEventListener( 'corex:form:error', heard );
} );

test( 'rejects a portfolio link that is not a full http(s) URL, and accepts an empty one', async () => {
	const form = setupForm();
	fill( form, { file: pdf() } );
	form.querySelector( '#jf-portfolio' ).value = 'linkedin.com/in/sara';

	await submit( form );

	expect( fieldError( 'jf-portfolio' ) ).toBe( MESSAGES.portfolio_invalid );
	expect( global.fetch ).not.toHaveBeenCalled();

	// A `javascript:` string parses as a URL but is not a link anyone should follow.
	form.querySelector( '#jf-portfolio' ).value = 'javascript:alert(1)';
	form.querySelector( '#jf-portfolio' ).dispatchEvent( new Event( 'input', { bubbles: true } ) );
	expect( fieldError( 'jf-portfolio' ) ).toBe( MESSAGES.portfolio_invalid );

	// Optional: blank is fine, and a real link clears the error live.
	form.querySelector( '#jf-portfolio' ).value = 'https://sara.example';
	form.querySelector( '#jf-portfolio' ).dispatchEvent( new Event( 'input', { bubbles: true } ) );
	expect( fieldError( 'jf-portfolio' ) ).toBe( '' );
	expect( isInvalid( 'jf-portfolio' ) ).toBe( 'false' );
} );

test( 'clears the name error live as soon as a valid name is typed, without touching other fields', async () => {
	const form = setupForm();
	fill( form, { name: '', email: 'sara@x.com', file: pdf() } );
	await submit( form ); // establishes the name error

	expect( fieldError( 'jf-name' ) ).toBe( MESSAGES.name_required );

	form.querySelector( '#jf-name' ).value = 'Sara';
	form.querySelector( '#jf-name' ).dispatchEvent( new Event( 'input', { bubbles: true } ) );

	expect( fieldError( 'jf-name' ) ).toBe( '' );
	expect( isInvalid( 'jf-name' ) ).toBe( 'false' );
} );

test( 'clears the email error live as soon as a valid email is typed', async () => {
	const form = setupForm();
	fill( form, { email: 'not-an-email', file: pdf() } );
	await submit( form );

	expect( fieldError( 'jf-email' ) ).toBe( MESSAGES.email_invalid );

	form.querySelector( '#jf-email' ).value = 'sara@x.com';
	form.querySelector( '#jf-email' ).dispatchEvent( new Event( 'input', { bubbles: true } ) );

	expect( fieldError( 'jf-email' ) ).toBe( '' );
} );

test( 'does not show an error on input before the field has ever been marked invalid (no premature nagging)', async () => {
	const form = setupForm();
	form.querySelector( '#jf-name' ).value = 'S';
	form.querySelector( '#jf-name' ).dispatchEvent( new Event( 'input', { bubbles: true } ) );

	expect( fieldError( 'jf-name' ) ).toBe( '' );
} );

test( 'validates a field on blur even before any submit attempt', () => {
	const form = setupForm();

	form.querySelector( '#jf-email' ).value = 'not-an-email';
	form.querySelector( '#jf-email' ).dispatchEvent( new Event( 'blur', { bubbles: true } ) );

	expect( fieldError( 'jf-email' ) ).toBe( MESSAGES.email_invalid );

	form.querySelector( '#jf-email' ).value = 'sara@x.com';
	form.querySelector( '#jf-email' ).dispatchEvent( new Event( 'blur', { bubbles: true } ) );

	expect( fieldError( 'jf-email' ) ).toBe( '' );
} );

test( 'clears the CV error live as soon as a valid file is chosen', async () => {
	const form = setupForm();
	fill( form, {} ); // no file
	await submit( form );

	expect( fieldError( 'jf-cv' ) ).toBe( MESSAGES.cv_required );

	Object.defineProperty( form.querySelector( '#jf-cv' ), 'files', { configurable: true, value: [ pdf() ] } );
	form.querySelector( '#jf-cv' ).dispatchEvent( new Event( 'change', { bubbles: true } ) );

	expect( fieldError( 'jf-cv' ) ).toBe( '' );
} );

test( 'rejects a non-pdf/doc/docx file client-side, flagging the CV field', async () => {
	const form = setupForm();
	fill( form, { file: pdf( { name: 'resume.txt' } ) } );

	await submit( form );

	expect( status() ).toBe( MESSAGES.form_has_errors );
	expect( fieldError( 'jf-cv' ) ).toBe( MESSAGES.wrong_type );
	expect( global.fetch ).not.toHaveBeenCalled();
} );

test( 'rejects a file over 5 MB client-side, flagging the CV field', async () => {
	const form = setupForm();
	fill( form, { file: pdf( { size: 6 * 1024 * 1024 } ) } );

	await submit( form );

	expect( status() ).toBe( MESSAGES.form_has_errors );
	expect( fieldError( 'jf-cv' ) ).toBe( MESSAGES.too_large );
	expect( global.fetch ).not.toHaveBeenCalled();
} );

test( 'posts a valid application and shows success (is-success), resetting the form and clearing field errors', async () => {
	global.fetch.mockResolvedValue( { ok: true, json: async () => ( { ok: true } ) } );
	const form = setupForm();
	fill( form, { file: pdf() } );

	await submit( form );

	expect( global.fetch ).toHaveBeenCalledWith( '/apply', expect.objectContaining( { method: 'POST' } ) );
	expect( status() ).toBe( MESSAGES.success );
	expect( statusTone() ).toBe( 'success' );
	expect( fieldError( 'jf-name' ) ).toBe( '' );
	expect( fieldError( 'jf-cv' ) ).toBe( '' );
} );

test( 'maps a server error code to its localized message (is-error)', async () => {
	global.fetch.mockResolvedValue( { ok: false, json: async () => ( { ok: false, error: 'rate_limit' } ) } );
	const form = setupForm();
	fill( form, { file: pdf() } );

	await submit( form );

	expect( status() ).toBe( MESSAGES.rate_limit );
	expect( statusTone() ).toBe( 'error' );
} );

test( 'falls back to server_error for an unknown error code', async () => {
	global.fetch.mockResolvedValue( { ok: false, json: async () => ( { ok: false, error: 'mystery' } ) } );
	const form = setupForm();
	fill( form, { file: pdf() } );

	await submit( form );

	expect( status() ).toBe( MESSAGES.server_error );
} );

test( 'shows server_error when the request throws', async () => {
	global.fetch.mockRejectedValue( new Error( 'network' ) );
	const form = setupForm();
	fill( form, { file: pdf() } );

	await submit( form );

	expect( status() ).toBe( MESSAGES.server_error );
} );

const filenameText = ( form ) => form.querySelector( '.file-drop__filename' ).textContent;
const hasFileClass = ( form ) => form.querySelector( '.file-drop' ).classList.contains( 'has-file' );

test( 'shows the selected filename and the has-file state after choosing a CV', () => {
	const form = setupForm();
	fill( form, { file: pdf( { name: 'my-resume.pdf' } ) } );
	form.querySelector( '#jf-cv' ).dispatchEvent( new Event( 'change', { bubbles: true } ) );

	expect( filenameText( form ) ).toBe( 'my-resume.pdf' );
	expect( hasFileClass( form ) ).toBe( true );
} );

test( 'clears the filename and has-file state after a successful submit resets the form', async () => {
	global.fetch.mockResolvedValue( { ok: true, json: async () => ( { ok: true } ) } );
	const form = setupForm();
	fill( form, { file: pdf( { name: 'my-resume.pdf' } ) } );
	form.querySelector( '#jf-cv' ).dispatchEvent( new Event( 'change', { bubbles: true } ) );
	expect( filenameText( form ) ).toBe( 'my-resume.pdf' );

	await submit( form );

	expect( filenameText( form ) ).toBe( '' );
	expect( hasFileClass( form ) ).toBe( false );
} );
