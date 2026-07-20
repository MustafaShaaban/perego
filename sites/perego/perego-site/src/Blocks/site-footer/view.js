/**
 * Footer quick-message counter (spec 020 D2, word-count per round 4) — mirrors the handoff's live
 * counter (site/js/main.js CAPS + field-counter) on the CoreX-rendered footer form, reusing the
 * contact page's `data-perego-counter` attribute pattern so the CSS projection is shared. Counts
 * words, not characters (`Corex\Forms\Validation\Rules\MaxWords` is the matching server rule) — a
 * word budget reads more meaningfully to someone writing a message than a raw character count. The
 * limit is read from the textarea's `data-max-words` attribute (not `maxlength`, which is inherently
 * character-based) so markup and counter can never disagree.
 */
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
