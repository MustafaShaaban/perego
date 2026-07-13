/**
 * perego/project-gallery Interactivity API store (spec 003 / M3, US2). Opens a thumbnail into an
 * accessible lightbox dialog: focus moves into the dialog and is trapped (Tab/Shift+Tab wrap),
 * Escape + backdrop close and restore focus to the opening thumbnail, arrow keys and prev/next
 * navigate with wraparound, and a polite counter announces "n / total". Ported from the handoff
 * main.js lightbox IIFE. See spec 003 US2 / ACCESSIBILITY_HANDOFF (dialog + focus trap + counter).
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

let lastFocusedBeforeOpen = null;

function focusableIn( container ) {
	return Array.from(
		container.querySelectorAll(
			'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'
		)
	).filter( ( el ) => el.offsetParent !== null );
}

const { state, actions } = store( 'perego/project-gallery', {
	state: {
		get currentSrc() {
			const context = getContext();

			return context.items[ context.activeIndex ]?.src || '';
		},
		get currentAlt() {
			const context = getContext();

			return context.items[ context.activeIndex ]?.alt || '';
		},
		get counterLabel() {
			const context = getContext();
			const template = context.counter || '%1$s / %2$s';

			return template
				.replace( '%1$s', String( context.activeIndex + 1 ) )
				.replace( '%2$s', String( context.count ) );
		},
	},
	actions: {
		open() {
			const context = getContext();
			context.activeIndex = context.index;
			context.isOpen = true;
			lastFocusedBeforeOpen = document.activeElement;
			document.body.style.overflow = 'hidden';

			// Move focus into the dialog after it paints.
			const { ref } = getElement();
			const dialog = ref
				.closest( '.project-gallery' )
				?.querySelector( '.lightbox' );
			if ( dialog ) {
				requestAnimationFrame( () => {
					const focusables = focusableIn( dialog );
					( focusables[ 0 ] || dialog ).focus?.();
				} );
			}
		},

		close() {
			const context = getContext();
			if ( ! context.isOpen ) {
				return;
			}
			context.isOpen = false;
			document.body.style.overflow = '';
			lastFocusedBeforeOpen?.focus?.();
		},

		next() {
			const context = getContext();
			context.activeIndex = ( context.activeIndex + 1 ) % context.count;
		},

		prev() {
			const context = getContext();
			context.activeIndex =
				( context.activeIndex - 1 + context.count ) % context.count;
		},

		onKeydown( event ) {
			const context = getContext();
			if ( ! context.isOpen ) {
				return;
			}

			if ( event.key === 'Escape' ) {
				actions.close();
				return;
			}
			if ( event.key === 'ArrowRight' ) {
				actions.next();
				return;
			}
			if ( event.key === 'ArrowLeft' ) {
				actions.prev();
				return;
			}
			if ( event.key !== 'Tab' ) {
				return;
			}

			const dialog = event.currentTarget;
			const focusables = focusableIn( dialog );
			if ( focusables.length === 0 ) {
				return;
			}
			const first = focusables[ 0 ];
			const last = focusables[ focusables.length - 1 ];

			if ( event.shiftKey && document.activeElement === first ) {
				event.preventDefault();
				last.focus();
			} else if ( ! event.shiftKey && document.activeElement === last ) {
				event.preventDefault();
				first.focus();
			}
		},
	},
	callbacks: {
		lightboxHidden() {
			return ! getContext().isOpen;
		},
	},
} );
