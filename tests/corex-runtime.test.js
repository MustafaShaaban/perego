/**
 * Jest unit tests for the Corex client runtime (spec 043). Loads the buildless
 * `window.Corex` against jsdom, mocking `fetch`/`wp.apiFetch`, and asserts the
 * envelope normalisation, the form lifecycle, loading/dedupe, and the events.
 */

const RUNTIME = '../plugins/corex-core/assets/js/corex-runtime.js';

const flush = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

function loadRuntimeWithFetch() {
	delete window.wp; // force the fetch path (no wp.apiFetch)
	jest.isolateModules( () => {
		require( RUNTIME );
	} );
}

function mockFetch( body, { ok = true, status = 200, nonJson = false } = {} ) {
	global.fetch = jest.fn( () =>
		Promise.resolve( {
			ok,
			status,
			json: () => ( nonJson ? Promise.reject( new Error( 'not json' ) ) : Promise.resolve( body ) ),
		} )
	);
}

function makeForm( schema ) {
	document.body.innerHTML = `
		<form class="corex-form"
			data-corex-endpoint="/wp-json/corex/v1/forms/contact"
			data-corex-nonce="abc123"
			data-corex-success="Thanks!"
			data-corex-error="Please check the form."
			data-corex-schema='${ JSON.stringify( schema ) }'>
			<div data-corex-field="email">
				<input name="email" type="email" />
				<span class="corex-form__error"></span>
			</div>
			<div class="corex-form__status"></div>
			<button type="submit">Send</button>
		</form>`;
	return document.querySelector( '.corex-form' );
}

const EMAIL_REQUIRED = [ { name: 'email', required: true, rules: [ { rule: 'required' }, { rule: 'email' } ] } ];

/** A form whose only field carries the rule under test, with the same wrapper contract. */
function makeFieldForm( name, rules, control = `<input name="${ name }" />`, formAttrs = '' ) {
	document.body.innerHTML = `
		<form class="corex-form"
			data-corex-endpoint="/wp-json/corex/v1/forms/contact"
			data-corex-nonce="abc123"
			data-corex-success="Thanks!"
			data-corex-error="Please check the form."
			data-corex-schema='${ JSON.stringify( [ { name, required: false, rules } ] ) }'
			${ formAttrs }>
			<div data-corex-field="${ name }">${ control }<span class="corex-form__error"></span></div>
			<div class="corex-form__status"></div>
			<button type="submit">Send</button>
		</form>`;
	return document.querySelector( '.corex-form' );
}

const errorText = ( form ) => form.querySelector( '.corex-form__error' ).textContent;
const submitForm = ( form ) => form.dispatchEvent( new Event( 'submit', { cancelable: true, bubbles: true } ) );

beforeEach( () => {
	document.body.innerHTML = '';
	loadRuntimeWithFetch();
} );

