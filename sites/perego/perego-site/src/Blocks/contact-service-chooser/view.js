/**
 * Mirror the framework's validation error onto the buttons the visitor actually clicks.
 *
 * The `services` control is a `<select multiple>` that this page hides — the chooser buttons are
 * its visible replacement, and they live in a different DOM subtree from the form. So the runtime
 * wrote a correct "required" error into a `display:none` span, its `focus()` on the hidden select
 * was a no-op, and submitting with nothing picked looked like nothing happened at all.
 *
 * Watching `aria-invalid` rather than hooking `submit` is deliberate: the runtime sets that
 * attribute on both its client-side and its server-side error paths, so one observer covers both
 * and no validation logic is duplicated here.
 */
function bindChooserErrors( chooser, form, select, buttons ) {
	const target = chooser.querySelector( '.svc-choice-error' );
	const group = chooser.querySelector( '.svc-choice-list' );

	if ( ! target || ! group ) return;

	const clear = () => {
		target.textContent = '';
		group.classList.remove( 'is-invalid' );
		group.removeAttribute( 'aria-invalid' );
	};

	const show = () => {
		// The framework's own span holds the translated message for whichever rule failed. Our
		// wording replaces it only for `required` — recognised by there being nothing selected —
		// because the generic "This field is required." names no field and this control has no
		// visible label. Any other rule keeps the framework's message rather than a wrong one.
		const generic = form.querySelector( '[data-corex-field="services"] .corex-form__error' )?.textContent;
		const missing = select.selectedOptions.length === 0;
		target.textContent = ( missing && chooser.dataset.errorRequired ) || generic || '';
		group.classList.add( 'is-invalid' );
		group.setAttribute( 'aria-invalid', 'true' );

		// Only take focus when this is the first thing wrong with the form — otherwise the visitor
		// would be thrown past the empty name and email fields they also need to fix.
		if ( form.querySelector( '[aria-invalid="true"]' ) !== select ) return;

		group.scrollIntoView( { block: 'center', behavior: 'smooth' } );
		buttons[ 0 ]?.focus();
	};

	new MutationObserver( () => {
		if ( select.getAttribute( 'aria-invalid' ) === 'true' ) show();
		else clear();
	} ).observe( select, { attributes: true, attributeFilter: [ 'aria-invalid' ] } );

	// The runtime clears `aria-invalid` only on the next submit, so picking a service would leave
	// the message standing over a chooser that no longer has anything wrong with it.
	buttons.forEach( ( button ) => button.addEventListener( 'click', clear ) );
}

function bindChooser( chooser ) {
	const form = chooser.closest( '.contact-hero' )?.querySelector( 'form[data-corex-form="perego-project-brief"]' );
	const select = form?.querySelector( 'select[name="services[]"]' );

	if ( ! select ) return;

	const buttons = chooser.querySelectorAll( '.svc-choice' );

	bindChooserErrors( chooser, form, select, buttons );

	buttons.forEach( ( button ) => {
		button.addEventListener( 'click', () => {
			const option = Array.from( select.options ).find( ( item ) => item.value === button.dataset.service );
			if ( ! option ) return;

			option.selected = ! option.selected;
			button.classList.toggle( 'is-selected', option.selected );
			button.setAttribute( 'aria-pressed', String( option.selected ) );
			select.dispatchEvent( new Event( 'input', { bubbles: true } ) );
			select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		} );
	} );

	// The shared CoreX runtime calls form.reset() on a successful submission (spec 043), which
	// reverts the underlying <select> without touching these decorative buttons — resync them so
	// the chooser doesn't keep showing a service as picked after the message it belonged to sent.
	// The form's reset algorithm fires this event *before* it reverts each control's value, so read
	// the select on the next tick rather than during the event itself.
	form.addEventListener( 'reset', () => {
		setTimeout( () => {
			buttons.forEach( ( button ) => {
				const option = Array.from( select.options ).find( ( item ) => item.value === button.dataset.service );
				const selected = !! option?.selected;
				button.classList.toggle( 'is-selected', selected );
				button.setAttribute( 'aria-pressed', String( selected ) );
			} );
		}, 0 );
	} );
}

// Counts words, not characters (spec 020 round 4) — matches the server-side
// Corex\Forms\Validation\Rules\MaxWords rule for this field.
function countWords( value ) {
	return value.trim().split( /\s+/ ).filter( ( word ) => word.length > 0 ).length;
}

