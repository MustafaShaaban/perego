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

function init() {
	document.querySelectorAll( '[data-perego-service-chooser]' ).forEach( bindChooser );
}

if ( document.readyState === 'loading' ) document.addEventListener( 'DOMContentLoaded', init );
else init();