describe( 'Corex.api', () => {
	it( 'normalises a bare success body into an envelope and fires start/end events', async () => {
		mockFetch( { id: 7 } );
		const start = jest.fn();
		const end = jest.fn();
		document.addEventListener( 'corex:request:start', start );
		document.addEventListener( 'corex:request:end', end );

		const result = await window.Corex.api.post( '/x', { a: 1 } );

		expect( result.ok ).toBe( true );
		expect( result.envelope.ok ).toBe( true );
		expect( result.envelope.data ).toEqual( { id: 7 } );
		expect( start ).toHaveBeenCalledTimes( 1 );
		expect( end ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'passes through a real envelope unchanged', async () => {
		mockFetch( { ok: false, code: 'validation_failed', message: 'no', errors: { email: 'required' }, details: {} }, { ok: false, status: 422 } );
		const result = await window.Corex.api.post( '/x', {} );
		expect( result.envelope.code ).toBe( 'validation_failed' );
		expect( result.envelope.errors ).toEqual( { email: 'required' } );
	} );

	it( 'attaches the nonce header on the fetch path', async () => {
		mockFetch( { ok: true, message: '', data: {} } );
		await window.Corex.api.post( '/x', { a: 1 }, { nonce: 'n-42' } );
		const init = global.fetch.mock.calls[ 0 ][ 1 ];
		expect( init.headers[ 'X-WP-Nonce' ] ).toBe( 'n-42' );
	} );

	it( 'supports PATCH mutations through the shared request contract', async () => {
		mockFetch( { ok: true, message: '', data: { version: 2 } } );
		const result = await window.Corex.api.patch( '/x/7', { expected_version: 1 } );
		const init = global.fetch.mock.calls[ 0 ][ 1 ];

		expect( init.method ).toBe( 'PATCH' );
		expect( JSON.parse( init.body ) ).toEqual( { expected_version: 1 } );
		expect( result.envelope.data.version ).toBe( 2 );
	} );

	it( 'resolves a network failure to an error result and never throws', async () => {
		global.fetch = jest.fn( () => Promise.reject( new Error( 'offline' ) ) );
		const result = await window.Corex.api.get( '/x' );
		expect( result.ok ).toBe( false );
		expect( result.envelope.ok ).toBe( false );
		expect( result.envelope.code ).toBe( 'error' );
	} );

	it( 'resolves a non-JSON (HTML) error body to a generic error envelope', async () => {
		mockFetch( null, { ok: false, status: 500, nonJson: true } );
		const result = await window.Corex.api.get( '/x' );
		expect( result.envelope.ok ).toBe( false );
		expect( typeof result.envelope.message ).toBe( 'string' );
	} );
} );

describe( 'Corex.forms.bind', () => {
	it( 'blocks submit and shows a field error on a client-side validation failure', async () => {
		mockFetch( { ok: true, message: '', data: {} } );
		const form = makeForm( EMAIL_REQUIRED );
		window.Corex.forms.bind( form );

		form.dispatchEvent( new Event( 'submit', { cancelable: true, bubbles: true } ) );
		await flush();

		expect( global.fetch ).not.toHaveBeenCalled();
		expect( form.querySelector( '[data-corex-field="email"] .corex-form__error' ).textContent ).not.toBe( '' );
		expect( form.querySelector( 'input[name="email"]' ).getAttribute( 'aria-invalid' ) ).toBe( 'true' );
	} );

	it( 'submits once on valid input, resets, and fires corex:form:success', async () => {
		mockFetch( { ok: true, message: 'Saved', data: {} } );
		const form = makeForm( EMAIL_REQUIRED );
		form.querySelector( 'input[name="email"]' ).value = 'a@b.com';
		const success = jest.fn();
		form.addEventListener( 'corex:form:success', success );
		window.Corex.forms.bind( form );

		form.dispatchEvent( new Event( 'submit', { cancelable: true, bubbles: true } ) );
		await flush();

		expect( global.fetch ).toHaveBeenCalledTimes( 1 );
		expect( success ).toHaveBeenCalledTimes( 1 );
		expect( form.querySelector( '.corex-form__status' ).textContent ).toBe( 'Thanks!' );
	} );

	it( 'honors the published flow success definition returned by the visitor endpoint', async () => {
		mockFetch( { ok: true, message: '', data: { success: { type: 'inline', message: 'Flow complete.' } } } );
		const form = makeForm( EMAIL_REQUIRED );
		form.querySelector( 'input[name="email"]' ).value = 'a@b.com';
		window.Corex.forms.bind( form );

		form.dispatchEvent( new Event( 'submit', { cancelable: true, bubbles: true } ) );
		await flush();

		expect( form.querySelector( '.corex-form__status' ).textContent ).toBe( 'Flow complete.' );
	} );

	it( 'renders server envelope errors and fires corex:form:error', async () => {
		mockFetch(
			{ ok: false, code: 'validation_failed', message: 'Check fields', errors: { email: 'email' }, details: {} },
			{ ok: false, status: 422 }
		);
		const form = makeForm( EMAIL_REQUIRED );
		form.querySelector( 'input[name="email"]' ).value = 'a@b.com';
		const onError = jest.fn();
		form.addEventListener( 'corex:form:error', onError );
		window.Corex.forms.bind( form );

		form.dispatchEvent( new Event( 'submit', { cancelable: true, bubbles: true } ) );
		await flush();

		expect( onError ).toHaveBeenCalledTimes( 1 );
		expect( form.querySelector( '[data-corex-field="email"] .corex-form__error' ).textContent ).not.toBe( '' );
	} );

	it( 'sends every option a multiple select has selected, not just the first', async () => {
		mockFetch( { ok: true, message: '', data: {} } );
		const form = makeForm( EMAIL_REQUIRED );
		form.querySelector( 'input[name="email"]' ).value = 'a@b.com';
		// `select.value` reports only the FIRST selected option, so reading it dropped every
		// extra pick: a visitor choosing three services had one stored and one emailed.
		form.insertAdjacentHTML(
			'beforeend',
			`<select name="services[]" multiple>
				<option value="video-editing" selected></option>
				<option value="graphic-design"></option>
				<option value="motion-graphics" selected></option>
			</select>`
		);
		window.Corex.forms.bind( form );

		form.dispatchEvent( new Event( 'submit', { cancelable: true, bubbles: true } ) );
		await flush();

		const body = JSON.parse( global.fetch.mock.calls[ 0 ][ 1 ].body );
		// The `[]` suffix is stripped, and the unselected option stays out.
		expect( body.services ).toEqual( [ 'video-editing', 'motion-graphics' ] );
	} );

	it( 'sends an empty list when a multiple select has nothing selected', async () => {
		mockFetch( { ok: true, message: '', data: {} } );
		const form = makeForm( EMAIL_REQUIRED );
		form.querySelector( 'input[name="email"]' ).value = 'a@b.com';
		form.insertAdjacentHTML(
			'beforeend',
			'<select name="services[]" multiple><option value="video-editing"></option></select>'
		);
		window.Corex.forms.bind( form );

		form.dispatchEvent( new Event( 'submit', { cancelable: true, bubbles: true } ) );
		await flush();

		// An empty list — not '' and not the first option — is what the `required` rule and the
		// server's list sanitizer both expect to see for "nothing chosen".
		expect( JSON.parse( global.fetch.mock.calls[ 0 ][ 1 ].body ).services ).toEqual( [] );
	} );

	it( 'is idempotent — binding twice does not double-submit', async () => {
		mockFetch( { ok: true, message: '', data: {} } );
		const form = makeForm( EMAIL_REQUIRED );
		form.querySelector( 'input[name="email"]' ).value = 'a@b.com';
		window.Corex.forms.bind( form );
		window.Corex.forms.bind( form );

		form.dispatchEvent( new Event( 'submit', { cancelable: true, bubbles: true } ) );
		await flush();

		expect( global.fetch ).toHaveBeenCalledTimes( 1 );
	} );
} );

describe( 'live re-validation', () => {
	it( 'clears a field error as soon as the visitor fixes it, without resubmitting', async () => {
		mockFetch( { ok: true, message: '', data: {} } );
		const form = makeForm( EMAIL_REQUIRED );
		const input = form.querySelector( 'input[name="email"]' );
		window.Corex.forms.bind( form );

		submitForm( form );
		await flush();
		expect( errorText( form ) ).not.toBe( '' );

		// The error used to survive until the next submit, so a visitor who had corrected the field
		// had no way to know it (client report 2026-07-27).
		input.value = 'a@b.com';
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );

		expect( errorText( form ) ).toBe( '' );
		expect( input.getAttribute( 'aria-invalid' ) ).toBeNull();
	} );

	it( 'stays quiet while an untouched field is being typed into', () => {
		const form = makeForm( EMAIL_REQUIRED );
		const input = form.querySelector( 'input[name="email"]' );
		window.Corex.forms.bind( form );

		// Nagging someone on their first keystroke is worse than saying nothing.
		input.value = 'a';
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );

		expect( errorText( form ) ).toBe( '' );
	} );

	it( 'validates on blur even for a field that has never failed', () => {
		const form = makeForm( EMAIL_REQUIRED );
		const input = form.querySelector( 'input[name="email"]' );
		window.Corex.forms.bind( form );

		input.value = 'not-an-email';
		input.dispatchEvent( new Event( 'blur', { bubbles: false } ) );

		expect( errorText( form ) ).toBe( 'Enter a valid email address.' );
	} );

	it( 'only re-validates the field being edited, leaving its neighbours alone', async () => {
		mockFetch( { ok: false, message: 'x', errors: { email: 'required', name: 'required' } }, { ok: false, status: 422 } );
		document.body.innerHTML = `
			<form class="corex-form" data-corex-endpoint="/e" data-corex-nonce="n"
				data-corex-error="Please check the form."
				data-corex-schema='${ JSON.stringify( [
		{ name: 'name', required: true, rules: [ { rule: 'required' } ] },
		{ name: 'email', required: true, rules: [ { rule: 'required' } ] },
	] ) }'>
				<div data-corex-field="name"><input name="name" /><span class="corex-form__error"></span></div>
				<div data-corex-field="email"><input name="email" /><span class="corex-form__error"></span></div>
				<div class="corex-form__status"></div><button type="submit">Send</button>
			</form>`;
		const form = document.querySelector( '.corex-form' );
		window.Corex.forms.bind( form );

		submitForm( form );
		await flush();

		const nameError = form.querySelector( '[data-corex-field="name"] .corex-form__error' );
		const emailError = form.querySelector( '[data-corex-field="email"] .corex-form__error' );
		expect( nameError.textContent ).not.toBe( '' );
		expect( emailError.textContent ).not.toBe( '' );

		const nameInput = form.querySelector( 'input[name="name"]' );
		nameInput.value = 'Mustafa';
		nameInput.dispatchEvent( new Event( 'input', { bubbles: true } ) );

		// Re-running the whole form's validation here would have blanked the email error too.
		expect( nameError.textContent ).toBe( '' );
		expect( emailError.textContent ).not.toBe( '' );
	} );
} );