function bindMessageCounter( form, wordsLabel ) {
	const textarea = form.querySelector( 'textarea[name="message"]' );
	const field = textarea?.closest( '[data-corex-field="message"]' );
	const limit = Number( textarea?.getAttribute( 'data-max-words' ) );

	if ( ! textarea || ! field || ! limit ) return;

	const update = () => {
		const used = countWords( textarea.value );
		field.setAttribute( 'data-perego-counter', `${ used } / ${ limit } ${ wordsLabel }` );
		// The reference stylesheet has always defined this red state; nothing set it, so the
		// counter looked identical whether you were within the limit or past it.
		field.classList.toggle( 'is-limit', used > limit );
	};
	textarea.addEventListener( 'input', update );
	update();
}

/**
 * A country-code picker in front of the phone input.
 *
 * The field was a bare `<input type="tel">` capped at 24 characters, so "01016999700" was accepted
 * and could not be dialled from outside Egypt (client report 2026-07-27). No library: the
 * constitution forbids loading a global JS one, and a `<select>` styled by the site's own rules is
 * both lighter and RTL-correct for free.
 *
 * The input stays the single named field. The picker never carries a `name`, so the runtime's
 * `collect()` is untouched and the submitted value is one E.164 string — which is also what the
 * server's `phone` rule validates and what the team ends up dialling.
 */
/**
 * The visitor's own country, or '' when nothing reliable says.
 *
 * Timezone first: it is the one country signal a browser gives away for free — no network call, no
 * geolocation permission, no third-party lookup — and it is already right on any device that set
 * its own clock. Language is the fallback rather than the primary because it so often isn't a
 * location at all: someone in Dubai on an English laptop reports `en-US`.
 *
 * Everything is wrapped, and an unknown answer returns '' rather than a guess: a visitor the map
 * does not cover should get the configured default and change it, which costs one click. Detection
 * runs once at init, before any interaction, so it can never overwrite a deliberate choice.
 */
function detectCountry( countries, zones ) {
	const known = ( iso ) => ( iso && countries.some( ( country ) => country.iso === iso ) ? iso : '' );

	try {
		const zone = Intl.DateTimeFormat().resolvedOptions().timeZone;
		const fromZone = known( zones[ zone ] );
		if ( fromZone ) {
			return fromZone;
		}
	} catch ( e ) {
		// Intl is unavailable or refused; the language hint below is still worth trying.
	}

	try {
		const tags = navigator.languages?.length ? navigator.languages : [ navigator.language ];
		for ( const tag of tags ) {
			// The region subtag of e.g. `ar-AE`. A bare `ar` names no country and is skipped.
			const region = known( String( tag ).split( '-' )[ 1 ]?.toUpperCase() );
			if ( region ) {
				return region;
			}
		}
	} catch ( e ) {
		// Fall through to the caller's default.
	}

	return '';
}

