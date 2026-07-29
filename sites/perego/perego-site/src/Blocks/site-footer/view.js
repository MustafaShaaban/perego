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

	const update = () => {
		const words = countWords( textarea.value );
		field.setAttribute( 'data-perego-counter', `${ words } / ${ limit } words` );
		field.classList.toggle( 'is-limit', words > limit );
	};

	textarea.addEventListener( 'input', update );

	// The shared CoreX runtime calls form.reset() on a successful submission; the reset event fires
	// before values revert, so resync on the next tick.
	form.addEventListener( 'reset', () => setTimeout( update, 0 ) );

	/*
	 * Stop an over-limit submission here, because since CoreX v0.40.0 nothing else will.
	 *
	 * The framework runtime dropped its `max_words` client rule — its own comment says there is no
	 * server rule either, which is true of CoreX and false of Perego: `Forms\Rules\MaxWords` is
	 * registered in `PeregoSiteServiceProvider`. So the form used to submit, the server would answer
	 * 422, and the runtime — finding no `max_words` entry in its message map — would fall back to the
	 * generic "Please check this field.", after a round trip, for a limit this counter has been
	 * displaying all along.
	 *
	 * Capture phase, so this runs before the runtime's own submit handler and its network call.
	 * Deliberately NOT a re-fork of `corex-runtime.js`: that file carried most of this update's
	 * conflicts, and a fork edit on it guarantees the same fight at the next version.
	 */
	form.addEventListener( 'submit', ( event ) => {
		if ( countWords( textarea.value ) <= limit ) return;

		event.preventDefault();
		event.stopImmediatePropagation();
		update();
		textarea.focus();
	}, true );

	update();
}

function init() {
	document.querySelectorAll( 'form[data-corex-form="perego-quick-message"]' ).forEach( bindMessageCounter );
}

if ( document.readyState === 'loading' ) document.addEventListener( 'DOMContentLoaded', init );
else init();
