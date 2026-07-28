function loadChooser() {
	jest.resetModules();
	require( './view.js' );
}

// jsdom has no layout, so it does not implement this; the chooser calls it when it takes focus.
Element.prototype.scrollIntoView = jest.fn();

// MutationObserver callbacks run on a microtask, so a test that changes an attribute has to yield
// before asserting on what the observer did.
const settle = () => Promise.resolve();

beforeEach( () => {
	document.body.innerHTML = `
		<section class="contact-hero">
			<form data-corex-form="perego-project-brief"><div data-corex-field="name">
				<input name="name" />
			</div><div data-corex-field="services">
				<span class="corex-form__error"></span>
				<select name="services[]" multiple>
					<option value="video-editing"></option><option value="graphic-design"></option>
				</select>
			</div><div data-corex-field="message"><textarea name="message" data-max-words="200"></textarea></div></form>
			<div data-perego-service-chooser data-error-required="Please choose at least one service.">
				<div class="svc-choice-list" role="group">
					<button class="svc-choice" data-service="video-editing" aria-pressed="false"></button>
					<button class="svc-choice" data-service="graphic-design" aria-pressed="false"></button>
				</div>
				<p class="svc-choice-error" id="perego-services-error" role="alert"></p>
			</div>
		</section>`;
} );

const COUNTRIES = [
	{ iso: 'AE', dial: '+971', label: 'United Arab Emirates (+971)', short: 'AE +971', example: '+971 50 123 4567' },
	{ iso: 'SA', dial: '+966', label: 'Saudi Arabia (+966)', short: 'SA +966', example: '+966 50 123 4567' },
	{ iso: 'EG', dial: '+20', label: 'Egypt (+20)', short: 'EG +20', example: '+20 100 123 4567' },
	{ iso: 'US', dial: '+1', label: 'United States (+1)', short: 'US +1', example: '' },
];
const ZONES = { 'Asia/Dubai': 'AE', 'Asia/Riyadh': 'SA', 'Africa/Cairo': 'EG', 'America/New_York': 'US' };
const GENERIC = 'Best number to reach you';

/** Loads the chooser with a phone field present, under a chosen timezone and language list. */
function loadPhone( { timeZone = 'Asia/Dubai', languages = [ 'en' ] } = {} ) {
	jest.spyOn( Intl, 'DateTimeFormat' ).mockReturnValue( { resolvedOptions: () => ( { timeZone } ) } );
	Object.defineProperty( navigator, 'languages', { configurable: true, value: languages } );

	const chooser = document.querySelector( '[data-perego-service-chooser]' );
	chooser.dataset.countries = JSON.stringify( COUNTRIES );
	chooser.dataset.countryZones = JSON.stringify( ZONES );
	chooser.dataset.defaultCountry = 'AE';

	const form = document.querySelector( 'form' );
	form.insertAdjacentHTML(
		'beforeend',
		`<div data-corex-field="phone"><input name="phone" type="tel" placeholder="${ GENERIC }" /></div>`
	);
	loadChooser();

	return document.querySelector( 'input[name="phone"]' );
}

const pickedIso = () => document.querySelector( '#perego-phone-country' ).selectedOptions[ 0 ].dataset.iso;

afterEach( () => jest.restoreAllMocks() );

test.each( [
	[ 'Asia/Dubai', 'AE' ],
	[ 'Asia/Riyadh', 'SA' ],
	[ 'Africa/Cairo', 'EG' ],
] )( 'preselects the visitor’s own country from the %s timezone', ( timeZone, iso ) => {
	loadPhone( { timeZone } );

	expect( pickedIso() ).toBe( iso );
} );

test( 'falls back to the language region when the timezone is not one we map', () => {
	loadPhone( { timeZone: 'Antarctica/Troll', languages: [ 'ar-SA', 'en' ] } );

	expect( pickedIso() ).toBe( 'SA' );
} );

test( 'falls back to the configured default when neither signal names a country we offer', () => {
	// A bare `ar` is a language, not a location, and Troll is not on the map.
	loadPhone( { timeZone: 'Antarctica/Troll', languages: [ 'ar' ] } );

	expect( pickedIso() ).toBe( 'AE' );
} );

test( 'still renders a working picker when Intl refuses to answer', () => {
	jest.spyOn( Intl, 'DateTimeFormat' ).mockImplementation( () => {
		throw new Error( 'no Intl here' );
	} );
	Object.defineProperty( navigator, 'languages', { configurable: true, value: [ 'en' ] } );
	const chooser = document.querySelector( '[data-perego-service-chooser]' );
	chooser.dataset.countries = JSON.stringify( COUNTRIES );
	chooser.dataset.countryZones = JSON.stringify( ZONES );
	chooser.dataset.defaultCountry = 'AE';
	document.querySelector( 'form' ).insertAdjacentHTML(
		'beforeend',
		`<div data-corex-field="phone"><input name="phone" type="tel" placeholder="${ GENERIC }" /></div>`
	);
	loadChooser();

	expect( pickedIso() ).toBe( 'AE' );
} );