describe( 'validation rules the client used to skip', () => {
	it( 'enforces the word cap and names it, instead of a round-trip and a generic message', () => {
		const form = makeFieldForm( 'message', [ { rule: 'max_words', params: [ '5' ] } ], '<textarea name="message"></textarea>' );
		form.querySelector( 'textarea' ).value = 'one two three four five six';
		window.Corex.forms.bind( form );

		// `max_words` was missing from the client rule table, so the cap was server-only and the
		// 422 came back labelled "Please check this field."
		submitForm( form );

		expect( global.fetch ).not.toHaveBeenCalled();
		expect( errorText( form ) ).toBe( 'This message is too long.' );
	} );

	it( 'accepts a message at exactly the word cap', () => {
		const form = makeFieldForm( 'message', [ { rule: 'max_words', params: [ '5' ] } ], '<textarea name="message"></textarea>' );
		form.querySelector( 'textarea' ).value = '  one two   three four five  ';
		window.Corex.forms.bind( form );

		// Boundary: at the limit passes, over it fails; surrounding and repeated whitespace is not
		// a word. An empty value is the `required` rule's business, not this one's.
		submitForm( form );

		expect( errorText( form ) ).toBe( '' );
	} );

	it.each( [
		[ '+20 101 699 9700', '' ],
		[ '+201016999700', '' ],
		[ '+971-4-123-4567', '' ],
		[ '01016999700', 'Enter a phone number including its country code.' ],
		[ '+0123456789', 'Enter a phone number including its country code.' ],
		[ '+12345', 'Enter a phone number including its country code.' ],
		[ 'call me', 'Enter a phone number including its country code.' ],
	] )( 'validates the phone number %s', ( value, expected ) => {
		const form = makeFieldForm( 'phone', [ { rule: 'phone' } ], '<input name="phone" type="tel" />' );
		form.querySelector( 'input' ).value = value;
		window.Corex.forms.bind( form );

		submitForm( form );

		expect( errorText( form ) ).toBe( expected );
	} );
} );

