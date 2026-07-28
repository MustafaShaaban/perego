/**
 * Corex client runtime (spec 043) — one buildless, jQuery-free `window.Corex` that
 * every Corex form and admin screen speaks through. No build step is required to use
 * it: it reads the WordPress globals (`wp.apiFetch`, `wp.i18n`) when present and
 * degrades to `fetch` + an identity translator otherwise (Principle IX).
 *
 *   Corex.api.{get,post,delete}  — nonce-attaching request → normalised envelope Result
 *   Corex.forms.bind(form)       — schema-mirrored validate → submit → render server errors
 *   Corex.loading                — disable/spinner/aria-busy/dedupe/restore
 *   Corex.notices                — accessible global status
 *
 * Events on `document`: corex:request:start, corex:request:end.
 * Events on the form:    corex:form:success, corex:form:error.
 */
( function ( window, document ) {
	'use strict';

	var wp = window.wp || {};

	/** Translate via wp.i18n when present; identity fallback keeps it buildless. */
	function t( text ) {
		return wp && wp.i18n && typeof wp.i18n.__ === 'function'
			? wp.i18n.__( text, 'corex' )
			: text;
	}

	function emit( target, name, detail ) {
		target.dispatchEvent( new CustomEvent( name, { detail: detail, bubbles: true } ) );
	}

	/* ----------------------------------------------------------------------- *
	 * Envelope normalisation — every response is coerced to { ok, ... }.
	 * ----------------------------------------------------------------------- */

	function isEnvelope( body ) {
		return body !== null && typeof body === 'object' && typeof body.ok === 'boolean';
	}

	function genericError( message ) {
		return { ok: false, code: 'error', message: message || t( 'Something went wrong. Please try again.' ), details: {} };
	}

	/**
	 * Describe a failure that carried no message of its own — a blank 5xx, an HTML error
	 * page, a proxy timeout. Naming the status is the difference between "the server broke"
	 * and "the network broke", which are not the same problem to chase.
	 */
	function statusMessage( status ) {
		if ( ! status ) {
			return ''; // No response at all — the caller's generic default is the honest one.
		}
		/* translators: %d: HTTP status code of the failed response. */
		return t( 'The server returned an unexpected response (%d).' ).replace(
			'%d',
			String( status )
		);
	}

	function normalise( body, httpOk, status ) {
		if ( isEnvelope( body ) ) {
			return body;
		}
		if ( httpOk ) {
			return { ok: true, message: '', data: body && typeof body === 'object' ? body : {} };
		}
		return genericError( ( body && body.message ) || statusMessage( status ) );
	}

	/* ----------------------------------------------------------------------- *
	 * Corex.api — always resolves to { ok, status, envelope }; never throws.
	 * ----------------------------------------------------------------------- */

	var DEFAULT_TIMEOUT = 15000;

	function nonceFor( opts ) {
		if ( opts && opts.nonce ) {
			return opts.nonce;
		}
		return ( window.corexRuntime && window.corexRuntime.nonce ) || '';
	}

	/** A Response is only useful to us if we can read a status and a body off it. */
	function isResponse( value ) {
		return (
			value !== null &&
			typeof value === 'object' &&
			typeof value.status === 'number' &&
			typeof value.json === 'function'
		);
	}

	function fromResponse( response ) {
		return response
			.json()
			.catch( function () {
				return null; // non-JSON / HTML body → described by status, never a parse throw
			} )
			.then( function ( body ) {
				return {
					ok: response.ok,
					status: response.status,
					envelope: normalise( body, response.ok, response.status ),
				};
			} );
	}

	function viaApiFetch( url, method, data, opts ) {
		var nonce = nonceFor( opts );
		return wp
			.apiFetch( {
				url: url,
				method: method,
				data: data,
				parse: false,
				headers: nonce ? { 'X-WP-Nonce': nonce } : {},
			} )
			.then( fromResponse, function ( error ) {
				// With parse:false core does NOT resolve on a non-2xx — parseAndThrowError()
				// rethrows the raw Response (wp-includes/js/dist/api-fetch.js). Reading it here
				// is what keeps the server's own message: without this every 4xx/5xx fell into
				// request()'s blanket catch and surfaced as "Something went wrong", which is how
				// a plain 404 from the Email Studio spent a release looking like a mystery.
				if ( isResponse( error ) ) {
					return fromResponse( error );
				}
				throw error; // genuine transport failure — request() owns it
			} );
	}

	function viaFetch( url, method, data, opts ) {
		var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
		var timeoutMs = ( opts && opts.timeoutMs ) || DEFAULT_TIMEOUT;
		var timer = controller
			? window.setTimeout( function () {
				controller.abort();
			}, timeoutMs )
			: null;

		var headers = { Accept: 'application/json' };
		var nonce = nonceFor( opts );
		if ( nonce ) {
			headers[ 'X-WP-Nonce' ] = nonce;
		}
		var init = { method: method, headers: headers, signal: controller ? controller.signal : undefined };
		if ( data !== undefined && method !== 'GET' ) {
			headers[ 'Content-Type' ] = 'application/json';
			init.body = JSON.stringify( data );
		}

		return window.fetch( url, init ).then( function ( response ) {
			if ( timer ) {
				window.clearTimeout( timer );
			}
			return fromResponse( response );
		} );
	}

	function request( url, method, data, opts ) {
		emit( document, 'corex:request:start', { url: url, method: method } );

		var run = wp && typeof wp.apiFetch === 'function' ? viaApiFetch : viaFetch;

		return run( url, method, data, opts )
			.catch( function () {
				// Genuine transport failure only: no response ever arrived (network down,
				// timeout/abort, DNS). An error *response* is read by fromResponse() and keeps
				// its own message, so status 0 now means exactly "nothing came back".
				return { ok: false, status: 0, envelope: genericError() };
			} )
			.then( function ( result ) {
				emit( document, 'corex:request:end', { url: url, method: method, ok: result.ok } );
				return result;
			} );
	}

	var api = {
		get: function ( url, opts ) {
			return request( url, 'GET', undefined, opts );
		},
		post: function ( url, data, opts ) {
			return request( url, 'POST', data || {}, opts );
		},
		patch: function ( url, data, opts ) {
			return request( url, 'PATCH', data || {}, opts );
		},
		delete: function ( url, opts ) {
			return request( url, 'DELETE', undefined, opts );
		},
	};

	/* ----------------------------------------------------------------------- *
	 * Corex.loading — disable + aria-busy + spinner + dedupe + restore.
	 * ----------------------------------------------------------------------- */

	var loading = {
		start: function ( region, submitEl ) {
			if ( ! region || region.classList.contains( 'corex-is-loading' ) ) {
				return null; // dedupe: already loading
			}
			region.classList.add( 'corex-is-loading' );
			region.setAttribute( 'aria-busy', 'true' );

			var spinner = document.createElement( 'span' );
			spinner.className = 'corex-spinner';
			spinner.setAttribute( 'aria-hidden', 'true' );
			if ( submitEl ) {
				submitEl.disabled = true;
				submitEl.insertAdjacentElement( 'afterend', spinner );
			} else {
				region.appendChild( spinner );
			}

			return { region: region, submitEl: submitEl, spinner: spinner };
		},
		stop: function ( token ) {
			if ( ! token ) {
				return;
			}
			token.region.classList.remove( 'corex-is-loading' );
			token.region.removeAttribute( 'aria-busy' );
			if ( token.submitEl ) {
				token.submitEl.disabled = false;
			}
			if ( token.spinner && token.spinner.parentNode ) {
				token.spinner.parentNode.removeChild( token.spinner );
			}
		},
	};

	/* ----------------------------------------------------------------------- *
	 * Corex.notices — write the accessible global status.
	 * ----------------------------------------------------------------------- */

	var notices = {
		status: function ( region, message, kind ) {
			var status = region.querySelector( '.corex-form__status' );
			if ( ! status ) {
				return;
			}
			status.textContent = message || '';
			status.classList.toggle( 'is-error', kind === 'error' );
			status.classList.toggle( 'is-success', kind === 'success' );
		},
	};

	/* ----------------------------------------------------------------------- *
	 * Validation — mirrors the PHP rules (Corex\Forms\Validation\Rules\*), driven
	 * by the schema the server embeds in data-corex-schema (spec 020). Returns the
	 * failing rule KEY per field; the server re-validates and stays authoritative.
	 * ----------------------------------------------------------------------- */

	function isEmpty( value ) {
		return value === null || value === undefined || String( value ).trim() === '';
	}

	function isNumericValue( value ) {
		if ( typeof value === 'number' ) {
			return ! Number.isNaN( value );
		}
		if ( typeof value !== 'string' ) {
			return false;
		}
		var trimmed = value.trim();
		return trimmed !== '' && ! Number.isNaN( Number( trimmed ) );
	}

	function length( value ) {
		return [].concat( Array.prototype.slice.call( String( value ) ) ).length;
	}

	var RULES = {
		required: function ( value ) {
			return isEmpty( value ) ? 'required' : null;
		},
		email: function ( value ) {
			if ( isEmpty( value ) ) {
				return null;
			}
			return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( String( value ) ) ? null : 'email';
		},
		max: function ( value, params ) {
			if ( isEmpty( value ) ) {
				return null;
			}
			var limit = parseInt( ( params && params[ 0 ] ) || '0', 10 );
			return isNumericValue( value )
				? ( Number( value ) > limit ? 'max' : null )
				: ( length( value ) > limit ? 'max' : null );
		},
		min: function ( value, params ) {
			if ( isEmpty( value ) ) {
				return null;
			}
			var limit = parseInt( ( params && params[ 0 ] ) || '0', 10 );
			return isNumericValue( value )
				? ( Number( value ) < limit ? 'min' : null )
				: ( length( value ) < limit ? 'min' : null );
		},
		numeric: function ( value ) {
			if ( isEmpty( value ) ) {
				return null;
			}
			return isNumericValue( value ) ? null : 'numeric';
		},
		url: function ( value ) {
			if ( isEmpty( value ) ) {
				return null;
			}
			return /^https?:\/\/\S+\.\S+/i.test( String( value ).trim() ) ? null : 'url';
		},
		// E.164: a leading + and 8-15 digits. Mirrors Corex\Forms\Validation\Rules\Phone.
		phone: function ( value ) {
			if ( isEmpty( value ) ) {
				return null;
			}
			var digits = String( value ).replace( /[\s()\-.]/g, '' );
			return /^\+[1-9]\d{7,14}$/.test( digits ) ? null : 'phone';
		},
		// Mirrors PeregoSite\Forms\Rules\MaxWords. Absent from this table, the rule was skipped
		// entirely client-side: a 500-word message submitted, round-tripped, and came back a 422
		// labelled "Please check this field." — with no hint that the problem was word count.
		max_words: function ( value, params ) {
			if ( isEmpty( value ) ) {
				return null;
			}
			var limit = parseInt( ( params && params[ 0 ] ) || '0', 10 );
			return countWords( value ) > limit ? 'max_words' : null;
		},
	};

	function countWords( value ) {
		var words = String( value ).trim().split( /\s+/ );
		return words.length === 1 && words[ 0 ] === '' ? 0 : words.length;
	}

	function validateField( field, value ) {
		var rules = field.rules || [];
		for ( var i = 0; i < rules.length; i++ ) {
			var spec = rules[ i ];
			var rule = RULES[ spec.rule ];
			if ( ! rule ) {
				continue;
			}
			var error = rule( value, spec.params || [] );
			if ( error ) {
				return error; // bail per field — first failing rule wins (matches PHP)
			}
		}
		return null;
	}

	function validate( schema, values ) {
		var errors = {};
		( schema || [] ).forEach( function ( field ) {
			var present = Object.prototype.hasOwnProperty.call( values, field.name );
			if ( ! present && ! field.required ) {
				return;
			}
			var error = validateField( field, present ? values[ field.name ] : null );
			if ( error ) {
				errors[ field.name ] = error;
			}
		} );
		return errors;
	}

	/**
	 * Per-rule messages the SERVER supplied, via data-corex-messages.
	 *
	 * The fallbacks below go through wp.i18n, which needs a JS translation file per text domain.
	 * Shipping one is optional and easily forgotten — on a site that has none, every validation
	 * message stays English no matter what locale the page is in, which is what an Arabic visitor
	 * saw. The renderer emits the same strings through PHP `__()` instead, where the site's own
	 * .mo (or a `gettext` filter) already translates them, so the message follows the page.
	 */
	function messagesOf( form ) {
		try {
			return JSON.parse( form.dataset.corexMessages || '{}' ) || {};
		} catch ( e ) {
			return {};
		}
	}

	function messageFor( key, form ) {
		var supplied = form ? messagesOf( form )[ key ] : null;
		if ( supplied ) {
			return supplied;
		}
		switch ( key ) {
			case 'required':
				return t( 'This field is required.' );
			case 'email':
				return t( 'Enter a valid email address.' );
			case 'numeric':
				return t( 'Enter a number.' );
			case 'url':
				return t( 'Enter a valid link.' );
			case 'phone':
				return t( 'Enter a phone number including its country code.' );
			case 'max_words':
				return t( 'This message is too long.' );
			case 'max':
				return t( 'This value is too long.' );
			case 'min':
				return t( 'This value is too short.' );
			default:
				return t( 'Please check this field.' );
		}
	}

	/* ----------------------------------------------------------------------- *
	 * Corex.forms — bind a schema-carrying form to the whole submit lifecycle.
	 * Reuses the spec-020 DOM contract so markup is unchanged.
	 * ----------------------------------------------------------------------- */

	function collect( form ) {
		var data = {};
		form.querySelectorAll( 'input[name], textarea[name], select[name]' ).forEach( function ( el ) {
			var name = el.name;
			var isArray = name.slice( -2 ) === '[]';
			if ( isArray ) {
				name = name.slice( 0, -2 );
			}
			if ( el.type === 'checkbox' ) {
				if ( isArray ) {
					if ( ! Array.isArray( data[ name ] ) ) {
						data[ name ] = [];
					}
					if ( el.checked ) {
						data[ name ].push( el.value );
					}
				} else {
					data[ name ] = el.checked ? el.value : '';
				}
				return;
			}
			if ( el.type === 'radio' ) {
				if ( el.checked ) {
					data[ name ] = el.value;
				} else if ( ! ( name in data ) ) {
					data[ name ] = '';
				}
				return;
			}
			// A multiple <select> reports only its FIRST selected option through .value, so the
			// line below silently dropped every extra pick — a visitor choosing three services
			// had one stored and one emailed. Read the whole selection instead.
			if ( el.multiple ) {
				data[ name ] = Array.prototype.map.call(
					el.selectedOptions,
					function ( option ) {
						return option.value;
					}
				);
				return;
			}
			data[ name ] = el.value;
		} );
		return data;
	}

	function fieldWrapper( form, name ) {
		return form.querySelector( '[data-corex-field="' + ( window.CSS ? window.CSS.escape( name ) : name ) + '"]' );
	}

	function clearErrors( form ) {
		form.querySelectorAll( '.corex-form__error' ).forEach( function ( el ) {
			el.textContent = '';
		} );
		form.querySelectorAll( '[aria-invalid="true"]' ).forEach( function ( el ) {
			el.removeAttribute( 'aria-invalid' );
		} );
	}

	function showErrors( form, errors ) {
		var firstControl = null;
		Object.keys( errors ).forEach( function ( name ) {
			var wrapper = fieldWrapper( form, name );
			if ( ! wrapper ) {
				return;
			}
			var message = wrapper.querySelector( '.corex-form__error' );
			var control = wrapper.querySelector( 'input, textarea, select' );
			if ( message ) {
				message.textContent = messageFor( errors[ name ], form );
			}
			if ( control ) {
				control.setAttribute( 'aria-invalid', 'true' );
				firstControl = firstControl || control;
			}
		} );
		if ( firstControl ) {
			firstControl.focus();
		}
	}

	function schemaOf( form ) {
		try {
			return JSON.parse( form.dataset.corexSchema || '[]' );
		} catch ( e ) {
			return [];
		}
	}

	function successOf( form, envelope ) {
		var configured = {};
		try {
			configured = JSON.parse( form.dataset.corexSuccessConfig || '{}' );
		} catch ( e ) {
			configured = {};
		}
		if ( envelope.data && envelope.data.success && typeof envelope.data.success === 'object' ) {
			return Object.assign( {}, configured, envelope.data.success );
		}
		return configured;
	}

	function renderSuccess( form, envelope ) {
		var success = successOf( form, envelope );
		var target = success.target_url || ( success.type === 'url' ? success.url : '' );
		if ( ( success.type === 'url' || success.type === 'page' ) && target ) {
			emit( form, 'corex:form:redirect', { url: target, success: success } );
			window.location.assign( target );
			return;
		}
		if ( success.type && success.type !== 'inline' ) {
			emit( form, 'corex:form:custom-success', { success: success } );
		}
		notices.status( form, success.message || form.dataset.corexSuccess || envelope.message, 'success' );
	}

	function submit( form ) {
		if ( form.dataset.corexBusy === '1' ) {
			return; // dedupe concurrent submits
		}
		form.dataset.corexBusy = '1';

		var submitEl = form.querySelector( '[type="submit"]' );
		var token = loading.start( form, submitEl );

		api.post( form.dataset.corexEndpoint, collect( form ), { nonce: form.dataset.corexNonce } ).then( function ( result ) {
			loading.stop( token );
			delete form.dataset.corexBusy;

			var envelope = result.envelope;
			if ( envelope.ok ) {
				form.reset();
				renderSuccess( form, envelope );
				emit( form, 'corex:form:success', { envelope: envelope } );
				return;
			}
			if ( envelope.errors ) {
				showErrors( form, envelope.errors );
			}
			notices.status( form, envelope.message || form.dataset.corexError, 'error' );
			emit( form, 'corex:form:error', { envelope: envelope } );
		} );
	}

	function onSubmit( form, event ) {
		event.preventDefault();
		clearErrors( form );

		var errors = validate( schemaOf( form ), collect( form ) );
		if ( Object.keys( errors ).length > 0 ) {
			showErrors( form, errors );
			notices.status( form, form.dataset.corexError, 'error' );
			return; // client error → no request leaves the browser
		}
		submit( form );
	}

	/**
	 * Clear or update ONE field's error, without touching the rest of the form.
	 *
	 * `clearErrors`/`showErrors` operate on the whole form, which is right on submit and wrong
	 * while typing — re-running them would blank a neighbour's error the moment you edited this
	 * field. This re-validates the single field against its own schema entry.
	 */
	function revalidateField( form, control ) {
		var name = ( control.name || '' ).replace( /\[\]$/, '' );
		var field = schemaOf( form ).filter( function ( entry ) {
			return entry.name === name;
		} )[ 0 ];
		var wrapper = fieldWrapper( form, name );
		if ( ! field || ! wrapper ) {
			return;
		}

		var error = validateField( field, collect( form )[ name ] );
		var message = wrapper.querySelector( '.corex-form__error' );
		if ( message ) {
			message.textContent = error ? messageFor( error, form ) : '';
		}
		if ( error ) {
			control.setAttribute( 'aria-invalid', 'true' );
		} else {
			control.removeAttribute( 'aria-invalid' );
		}
	}

	/**
	 * Live re-validation, on the touched-field rule.
	 *
	 * Validation used to run on submit and only on submit, so an error stayed on screen while the
	 * visitor fixed it and could only be cleared by submitting again — they had no way to know they
	 * had corrected it. Re-validating on `blur` always, and on every keystroke only once a field is
	 * already marked invalid, gives immediate confirmation without nagging someone part-way through
	 * typing their first character.
	 */
	function bindLiveValidation( form ) {
		var handle = function ( event, whenTouchedOnly ) {
			var control = event.target;
			if ( ! control || ! control.name || ! form.contains( control ) ) {
				return;
			}
			if ( whenTouchedOnly && control.getAttribute( 'aria-invalid' ) !== 'true' ) {
				return;
			}
			revalidateField( form, control );
		};

		// `blur` and `focusout`: blur does not bubble, so the capture phase is what makes one
		// listener on the form cover every control, including any added later.
		form.addEventListener( 'blur', function ( event ) {
			handle( event, false );
		}, true );
		form.addEventListener( 'input', function ( event ) {
			handle( event, true );
		} );
		form.addEventListener( 'change', function ( event ) {
			handle( event, true );
		} );
	}

	var forms = {
		bind: function ( form ) {
			if ( ! form || form.dataset.corexBound === '1' ) {
				return; // idempotent
			}
			form.dataset.corexBound = '1';
			form.addEventListener( 'submit', function ( event ) {
				onSubmit( form, event );
			} );
			bindLiveValidation( form );
		},
		validate: validate,
	};

	function autoBind() {
		document.querySelectorAll( '.corex-form' ).forEach( forms.bind );
	}

	window.Corex = window.Corex || {};
	window.Corex.api = api;
	window.Corex.forms = forms;
	window.Corex.loading = loading;
	window.Corex.notices = notices;

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', autoBind );
	} else {
		autoBind();
	}
} )( window, document );
