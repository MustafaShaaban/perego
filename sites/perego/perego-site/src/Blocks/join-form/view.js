/**
 * Perego "Join us" / CV form — progressive upload lifecycle for the secure
 * perego/v1/careers/apply endpoint. The markup is server-rendered and usable without JS (a plain
 * multipart POST); this enhances it: client-side validation (required fields, CV type + 5 MB size)
 * reported per field with a **distinct message per failure reason** — not one generic sentence
 * reused everywhere — mirroring the shared CoreX form runtime's `corex-form__error` + `aria-invalid`
 * contract. Each field also re-validates live (on blur once touched, on every keystroke/change after
 * that) so a fixed field's error clears immediately instead of waiting for the next submit attempt
 * (spec 020 round 4). Plus an accessible aria-live status through every handoff state
 * (uploading → submitting → success / errors / rate_limit / server_error), and submit locking so a
 * slow upload can't be double-sent.
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

		const fields = {
			name: { input: form.querySelector( '#jf-name' ), error: form.querySelector( '#jf-name-error' ) },
			email: { input: form.querySelector( '#jf-email' ), error: form.querySelector( '#jf-email-error' ) },
			cv: { input: form.querySelector( '#jf-cv' ), error: form.querySelector( '#jf-cv-error' ) },
		};
		const cvFilename = form.querySelector( '.file-drop__filename' );

		const setStatus = ( key, tone ) => {
			if ( ! status ) {
				return;
			}
			status.textContent = messages[ key ] || '';
			status.classList.remove( 'is-success', 'is-error' );
			if ( tone === 'success' || tone === 'error' ) {
				status.classList.add( 'is-' + tone );
			}
		};

		const setFieldError = ( field, message ) => {
			if ( field.error ) {
				field.error.textContent = message || '';
			}
			if ( field.input ) {
				field.input.setAttribute( 'aria-invalid', message ? 'true' : 'false' );
			}
		};

		const clearFieldErrors = () => {
			Object.values( fields ).forEach( ( field ) => setFieldError( field, '' ) );
		};

		// One validator per field, each returning its own specific message *key* (never a shared
		// generic one) so "Name is required" and "Please attach your CV" never collapse into the same
		// sentence. Each is reusable both on submit and for live re-validation as the user types/picks
		// a file — same rule, same message, one place.
		const validateName = () => ( fields.name.input.value.trim() ? '' : 'name_required' );
		const validateEmail = () => ( isEmail( fields.email.input.value ) ? '' : 'email_invalid' );
		const validateCv = ( file ) => {
			if ( ! file ) return 'cv_required';
			const ext = file.name.split( '.' ).pop().toLowerCase();
			if ( ! ALLOWED_EXT.includes( ext ) ) return 'wrong_type';
			if ( file.size > maxBytes ) return 'too_large';
			return '';
		};

		const validate = ( file ) => {
			const errorKeys = {};
			const nameError = validateName();
			const emailError = validateEmail();
			const cvError = validateCv( file );
			if ( nameError ) errorKeys.name = nameError;
			if ( emailError ) errorKeys.email = emailError;
			if ( cvError ) errorKeys.cv = cvError;
			return errorKeys;
		};

		// Live re-validation: once a field is touched, clear (or update) its own error as the user
		// fixes it — never wait for the next submit to notice the field is valid now.
		fields.name.input.addEventListener( 'blur', () => setFieldError( fields.name, messages[ validateName() ] ) );
		fields.name.input.addEventListener( 'input', () => {
			if ( fields.name.input.getAttribute( 'aria-invalid' ) === 'true' ) {
				setFieldError( fields.name, messages[ validateName() ] );
			}
		} );
		fields.email.input.addEventListener( 'blur', () => setFieldError( fields.email, messages[ validateEmail() ] ) );
		fields.email.input.addEventListener( 'input', () => {
			if ( fields.email.input.getAttribute( 'aria-invalid' ) === 'true' ) {
				setFieldError( fields.email, messages[ validateEmail() ] );
			}
		} );
		const cvDrop = fields.cv.input.closest( '.file-drop' );
		fields.cv.input.addEventListener( 'change', () => {
			const file = fields.cv.input.files && fields.cv.input.files[ 0 ];
			setFieldError( fields.cv, messages[ validateCv( file ) ] );
			if ( cvFilename ) {
				cvFilename.textContent = file ? file.name : '';
			}
			if ( cvDrop ) {
				cvDrop.classList.toggle( 'has-file', !! file );
			}
		} );

		form.addEventListener( 'submit', async ( event ) => {
			event.preventDefault();
			if ( submit && submit.disabled ) {
				return;
			}

			const cv = fields.cv.input;
			const file = cv && cv.files && cv.files[ 0 ];
			const errorKeys = validate( file );
			const firstErroredKey = Object.keys( errorKeys )[ 0 ];

			clearFieldErrors();
			Object.keys( errorKeys ).forEach( ( key ) => setFieldError( fields[ key ], messages[ errorKeys[ key ] ] ) );

			if ( firstErroredKey ) {
				setStatus( 'form_has_errors', 'error' );
				fields[ firstErroredKey ].input.focus();
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
					if ( cvFilename ) {
						cvFilename.textContent = '';
					}
					if ( cvDrop ) {
						cvDrop.classList.remove( 'has-file' );
					}
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

// `disabled` alone is not feedback: a CV upload can run for seconds, and a merely greyed-out button
// reads as "broken", not "working" (client report 2026-07-27). `is-loading` is the same class the
// reference stylesheet already spins on `.footer-form__submit`, which this button also carries — so
// the visual busy state is the one the rest of the site uses, not a second one invented here.
function lock( submit, busy ) {
	if ( ! submit ) {
		return;
	}
	submit.disabled = busy;
	submit.classList.toggle( 'is-loading', busy );
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
