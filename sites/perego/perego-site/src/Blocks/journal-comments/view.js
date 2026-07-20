/**
 * Front-end behaviour for the journal comments section (perego-theme/journal-comments).
 *
 * Two jobs, both progressive enhancements over the server-rendered, no-JS-usable form:
 *
 * 1. Reply WITHOUT moving the DOM. Clicking a comment's "Reply" sets the form's hidden
 *    `comment_parent`, reveals a "Replying to X — Cancel" chip, and scrolls to the form. Cancel
 *    clears the parent and hides the chip, so the user can always switch back to a normal top-level
 *    comment. (This replaces WordPress's comment-reply.js form-moving, which trapped users in
 *    reply mode.)
 *
 * 2. AJAX submit to the secure `perego/v1/comments` endpoint (nonce + honeypot + rate limit +
 *    WP moderation server-side). On success it shows an accessible status message — an approved
 *    comment is inserted into the list on the fly (count bumped); a held comment shows an
 *    "awaiting review" message and is NOT injected. Errors map to friendly, distinct messages.
 */

const init = () => {
	document.querySelectorAll( '[data-perego-comments]' ).forEach( ( root ) => {
		if ( root.dataset.peregoBound === '1' ) {
			return;
		}
		root.dataset.peregoBound = '1';

		const form = root.querySelector( '.comment-form' );
		if ( ! form ) {
			return;
		}

		const messages = parseMessages( root.dataset.messages );
		const endpoint = root.dataset.endpoint || '';
		const nonce = root.dataset.nonce || '';

		const status = form.querySelector( '.comment-form__status' );
		const submit = form.querySelector( '.comment-form__submit' );
		const parentInput = form.querySelector( 'input[name="comment_parent"]' );
		const chip = form.querySelector( '.comment-form__reply-chip' );
		const chipName = form.querySelector( '.comment-form__reply-name' );
		const cancelBtn = form.querySelector( '.comment-form__cancel' );

		const setStatus = ( key, tone ) => {
			if ( ! status ) {
				return;
			}
			status.textContent = messages[ key ] || '';
			status.classList.remove( 'is-success', 'is-error', 'is-busy' );
			if ( tone ) {
				status.classList.add( 'is-' + tone );
			}
		};

		const clearReply = () => {
			if ( parentInput ) {
				parentInput.value = '0';
			}
			if ( chip ) {
				chip.hidden = true;
			}
		};

		// Reply links (event-delegated so injected comments work too).
		root.addEventListener( 'click', ( event ) => {
			const link = event.target.closest( '.comment__reply[data-parent-id]' );
			if ( ! link || ! root.contains( link ) ) {
				return;
			}
			event.preventDefault();
			if ( parentInput ) {
				parentInput.value = link.dataset.parentId || '0';
			}
			if ( chipName ) {
				chipName.textContent = link.dataset.parentName || '';
			}
			if ( chip ) {
				chip.hidden = false;
			}
			form.scrollIntoView( { behavior: 'smooth', block: 'center' } );
			const author = form.querySelector( '#cm-author' );
			if ( author ) {
				author.focus();
			}
		} );

		if ( cancelBtn ) {
			cancelBtn.addEventListener( 'click', () => {
				clearReply();
				setStatus( '', null );
			} );
		}

		form.addEventListener( 'submit', async ( event ) => {
			// No JS-less fallback needed here — enhance only when we have somewhere to POST.
			if ( ! endpoint ) {
				return;
			}
			event.preventDefault();
			if ( submit && submit.disabled ) {
				return;
			}

			const author = form.querySelector( '#cm-author' );
			const email = form.querySelector( '#cm-email' );
			const comment = form.querySelector( '#cm-comment' );

			if ( ! author.value.trim() || ! isEmail( email.value ) || ! comment.value.trim() ) {
				setStatus( 'invalid', 'error' );
				( ! author.value.trim() ? author : ! isEmail( email.value ) ? email : comment ).focus();
				return;
			}

			lock( submit, true );
			setStatus( 'sending', 'busy' );

			try {
				const response = await fetch( endpoint, {
					method: 'POST',
					headers: { 'X-WP-Nonce': nonce },
					body: new FormData( form ),
				} );
				let payload = {};
				try {
					payload = await response.json();
				} catch ( e ) {
					payload = {};
				}

				if ( response.ok && payload.ok ) {
					if ( payload.status === 'approved' && payload.commentHtml ) {
						insertComment( root, payload.commentHtml, parentInput && parentInput.value, payload.count, messages );
						setStatus( 'approved', 'success' );
					} else {
						setStatus( 'moderation', 'success' );
					}
					comment.value = '';
					clearReply();
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

/**
 * Insert an approved comment card into the list on the fly: a reply goes right after its parent's
 * subtree, a top-level comment goes at the end. Creates the list + title if this is the first comment.
 */
function insertComment( root, html, parentId, count, messages ) {
	const section = root;
	let list = section.querySelector( '.comment-list' );

	if ( ! list ) {
		// First comment on the post — build the title + list before the form.
		const title = document.createElement( 'h2' );
		title.className = 'comments__title';
		list = document.createElement( 'ul' );
		list.className = 'comment-list';
		const respond = section.querySelector( '#respond' );
		section.insertBefore( title, respond );
		section.insertBefore( list, respond );
	}

	const temp = document.createElement( 'tbody' );
	temp.innerHTML = html.trim();
	const li = temp.querySelector( 'li.comment' ) || temp.firstElementChild;
	if ( ! li ) {
		return;
	}

	const parent = parentId && parentId !== '0' ? section.querySelector( '#comment-' + CSS.escape( parentId ) ) : null;
	if ( parent ) {
		// Place after the parent and any of its existing replies.
		let anchor = parent;
		while ( anchor.nextElementSibling && anchor.nextElementSibling.classList.contains( 'comment--reply' ) ) {
			anchor = anchor.nextElementSibling;
		}
		anchor.after( li );
	} else {
		list.appendChild( li );
	}

	// Update the "N Comments" title.
	const title = section.querySelector( '.comments__title' );
	if ( title && typeof count === 'number' ) {
		const format = count === 1 ? messages.__titleOne : messages.__title;
		if ( format ) {
			title.textContent = format.replace( '%d', String( count ) );
		}
	}

	// Reveal animation parity with the rest of the site's cards.
	li.classList.add( 'reveal', 'is-visible' );
}

function lock( submit, busy ) {
	if ( ! submit ) {
		return;
	}
	submit.disabled = busy;
	submit.setAttribute( 'aria-busy', busy ? 'true' : 'false' );
}

function isEmail( value ) {
	return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( ( value || '' ).trim() );
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