test( 'shows the selected country’s own number format, and the generic copy where there is none', () => {
	const input = loadPhone( { timeZone: 'Asia/Dubai' } );
	expect( input.placeholder ).toBe( '+971 50 123 4567' );

	const select = document.querySelector( '#perego-phone-country' );
	select.value = '+966';
	select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	expect( input.placeholder ).toBe( '+966 50 123 4567' );

	// US publishes no example here, so the field restores its translated sentence rather than
	// emptying the placeholder.
	select.value = '+1';
	select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	expect( input.placeholder ).toBe( GENERIC );
} );

test( 'lets a typed dial code override the detected country', () => {
	const input = loadPhone( { timeZone: 'Asia/Dubai' } );
	expect( pickedIso() ).toBe( 'AE' );

	input.value = '+20 100 169 9970';
	input.dispatchEvent( new Event( 'input', { bubbles: true } ) );

	expect( pickedIso() ).toBe( 'EG' );
	expect( input.placeholder ).toBe( '+20 100 123 4567' );
} );

test( 'keeps a live "n / 200 words" counter in sync with the live CoreX textarea', () => {
	loadChooser();
	const textarea = document.querySelector( 'textarea[name="message"]' );
	const field = document.querySelector( '[data-corex-field="message"]' );

	expect( field.getAttribute( 'data-perego-counter' ) ).toBe( '0 / 200 words' );

	textarea.value = 'Hello there friend';
	textarea.dispatchEvent( new Event( 'input', { bubbles: true } ) );

	expect( field.getAttribute( 'data-perego-counter' ) ).toBe( '3 / 200 words' );
} );

test( 'counts words, not characters, in the project-brief message counter', () => {
	loadChooser();
	const textarea = document.querySelector( 'textarea[name="message"]' );
	const field = document.querySelector( '[data-corex-field="message"]' );

	textarea.value = 'supercalifragilisticexpialidocious';
	textarea.dispatchEvent( new Event( 'input', { bubbles: true } ) );

	expect( field.getAttribute( 'data-perego-counter' ) ).toBe( '1 / 200 words' );
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

test( 'surfaces the hidden CoreX validation error on the visible chooser when no service is picked', async () => {
	loadChooser();
	const select = document.querySelector( 'select[name="services[]"]' );
	const group = document.querySelector( '.svc-choice-list' );
	const error = document.querySelector( '.svc-choice-error' );

	// What the runtime does on a failed submit: a message in the (hidden) field wrapper, and
	// aria-invalid on the control.
	document.querySelector( '.corex-form__error' ).textContent = 'This field is required.';
	select.setAttribute( 'aria-invalid', 'true' );
	await settle();

	expect( error.textContent ).toBe( 'Please choose at least one service.' );
	expect( group.classList.contains( 'is-invalid' ) ).toBe( true );
	expect( group.getAttribute( 'aria-invalid' ) ).toBe( 'true' );
	expect( document.activeElement ).toBe( document.querySelector( '[data-service="video-editing"]' ) );
} );

test( 'falls back to the framework message when the block carries no field-specific wording', async () => {
	document.querySelector( '[data-perego-service-chooser]' ).removeAttribute( 'data-error-required' );
	loadChooser();
	document.querySelector( '.corex-form__error' ).textContent = 'This field is required.';
	document.querySelector( 'select[name="services[]"]' ).setAttribute( 'aria-invalid', 'true' );
	await settle();

	expect( document.querySelector( '.svc-choice-error' ).textContent ).toBe( 'This field is required.' );
} );

test( 'leaves focus alone when an earlier field is also invalid', async () => {
	loadChooser();
	document.querySelector( 'input[name="name"]' ).setAttribute( 'aria-invalid', 'true' );
	document.querySelector( 'select[name="services[]"]' ).setAttribute( 'aria-invalid', 'true' );
	await settle();

	expect( document.querySelector( '.svc-choice-error' ).textContent ).toBe( 'Please choose at least one service.' );
	expect( document.activeElement ).toBe( document.body );
} );

test( 'clears the chooser error as soon as a service is chosen', async () => {
	loadChooser();
	const error = document.querySelector( '.svc-choice-error' );
	document.querySelector( 'select[name="services[]"]' ).setAttribute( 'aria-invalid', 'true' );
	await settle();
	expect( error.textContent ).not.toBe( '' );

	document.querySelector( '[data-service="graphic-design"]' ).click();
	await settle();

	const group = document.querySelector( '.svc-choice-list' );
	expect( error.textContent ).toBe( '' );
	expect( group.classList.contains( 'is-invalid' ) ).toBe( false );
	expect( group.hasAttribute( 'aria-invalid' ) ).toBe( false );
} );

test( 'resyncs every choice button when the shared CoreX runtime resets the form after a successful submission', () => {
	jest.useFakeTimers();
	loadChooser();
	const form = document.querySelector( 'form' );
	const graphic = document.querySelector( '[data-service="graphic-design"]' );

	graphic.click();
	expect( graphic.classList.contains( 'is-selected' ) ).toBe( true );

	form.reset();
	jest.runAllTimers();

	expect( graphic.classList.contains( 'is-selected' ) ).toBe( false );
	expect( graphic.getAttribute( 'aria-pressed' ) ).toBe( 'false' );
	jest.useRealTimers();
} );
