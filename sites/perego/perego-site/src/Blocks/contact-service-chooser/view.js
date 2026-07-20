function bindChooser( chooser ) {
	const form = chooser.closest( '.contact-hero' )?.querySelector( 'form[data-corex-form="perego-project-brief"]' );
	const select = form?.querySelector( 'select[name="services[]"]' );

	if ( ! select ) return;

	const buttons = chooser.querySelectorAll( '.svc-choice' );

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

function bindMessageCounter( form ) {
	const textarea = form.querySelector( 'textarea[name="message"]' );
	const field = textarea?.closest( '[data-corex-field="message"]' );
	const limit = Number( textarea?.getAttribute( 'data-max-words' ) );

	if ( ! textarea || ! field || ! limit ) return;

	const update = () => field.setAttribute( 'data-perego-counter', `${ countWords( textarea.value ) } / ${ limit } words` );
	textarea.addEventListener( 'input', update );
	update();
}

function init() {
	document.querySelectorAll( '[data-perego-service-chooser]' ).forEach( bindChooser );
	document.querySelectorAll( 'form[data-corex-form="perego-project-brief"]' ).forEach( bindMessageCounter );
}

if ( document.readyState === 'loading' ) document.addEventListener( 'DOMContentLoaded', init );
else init();
