/**
 * Footer quick-message counter (spec 020 D2) — mirrors the handoff's live "n / 1200" character
 * counter (site/js/main.js CAPS + field-counter) on the CoreX-rendered footer form, reusing the
 * contact page's `data-perego-counter` attribute pattern so the CSS projection is shared. The limit
 * is read from the textarea's own maxlength so markup and counter can never disagree.
 */
function bindMessageCounter( form ) {
	const textarea = form.querySelector( 'textarea[name="message"]' );
	const field = textarea?.closest( '[data-corex-field="message"]' );
	const limit = Number( textarea?.getAttribute( 'maxlength' ) );

	if ( ! textarea || ! field || ! limit ) return;

	const update = () => field.setAttribute( 'data-perego-counter', `${ textarea.value.length } / ${ limit }` );
	textarea.addEventListener( 'input', update );

	// The shared CoreX runtime calls form.reset() on a successful submission; the reset event fires
	// before values revert, so resync on the next tick.
	form.addEventListener( 'reset', () => setTimeout( update, 0 ) );

	update();
}

function init() {
	document.querySelectorAll( 'form[data-corex-form="perego-quick-message"]' ).forEach( bindMessageCounter );
}

if ( document.readyState === 'loading' ) document.addEventListener( 'DOMContentLoaded', init );
else init();
