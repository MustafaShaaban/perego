/**
 * Perego "Join us" / CV form — progressive upload lifecycle for the secure
 * perego/v1/careers/apply endpoint. The markup is server-rendered and usable without JS (a plain
 * multipart POST); this enhances it: client-side validation (required fields, CV type + 5 MB size),
 * an accessible aria-live status through every handoff state (uploading → submitting → success /
 * invalid / wrong_type / too_large / rate_limit / server_error), and submit locking so a slow upload
 * can't be double-sent.
 */

const ALLOWED_EXT = [ 'pdf', 'doc', 'docx' ];

const init = () => {
	document.querySelectorAll( '[data-perego-join]' ).forEach( ( form ) => {
		if ( form.dataset.peregoBound === '1' ) {
			return;
		}
		form.dataset.peregoBound = '1';

		const status = form.querySelector( '.join-form__status' );
		const submit = form.querySelector( '.join-form__submit' );
		const messages = parseMessages( form.dataset.messages );
		const maxBytes = parseInt( form.dataset.maxBytes || '5242880', 10 );

		const setStatus = ( key, tone ) => {
			if ( ! status ) {
				return;
			}
			status.textContent = messages[ key ] || '';
			status.dataset.tone = tone || '';
		};

		form.addEventListener( 'submit', async ( event ) => {
			event.preventDefault();
			if ( submit && submit.disabled ) {
				return;
			}

			const name = form.querySelector( '#jf-name' );
			const email = form.querySelector( '#jf-email' );
			const cv = form.querySelector( '#jf-cv' );
			const file = cv && cv.files && cv.files[ 0 ];

			// Client-side validation mirrors the server's contract for instant feedback.
			if ( ! name.value.trim() || ! isEmail( email.value ) || ! file ) {
				setStatus( 'invalid', 'error' );
				return;
			}
			const ext = file.name.split( '.' ).pop().toLowerCase();
			if ( ! ALLOWED_EXT.includes( ext ) ) {
				setStatus( 'wrong_type', 'error' );
				return;
			}
			if ( file.size > maxBytes ) {
				setStatus( 'too_large', 'error' );
				return;
			}

			lock( submit, true );
			setStatus( file.size > 512 * 1024 ? 'uploading' : 'submitting', 'busy' );

			try {
				const response = await fetch( form.dataset.endpoint, {
					method: 'POST',
					headers: { 'X-WP-Nonce': form.dataset.nonce || '' },
					body: new FormData( form ),
				} );
				let payload = {};
				try {
					payload = await response.json();
				} catch ( e ) {
					payload = {};
				}

				if ( response.ok && payload.ok ) {
					setStatus( 'success', 'success' );
					form.reset();
				} else {
					const key = messages[ payload.error ] ? payload.error : 'server_error';
					setStatus( key, 'error' );
				}
			} catch ( e ) {
				setStatus( 'server_error', 'error' );
			} finally {
				lock( submit, false );
			}
		} );
	} );
};

function lock( submit, busy ) {
	if ( ! submit ) {
		return;
	}
	submit.disabled = busy;
	submit.setAttribute( 'aria-busy', busy ? 'true' : 'false' );
}

function isEmail( value ) {
	return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( value.trim() );
}

function parseMessages( raw ) {
	try {
		return JSON.parse( raw || '{}' );
	} catch ( e ) {
		return {};
	}
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