describe( 'localized validation messages', () => {
	it( 'prefers the messages the server supplied over its own English table', () => {
		const messages = JSON.stringify( { required: 'هذا الحقل مطلوب.' } );
		const form = makeFieldForm( 'name', [ { rule: 'required' } ], '<input name="name" />', `data-corex-messages='${ messages }'` );
		window.Corex.forms.bind( form );

		// The built-in table goes through wp.i18n, which needs a JS translation file this site does
		// not ship — so without this seam an Arabic page showed English errors.
		submitForm( form );

		expect( errorText( form ) ).toBe( 'هذا الحقل مطلوب.' );
	} );

	it( 'falls back to its own message for a rule the server did not name', () => {
		const form = makeFieldForm( 'name', [ { rule: 'required' } ], '<input name="name" />', `data-corex-messages='{"email":"…"}'` );
		window.Corex.forms.bind( form );

		submitForm( form );

		expect( errorText( form ) ).toBe( 'This field is required.' );
	} );

	it( 'ignores a malformed messages attribute rather than breaking the form', () => {
		const form = makeFieldForm( 'name', [ { rule: 'required' } ], '<input name="name" />', 'data-corex-messages="not json"' );
		window.Corex.forms.bind( form );

		submitForm( form );

		expect( errorText( form ) ).toBe( 'This field is required.' );
	} );
} );

