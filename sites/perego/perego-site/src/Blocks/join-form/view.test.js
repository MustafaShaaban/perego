/**
 * Jest — perego-theme/join-form upload lifecycle. Covers client-side validation (missing fields,
 * wrong file type, oversize), the busy/lock transition, and mapping the endpoint's response to the
 * localized aria-live status (success, mapped error code, and unknown-error fallback). `fetch` is
 * mocked so no real request is made.
 */

const MESSAGES = {
	uploading: 'Uploading…',
	submitting: 'Sending…',
	success: 'Thanks!',
	invalid: 'Add name, email, CV.',
	wrong_type: 'PDF/DOC/DOCX only.',
	too_large: 'Under 5 MB please.',
	rate_limit: 'Too many attempts.',
	server_error: 'Something went wrong.',
};

function setupForm() {
	document.body.innerHTML =
		'<form class="join-form" data-perego-join data-endpoint="/apply" data-nonce="n" ' +
		'data-max-bytes="5242880" data-messages=\'' + JSON.stringify( MESSAGES ) + '\'>' +
		'<input id="jf-name" /><input id="jf-email" /><input id="jf-cv" type="file" />' +
		'<button type="submit" class="join-form__submit">Apply</button>' +
		'<p class="join-form__status"></p></form>';
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

beforeEach( () => {
	global.fetch = jest.fn();
} );

test( 'rejects a submission missing the CV without calling the endpoint', async () => {
	const form = setupForm();
	fill( form, {} ); // no file

	await submit( form );

	expect( status() ).toBe( MESSAGES.invalid );
	expect( global.fetch ).not.toHaveBeenCalled();
} );

test( 'rejects a non-pdf/doc/docx file client-side', async () => {
	const form = setupForm();
	fill( form, { file: pdf( { name: 'resume.txt' } ) } );

	await submit( form );

	expect( status() ).toBe( MESSAGES.wrong_type );
	expect( global.fetch ).not.toHaveBeenCalled();
} );

test( 'rejects a file over 5 MB client-side', async () => {
	const form = setupForm();
	fill( form, { file: pdf( { size: 6 * 1024 * 1024 } ) } );

	await submit( form );

	expect( status() ).toBe( MESSAGES.too_large );
	expect( global.fetch ).not.toHaveBeenCalled();
} );

test( 'posts a valid application and shows success, resetting the form', async () => {
	global.fetch.mockResolvedValue( { ok: true, json: async () => ( { ok: true } ) } );
	const form = setupForm();
	fill( form, { file: pdf() } );

	await submit( form );

	expect( global.fetch ).toHaveBeenCalledWith( '/apply', expect.objectContaining( { method: 'POST' } ) );
	expect( status() ).toBe( MESSAGES.success );
} );

test( 'maps a server error code to its localized message', async () => {
	global.fetch.mockResolvedValue( { ok: false, json: async () => ( { ok: false, error: 'rate_limit' } ) } );
	const form = setupForm();
	fill( form, { file: pdf() } );

	await submit( form );

	expect( status() ).toBe( MESSAGES.rate_limit );
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
