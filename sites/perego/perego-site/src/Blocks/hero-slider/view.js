/**
 * perego/hero-slider Interactivity API store (spec 002 / M2, US1). Drives the homepage hero:
 * auto-advancing headline slides (6.5s), a dot tablist, and a polite live-region announcer. Pauses
 * on pointer hover and when the tab is hidden; never auto-advances under
 * `prefers-reduced-motion: reduce`; manual dot selection stops autoplay (WCAG 2.2.2). Ported from
 * the handoff's main.js heroSlider IIFE; prev/next + pause/play controls were dropped per an
 * explicit owner request (dots-only) — see DECISIONS.md. See spec 002 FR US1.
 */
import { store, getContext } from '@wordpress/interactivity';

const AUTO_ADVANCE_MS = 6500;
// Below this horizontal travel a pointer gesture is a tap/click, not a swipe (px).
const SWIPE_THRESHOLD = 45;

let timer = null;
// Pointer X at the start of a drag on the hero; null when no drag is in progress.
let dragStartX = null;

function isReducedMotion() {
	return (
		typeof window.matchMedia === 'function' &&
		window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
	);
}

function stopTimer() {
	if ( timer !== null ) {
		clearInterval( timer );
		timer = null;
	}
}

// The single advance primitive shared by autoplay and swipe; wraps in both directions.
function advance( context, direction ) {
	context.activeIndex =
		( context.activeIndex + direction + context.count ) % context.count;
}

const { state, actions } = store( 'perego/hero-slider', {
	state: {
		/** Polite announcement of the active slide, e.g. "Slide 2 of 3". */
		get currentSlideLabel() {
			const context = getContext();
			const template = context.announce || 'Slide %1$s of %2$s';

			return template
				.replace( '%1$s', String( context.activeIndex + 1 ) )
				.replace( '%2$s', String( context.count ) );
		},
	},
	actions: {
		goTo() {
			const context = getContext();
			// Nested per-dot context carries `index`; the shared root context carries activeIndex.
			context.activeIndex = context.index;
			actions.stopAutoplay();
		},

		// Temporary suspend/resume on hover — does not change the user's play/pause intent.
		pause() {
			stopTimer();
		},

		resume() {
			const context = getContext();
			if ( context.isPlaying ) {
				actions.startAutoplay();
			}
		},

		startAutoplay() {
			const context = getContext();
			if ( isReducedMotion() || ! context.isPlaying ) {
				return;
			}
			stopTimer();
			timer = setInterval( () => {
				advance( context, 1 );
			}, AUTO_ADVANCE_MS );
		},

		// Next/previous slide, reused by the autoplay primitive and by swipe navigation.
		next() {
			advance( getContext(), 1 );
		},

		prev() {
			advance( getContext(), -1 );
		},

		// Swipe/drag to change slides — one pointer path covers touch and mouse. A gesture shorter
		// than the threshold is a tap (dot/CTA clicks still work); a committed swipe halts autoplay,
		// matching the dots' stop-on-interaction (WCAG 2.2.2).
		pointerDown( event ) {
			dragStartX = event.clientX;
			stopTimer(); // suspend auto-advance while the finger/mouse is down
		},

		pointerUp( event ) {
			if ( dragStartX === null ) {
				return;
			}

			const delta = event.clientX - dragStartX;
			dragStartX = null;

			if ( Math.abs( delta ) < SWIPE_THRESHOLD ) {
				actions.resume(); // a tap, not a swipe — resume what pointerDown paused
				return;
			}

			// Advance in the reading direction: swipe-left is "forward" in LTR, swipe-right in RTL.
			const forward =
				document.documentElement.dir === 'rtl' ? delta > 0 : delta < 0;
			advance( getContext(), forward ? 1 : -1 );
			actions.stopAutoplay();
		},

		// Manual navigation halts autoplay so it never fights the visitor; play resumes it.
		stopAutoplay() {
			const context = getContext();
			context.isPlaying = false;
			stopTimer();
		},
	},
	callbacks: {
		init() {
			const context = getContext();

			if ( isReducedMotion() ) {
				context.isPlaying = false;
				return;
			}

			actions.startAutoplay();

			document.addEventListener( 'visibilitychange', () => {
				if ( document.hidden ) {
					stopTimer();
				} else if ( context.isPlaying ) {
					actions.startAutoplay();
				}
			} );
		},

		slideHidden() {
			const context = getContext();

			return context.index !== context.activeIndex;
		},

		dotSelected() {
			const context = getContext();

			return context.index === context.activeIndex;
		},
	},
} );
