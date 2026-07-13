function bindChooser( chooser ) {
	const form = chooser.closest( '.contact-hero' )?.querySelector( 'form[data-corex-form="perego-project-brief"]' );
	const select = form?.querySelector( 'select[name="services[]"]' );

	if ( ! select ) return;

	chooser.querySelectorAll( '.svc-choice' ).forEach( ( button ) => {
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
}

function bindMessageCounter( form ) {
	const textarea = form.querySelector( 'textarea[name="message"]' );
	const field = textarea?.closest( '[data-corex-field="message"]' );

	if ( ! textarea || ! field ) return;

	const update = () => field.setAttribute( 'data-perego-counter', `${ textarea.value.length } / 1200` );
	textarea.addEventListener( 'input', update );
	update();
}

function init() {
	document.querySelectorAll( '[data-perego-service-chooser]' ).forEach( bindChooser );
	document.querySelectorAll( 'form[data-corex-form="perego-project-brief"]' ).forEach( bindMessageCounter );
}

if ( document.readyState === 'loading' ) document.addEventListener( 'DOMContentLoaded', init );
else init();
