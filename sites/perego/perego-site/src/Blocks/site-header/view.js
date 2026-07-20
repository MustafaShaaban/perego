/**
 * perego/site-header Interactivity API store. Handles: the sticky-scroll header state, the
 * mobile slide-in nav panel (focus trap, scroll lock, Esc/backdrop/link-click close, focus
 * restore to the hamburger), the mobile Services tap-accordion (desktop uses pure CSS
 * :hover/:focus-within — no JS needed there). Language switching is real navigation now — the
 * switcher is server-rendered anchors to each locale's URL (spec Phase 5, no JS-only toggle); this
 * script only handles client-side language state in the fallback (non-URL-managed) mode: mirror the
 * persisted cookie into <html> on load and persist each switch-link click for cross-page memory.
 * See spec 001 FR-002–FR-004, FR-008.
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
				document.getElementById( 'mainNav' )?.classList.toggle( 'is-open', context.isMenuOpen );
				document.getElementById( 'navBackdrop' )?.classList.toggle( 'is-open', context.isMenuOpen );
				ref.classList.toggle( 'is-open', context.isMenuOpen );

			if ( context.isMenuOpen ) {
				lastFocusedBeforeMenuOpen = document.activeElement;
				document.body.style.overflow = 'hidden';
				// Move focus into the panel so the focus trap works AND a keyboard user can press
				// Escape to close it — the Escape/Tab handler is scoped to the nav, so it only fires
				// when focus is inside the panel (it never is if focus stays on the hamburger, which
				// was the bug the interaction verification caught).
				const header = ref.closest?.( '.site-header' ) || ref;
				const firstFocusable = header.querySelector?.(
					'.main-nav a[href], .main-nav button:not([disabled])'
				);
				// `preventScroll` — the panel slides in from `translateX(100%)`, so focusing a
					// still-off-screen link made the browser smooth-scroll the page to the top when the
					// hamburger was tapped. Keep the focus (for the trap) without moving the viewport.
					firstFocusable?.focus?.( { preventScroll: true } );
			} else {
				document.body.style.overflow = '';
				( lastFocusedBeforeMenuOpen || ref ).focus?.( { preventScroll: true } );
			}
		},

		closeMenu() {
			const context = getContext();

			if ( ! context.isMenuOpen ) {
				return;
			}

			context.isMenuOpen = false;
			document.getElementById( 'mainNav' )?.classList.remove( 'is-open' );
			document.getElementById( 'navBackdrop' )?.classList.remove( 'is-open' );
			document.getElementById( 'navToggle' )?.classList.remove( 'is-open' );
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
			const item = event.currentTarget.closest( '.has-dropdown' );
			const isOpen = item?.classList.toggle( 'is-open' ) ?? false;
			event.currentTarget.setAttribute( 'aria-expanded', String( isOpen ) );
		},

	},
	callbacks: {
		init() {
			const context = getContext();
			const { ref } = getElement();

			// Language switching is real navigation via anchors. Only the fallback (non-URL-managed)
			// mode needs the client: reflect the persisted cookie into <html> on load and persist
			// each switch click so the choice carries across pages. Polylang carries it in the URL.
			const toggle = ref.querySelector?.( '.lang-toggle' );
			if ( toggle && toggle.dataset.langUrlManaged !== '1' ) {
				const persisted = readPersistedLanguage();
				if ( persisted ) {
					applyLanguage( persisted );
				}
				toggle.querySelectorAll( 'a[data-locale]' ).forEach( ( link ) => {
					link.addEventListener( 'click', () => {
						persistLanguage( link.dataset.locale );
					} );
				} );
			}

			const onScroll = () => {
				context.isScrolled = window.scrollY > SCROLL_THRESHOLD;
			};
			onScroll();
			window.addEventListener( 'scroll', onScroll, { passive: true } );

			const backdrop = document.getElementById( 'navBackdrop' );
			backdrop?.addEventListener( 'click', () => {
				if ( context.isMenuOpen ) {
					context.isMenuOpen = false;
					document.getElementById( 'mainNav' )?.classList.remove( 'is-open' );
					backdrop.classList.remove( 'is-open' );
					document.getElementById( 'navToggle' )?.classList.remove( 'is-open' );
					document.body.style.overflow = '';
					lastFocusedBeforeMenuOpen?.focus?.();
				}
			} );

			ref.querySelectorAll?.( '.main-nav a' ).forEach( ( link ) => {
				link.addEventListener( 'click', () => {
					const isMobileServicesTrigger =
						window.matchMedia?.( '(max-width: 1024px)' ).matches &&
						link.parentElement?.classList.contains( 'has-dropdown' );

					if ( isMobileServicesTrigger ) {
						return;
					}

					if ( context.isMenuOpen ) {
						context.isMenuOpen = false;
						document.getElementById( 'mainNav' )?.classList.remove( 'is-open' );
						document.getElementById( 'navBackdrop' )?.classList.remove( 'is-open' );
						document.getElementById( 'navToggle' )?.classList.remove( 'is-open' );
						document.body.style.overflow = '';
					}
				} );
			} );

			// Clear a stale mobile "Services" accordion state when the viewport grows to desktop —
			// otherwise a dropdown opened by tap on mobile stays visually open (its `.is-open` was
			// never removed) once desktop hover/focus rules take over.
			let resizeRaf;
			window.addEventListener(
				'resize',
				() => {
					window.cancelAnimationFrame( resizeRaf );
					resizeRaf = window.requestAnimationFrame( () => {
						if ( window.matchMedia?.( '(min-width: 1025px)' ).matches ) {
							ref.querySelectorAll?.( '.has-dropdown.is-open' ).forEach( ( item ) => {
								item.classList.remove( 'is-open' );
								item
									.querySelector( '.main-nav__link[aria-expanded]' )
									?.setAttribute( 'aria-expanded', 'false' );
							} );
						}
					} );
				},
				{ passive: true }
			);

			// Scroll-spy — on the home page, highlight the nav link for the in-page section currently in
			// view (About / Services / Clients / Contact). Fragment links (`/#about`, `#contact`, …) map
			// to section ids; before the first section (hero) the Home link is active. Only runs where the
			// target sections exist, so inner pages are unaffected.
			const spyEntries = [];
			const navLinks = Array.from( ref.querySelectorAll?.( '.main-nav__link' ) || [] );
			navLinks.forEach( ( link ) => {
				const href = link.getAttribute( 'href' ) || '';
				const hashIndex = href.indexOf( '#' );
				if ( hashIndex === -1 ) {
					return;
				}
				const id = href.slice( hashIndex + 1 );
				const section = id && document.getElementById( id );
				if ( section ) {
					spyEntries.push( { id, section, link } );
				}
			} );

			// The scroll-spy must only run on the front page. Its hash targets (About/Services/Clients)
			// live there, but the footer's `#contact` exists on every page, so without this gate the spy
			// would fire on inner pages and clobber the server-rendered `.is-active` (defaulting to Home).
			// WordPress adds the `home` body class on the front page (EN and AR).
			const isFrontPage =
				document.body.classList.contains( 'home' ) ||
				document.body.classList.contains( 'front-page' );

			if ( isFrontPage && spyEntries.length ) {
				const homeLink = navLinks.find( ( link ) => {
					const path = ( link.getAttribute( 'href' ) || '' ).replace( window.location.origin, '' );
					return path === '/' || path === '/ar/' || /^\/(ar\/)?(#[^/]*)?$/.test( path );
				} );

				const setActive = ( id ) => {
					navLinks.forEach( ( link ) => link.classList.remove( 'is-active' ) );
					if ( id ) {
						spyEntries.find( ( entry ) => entry.id === id )?.link.classList.add( 'is-active' );
					} else {
						homeLink?.classList.add( 'is-active' );
					}
				};

				let spyRaf;
				const updateSpy = () => {
					window.cancelAnimationFrame( spyRaf );
					spyRaf = window.requestAnimationFrame( () => {
						// The active section is the last one whose top has crossed a line ~30% down the
						// viewport (getBoundingClientRect, since the home sections don't share an offset
						// parent). At the very bottom, force the last section active (short trailing
						// sections can never reach the line).
						const line = window.innerHeight * 0.3;
						let current = null;
						spyEntries.forEach( ( entry ) => {
							if ( entry.section.getBoundingClientRect().top <= line ) {
								current = entry.id;
							}
						} );
						const atBottom =
							window.innerHeight + window.scrollY >=
							document.documentElement.scrollHeight - 4;
						if ( atBottom ) {
							current = spyEntries[ spyEntries.length - 1 ].id;
						}
						setActive( current );
					} );
				};
				updateSpy();
				window.addEventListener( 'scroll', updateSpy, { passive: true } );
			}

			if ( isReducedMotion() ) {
				ref.classList?.add( 'is-reduced-motion' );
			}
		},
	},
} );