describe( 'Corex.loading', () => {
	it( 'dedupes a second start and toggles aria-busy + disabled', () => {
		const form = makeForm( EMAIL_REQUIRED );
		const submitEl = form.querySelector( '[type="submit"]' );

		const token = window.Corex.loading.start( form, submitEl );
		expect( form.getAttribute( 'aria-busy' ) ).toBe( 'true' );
		expect( submitEl.disabled ).toBe( true );
		expect( window.Corex.loading.start( form, submitEl ) ).toBeNull();

		window.Corex.loading.stop( token );
		expect( form.hasAttribute( 'aria-busy' ) ).toBe( false );
		expect( submitEl.disabled ).toBe( false );
	} );
} );

describe( 'Corex.api via wp.apiFetch (admin path)', () => {
	it( 'uses wp.apiFetch when present and normalises the response', async () => {
		const apiFetch = jest.fn( () =>
			Promise.resolve( { ok: true, status: 200, json: () => Promise.resolve( { ok: true, message: 'ok', data: { n: 1 } } ) } )
		);
		window.wp = { apiFetch, i18n: { __: ( s ) => s } };
		let runtime;
		jest.isolateModules( () => {
			require( RUNTIME );
		} );
		runtime = window.Corex;

		const result = await runtime.api.get( '/admin', { nonce: 'x' } );

		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
		expect( result.envelope.data ).toEqual( { n: 1 } );
		delete window.wp;
	} );

	/**
	 * With `parse: false` core does not resolve on a non-2xx — parseAndThrowError() rethrows
	 * the raw Response. Every test above this one mocked a *resolve*, which is why a real
	 * server error could reach the UI as "Something went wrong" for a whole release.
	 */
	const loadWithApiFetch = ( apiFetch ) => {
		window.wp = { apiFetch, i18n: { __: ( s ) => s } };
		jest.isolateModules( () => {
			require( RUNTIME );
		} );
		return window.Corex;
	};

	const rejectingApiFetch = ( body, status, { nonJson = false } = {} ) =>
		jest.fn( () =>
			Promise.reject( {
				ok: false,
				status,
				json: () =>
					nonJson
						? Promise.reject( new Error( 'not json' ) )
						: Promise.resolve( body ),
			} )
		);

	it( 'keeps the server message when apiFetch rejects with an error Response', async () => {
		const runtime = loadWithApiFetch(
			rejectingApiFetch(
				{
					ok: false,
					code: 'email_template_not_found',
					message: 'That email template was not found.',
					details: {},
				},
				404
			)
		);

		const result = await runtime.api.post( '/admin/templates/1/draft', {} );

		expect( result.ok ).toBe( false );
		expect( result.status ).toBe( 404 );
		expect( result.envelope.code ).toBe( 'email_template_not_found' );
		expect( result.envelope.message ).toBe(
			'That email template was not found.'
		);
		delete window.wp;
	} );

	it( 'keeps field details from a rejected validation response', async () => {
		const runtime = loadWithApiFetch(
			rejectingApiFetch(
				{
					ok: false,
					code: 'email_template_unsafe',
					message: 'The draft contains invalid or unsafe content.',
					details: { fields: { layout_id: 'Choose a layout.' } },
				},
				422
			)
		);

		const result = await runtime.api.post( '/admin/x', {} );

		expect( result.status ).toBe( 422 );
		expect( result.envelope.details.fields ).toEqual( {
			layout_id: 'Choose a layout.',
		} );
		delete window.wp;
	} );

	it( 'names the status when a rejected response carries no readable body', async () => {
		const runtime = loadWithApiFetch(
			rejectingApiFetch( null, 500, { nonJson: true } )
		);

		const result = await runtime.api.get( '/admin/x' );

		expect( result.status ).toBe( 500 );
		expect( result.envelope.message ).toContain( '500' );
		delete window.wp;
	} );

	it( 'still reports a transport failure as status 0', async () => {
		const runtime = loadWithApiFetch(
			jest.fn( () => Promise.reject( new Error( 'offline' ) ) )
		);

		const result = await runtime.api.get( '/admin/x' );

		expect( result.ok ).toBe( false );
		expect( result.status ).toBe( 0 );
		expect( result.envelope.code ).toBe( 'error' );
		delete window.wp;
	} );
} );
