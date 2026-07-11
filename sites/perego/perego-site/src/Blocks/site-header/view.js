/**
 * perego/site-header Interactivity API store. Handles: the sticky-scroll header state, the
 * mobile slide-in nav panel (focus trap, scroll lock, Esc/backdrop/link-click close, focus
 * restore to the hamburger), the mobile Services tap-accordion (desktop uses pure CSS
 * :hover/:focus-within — no JS needed there), and the language toggle (persists to a cookie,
 * swaps <html lang>/dir and the font stack). See spec 001 FR-002–FR-004, FR-008.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

const SCROLL_THRESHOLD = 20;
const COOKIE_NAME = 'perego_lang';
const COOKIE_MAX_AGE_SECONDS = 60 * 60 * 24 * 365;
const RTL_LOCALES = [ 'ar' ];

let lastFocusedBeforeMenuOpen = null;

function isReducedMotion() {
	return (
		typeof window.matchMedia === 'function' &&
		window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
	);
}

function getFocusableElements( container ) {
	return Array.from(
		container.querySelectorAll(
			'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'
		)
	);
}

function applyLanguage( locale ) {
	const isRtl = RTL_LOCALES.includes( locale );
	document.documentElement.lang = locale;
	document.documentElement.dir = isRtl ? 'rtl' : 'ltr';
	document.documentElement.classList.toggle( 'lang-ar', isRtl );
}

function persistLanguage( locale ) {
	document.cookie = `${ COOKIE_NAME }=${ locale };max-age=${ COOKIE_MAX_AGE_SECONDS };path=/`;
}

function readPersistedLanguage() {
	const match = document.cookie.match( new RegExp( `(?:^|; )${ COOKIE_NAME }=([^;]*)` ) );

	return match ? decodeURIComponent( match[ 1 ] ) : null;
}

const { actions } = store( 'perego/site-header', {
	state: {},
	actions: {
		toggleMenu() {
			const context = getContext();
			const { ref } = getElement();
			context.isMenuOpen = ! context.isMenuOpen;

			if ( context.isMenuOpen ) {
				lastFocusedBeforeMenuOpen = document.activeElement;
				document.body.style.overflow = 'hidden';
			} else {
				document.body.style.overflow = '';
				( lastFocusedBeforeMenuOpen || ref ).focus?.();
			}
		},

		closeMenu() {
			const context = getContext();

			if ( ! context.isMenuOpen ) {
				return;
			}

			context.isMenuOpen = false;
			document.body.style.overflow = '';
			lastFocusedBeforeMenuOpen?.focus?.();
		},

		handleMenuKeydown( event ) {
			const context = getContext();

			if ( ! context.isMenuOpen ) {
				return;
			}

			if ( event.key === 'Escape' ) {
				actions.closeMenu();

				return;
			}

			if ( event.key !== 'Tab' ) {
				return;
			}

			const panel = event.currentTarget;
			const focusable = getFocusableElements( panel );

			if ( focusable.length === 0 ) {
				return;
			}

			const first = focusable[ 0 ];
			const last = focusable[ focusable.length - 1 ];

			if ( event.shiftKey && document.activeElement === first ) {
				event.preventDefault();
				last.focus();
			} else if ( ! event.shiftKey && document.activeElement === last ) {
				event.preventDefault();
				first.focus();
			}
		},

		toggleMobileDropdown( event ) {
			const isMobile =
				window.matchMedia && window.matchMedia( '(max-width: 1024px)' ).matches;

			if ( ! isMobile ) {
				return;
			}

			event.preventDefault();
			const item = event.currentTarget.closest( '.perego-header__nav-item' );
			item?.classList.toggle( 'is-open' );
		},

		switchLanguage( event ) {
			const locale = event.target.closest( '[data-locale]' )?.dataset.locale;

			if ( ! locale ) {
				return;
			}

			applyLanguage( locale );
			persistLanguage( locale );
			window.location.reload();
		},
	},
	callbacks: {
		init() {
			const context = getContext();
			const { ref } = getElement();

			const persisted = readPersistedLanguage();
			if ( persisted ) {
				applyLanguage( persisted );
			}

			const onScroll = () => {
				context.isScrolled = window.scrollY > SCROLL_THRESHOLD;
			};
			onScroll();
			window.addEventListener( 'scroll', onScroll, { passive: true } );

			const backdrop = ref.querySelector?.( '.perego-header__nav-backdrop' );
			backdrop?.addEventListener( 'click', () => {
				if ( context.isMenuOpen ) {
					context.isMenuOpen = false;
					document.body.style.overflow = '';
					lastFocusedBeforeMenuOpen?.focus?.();
				}
			} );

			ref.querySelectorAll?.( '.perego-header__nav a' ).forEach( ( link ) => {
				link.addEventListener( 'click', () => {
					if ( context.isMenuOpen ) {
						context.isMenuOpen = false;
						document.body.style.overflow = '';
					}
				} );
			} );

			if ( isReducedMotion() ) {
				ref.classList?.add( 'is-reduced-motion' );
			}
		},
	},
} );
