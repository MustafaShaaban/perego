/**
 * perego/preloader Interactivity API store. Shows the branded overlay only on a visitor's first
 * homepage view of a session, clears it ~0.9s after mount (soft) or 2.5s regardless (hard safety
 * timeout, so a stuck asset/script can never block the page), and never shows at all under
 * `prefers-reduced-motion`. See spec 001 FR-009.
 */
import { store, getContext } from '@wordpress/interactivity';

const SESSION_KEY = 'perego-preloaded';
const SOFT_HIDE_MS = 900;
const HARD_HIDE_MS = 2500;

function prefersReducedMotion() {
	return (
		typeof window.matchMedia === 'function' &&
		window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
	);
}

function alreadyShownThisSession() {
	try {
		return window.sessionStorage.getItem( SESSION_KEY ) === '1';
	} catch {
		// Storage can throw in locked-down/private-browsing contexts — treat as "already shown"
		// so the preloader fails safe (never shows) rather than showing on every single view.
		return true;
	}
}

function markShownThisSession() {
	try {
		window.sessionStorage.setItem( SESSION_KEY, '1' );
	} catch {
		// Nothing to do if storage is unavailable — the hide timers still run regardless.
	}
}

store( 'perego/preloader', {
	callbacks: {
		init() {
			const context = getContext();

			if ( prefersReducedMotion() || alreadyShownThisSession() ) {
				context.isHidden = true;

				return;
			}

			markShownThisSession();

			// Soft hide: the normal path, independent of whether every asset has finished loading.
			setTimeout( () => {
				context.isHidden = true;
			}, SOFT_HIDE_MS );

			// Hard safety timeout: a guaranteed backstop, kept independent of the soft-hide timer
			// above so a future change to the soft-hide condition (e.g. tying it to a resource or
			// animation event instead of a flat delay) can never leave this stuck open past 2.5s.
			setTimeout( () => {
				context.isHidden = true;
			}, HARD_HIDE_MS );
		},
	},
} );
