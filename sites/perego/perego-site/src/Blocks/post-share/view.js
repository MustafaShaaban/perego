/**
 * Copy-link behaviour for the share row. Everything else in the row is a plain anchor and needs no
 * script — this file exists only for the one control that cannot work without it.
 *
 * The button is rendered unconditionally and simply does nothing without JS, rather than being hidden:
 * hiding it would need the server to know whether script will run, which it cannot.
 */
const COPIED_MS = 2000;

function flash( button ) {
	const badge = button.querySelector( '.post-share__copied' );
	if ( ! badge ) return;

	badge.hidden = false;
	button.classList.add( 'is-copied' );

	window.setTimeout( () => {
		badge.hidden = true;
		button.classList.remove( 'is-copied' );
	}, COPIED_MS );
}

async function copy( url ) {
	// navigator.clipboard needs a secure context; fall back so http:// dev hosts still work.
	if ( navigator.clipboard && window.isSecureContext ) {
		await navigator.clipboard.writeText( url );
		return;
	}

	const field = document.createElement( 'textarea' );
	field.value = url;
	field.setAttribute( 'readonly', '' );
	field.style.position = 'fixed';
	field.style.opacity = '0';
	document.body.appendChild( field );
	field.select();
	document.execCommand( 'copy' );
	field.remove();
}

function init() {
	document.querySelectorAll( '[data-perego-copy]' ).forEach( ( button ) => {
		button.addEventListener( 'click', async () => {
			try {
				await copy( button.getAttribute( 'data-perego-copy' ) );
				flash( button );
			} catch {
				// A blocked clipboard is not worth breaking the page over; the URL is in the address bar.
			}
		} );
	} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
