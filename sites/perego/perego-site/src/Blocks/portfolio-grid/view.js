/**
 * perego/portfolio-grid Interactivity API store (spec 003 / M3). Client-side service filter for the
 * project grid: clicking a chip sets the active filter, cards whose category doesn't match hide, the
 * chips reflect the active state, and a no-results message shows when the chosen service has no
 * projects. Ported from the handoff main.js webFilter IIFE. See spec 003 US Portfolio.
 */
import { store, getContext } from '@wordpress/interactivity';

store( 'perego/portfolio-grid', {
	actions: {
		setFilter() {
			const context = getContext();
			// Nested per-chip context carries `filter`; the root context carries activeFilter.
			context.activeFilter = context.filter;
		},
	},
	callbacks: {
		filterPressed() {
			const context = getContext();

			return context.filter === context.activeFilter;
		},

		cardHidden() {
			const context = getContext();

			return (
				context.activeFilter !== 'all' &&
				context.category !== context.activeFilter
			);
		},

		noResultsHidden() {
			const context = getContext();

			// Hidden (no message) when showing everything, or when the active service has projects.
			return (
				context.activeFilter === 'all' ||
				context.present.includes( context.activeFilter )
			);
		},
	},
} );
