/**
 * perego/hero-slider Interactivity API store (spec 002 / M2, US1). Drives the homepage hero:
 * auto-advancing headline slides (6.5s), a dot tablist, prev/next + pause/play controls, and a
 * polite live-region announcer. Pauses on pointer hover and when the tab is hidden; never
 * auto-advances under `prefers-reduced-motion: reduce`; manual navigation stops autoplay
 * (WCAG 2.2.2 — the pause/play control resumes it). Ported from the handoff's main.js heroSlider
 * IIFE with the ACCESSIBILITY_HANDOFF build additions. See spec 002 FR US1.
 */
import { store, getContext } from '@wordpress/interactivity';

const AUTO_ADVANCE_MS = 6500;

let timer = null;

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
		/** aria-label for the pause/play control, reflecting the current intent. */
		get playLabel() {
			const context = getContext();

			return context.isPlaying ? context.pauseLabel : context.resumeLabel;
		},
	},
	actions: {
		goTo() {
			const context = getContext();
			// Nested per-dot context carries `index`; the shared root context carries activeIndex.
			context.activeIndex = context.index;
			actions.stopAutoplay();
		},

		next() {
			const context = getContext();
			context.activeIndex = ( context.activeIndex + 1 ) % context.count;
			actions.stopAutoplay();
		},

		prev() {
			const context = getContext();
			context.activeIndex =
				( context.activeIndex - 1 + context.count ) % context.count;
			actions.stopAutoplay();
		},

		togglePlay() {
			const context = getContext();
			context.isPlaying = ! context.isPlaying;

			if ( context.isPlaying ) {
				actions.startAutoplay();
			} else {
				stopTimer();
			}
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
				context.activeIndex =
					( context.activeIndex + 1 ) % context.count;
			}, AUTO_ADVANCE_MS );
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

		playPressed() {
			return ! getContext().isPlaying;
		},
	},
} );