function bindPhoneCountry( form, countries, { zones, defaultIso, label } ) {
	const input = form.querySelector( 'input[name="phone"]' );
	if ( ! input || ! countries.length || input.dataset.peregoPhoneBound === '1' ) return;
	input.dataset.peregoPhoneBound = '1';

	const wrap = document.createElement( 'div' );
	wrap.className = 'phone-field';

	const select = document.createElement( 'select' );
	// `corex-form__input` is not decoration — it is the selector the theme's own select enhancer
	// scans for (assets/src/js/select.js). Without it this stayed a raw OS dropdown while the budget
	// select beside it got the styled listbox, which is exactly what the client reported. The
	// enhancer runs on DOMContentLoaded, after this module, so the element is already here.
	//
	// One class only: the enhancer clips this element to 1px and takes over the visuals, so a second
	// styling hook on it would be a hook nothing can use. The layout rules target `.phone-field
	// .perego-select`, the wrapper the enhancer builds.
	select.className = 'corex-form__input';
	select.id = 'perego-phone-country';

	// The enhancer names its trigger from `label[for]` and does NOT copy `aria-label`, so a bare
	// aria-label here would leave the visible control unnamed. A real, visually-hidden label is what
	// that path expects.
	const legend = document.createElement( 'label' );
	legend.className = 'screen-reader-text';
	legend.htmlFor = select.id;
	legend.textContent = label;

	countries.forEach( ( country ) => {
		const option = document.createElement( 'option' );
		option.value = country.dial;
		option.dataset.iso = country.iso;
		// Full name in the open list, which has room; short code in the closed trigger, which does not.
		option.dataset.triggerLabel = country.short || country.dial;
		option.textContent = country.label;
		select.appendChild( option );
	} );

	input.replaceWith( wrap );
	wrap.append( legend, select, input );

	// Longest dial code wins: +20 is a prefix of nothing here, but +1 is a prefix of +1… and a
	// shortest-first scan would claim every North American number for the first +1 in the list.
	const dialFor = ( value ) => {
		const digits = value.replace( /[\s()\-.]/g, '' );
		if ( ! digits.startsWith( '+' ) ) return '';
		return countries
			.map( ( country ) => country.dial )
			.filter( ( dial ) => digits.startsWith( dial ) )
			.sort( ( a, b ) => b.length - a.length )[ 0 ] || '';
	};

	// `select.value = x` fires nothing, so the styled trigger would keep showing the old country
	// after the visitor typed a number carrying a different code. Dispatching `change` is what the
	// enhancer listens for; `echoing` stops that synthetic event re-entering the handler below and
	// rewriting the very input the visitor is typing into.
	let echoing = false;
	const syncSelectFromInput = () => {
		const dial = dialFor( input.value );
		if ( ! dial || select.value === dial ) {
			return;
		}
		echoing = true;
		select.value = dial;
		select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		echoing = false;
	};

	// The placeholder follows the country, because a format is only a hint if it is the right
	// format — a Saudi visitor shown a UAE-shaped number learns nothing. The server-rendered copy
	// is kept as the fallback for the countries with no published example here, so an unmapped
	// selection restores the translated sentence rather than emptying the field.
	const genericPlaceholder = input.placeholder;
	const syncPlaceholder = () => {
		const iso = select.selectedOptions[ 0 ]?.dataset.iso;
		const country = countries.find( ( item ) => item.iso === iso );
		input.placeholder = country?.example || genericPlaceholder;
	};

	// Select by option, not by `select.value = dial`: dial codes are not unique (+1 is the US and
	// Canada both), so assigning the value would silently land on whichever comes first.
	const selectIso = ( iso ) => {
		const option = Array.from( select.options ).find( ( item ) => item.dataset.iso === iso );
		if ( option ) {
			option.selected = true;
		}
	};

	selectIso( detectCountry( countries, zones ) || defaultIso );
	syncPlaceholder();
	syncSelectFromInput();

	select.addEventListener( 'change', () => {
		syncPlaceholder();
		if ( echoing ) {
			return;
		}
		const current = dialFor( input.value );
		const national = current ? input.value.trim().slice( current.length ) : input.value.trim();
		// An untouched field stays empty: pre-filling a dial code into an optional input makes it
		// look answered, and the visitor then submits a country code with no number after it.
		input.value = national === '' ? '' : select.value + national;
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		input.focus();
	} );

	input.addEventListener( 'input', syncSelectFromInput );

	// Typing a local number is the common case; on leaving the field it becomes dialable rather
	// than failing validation for something the picker already knows.
	input.addEventListener( 'blur', () => {
		const value = input.value.trim();
		if ( value === '' || value.startsWith( '+' ) ) return;
		input.value = select.value + value.replace( /^0+/, '' );
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
	} );
}

function parseJson( value, fallback ) {
	try {
		return JSON.parse( value ) || fallback;
	} catch ( e ) {
		return fallback;
	}
}

function init() {
	const chooser = document.querySelector( '[data-perego-service-chooser]' );
	const countries = parseJson( chooser?.dataset.countries, [] );
	const zones = parseJson( chooser?.dataset.countryZones, {} );
	const wordsLabel = chooser?.dataset.wordsLabel || 'words';

	document.querySelectorAll( '[data-perego-service-chooser]' ).forEach( bindChooser );
	document.querySelectorAll( 'form[data-corex-form="perego-project-brief"]' ).forEach( ( form ) => {
		bindMessageCounter( form, wordsLabel );
		bindPhoneCountry( form, countries, {
			zones,
			defaultIso: chooser?.dataset.defaultCountry || 'AE',
			label: chooser?.dataset.phoneLabel || 'Country code',
		} );
	} );
}

if ( document.readyState === 'loading' ) document.addEventListener( 'DOMContentLoaded', init );
else init();
