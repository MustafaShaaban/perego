/**
 * Front-end behaviour for the service pages' work sections, ported from the handoff's main.js:
 * the Selected-work masonry's "Load more" reveal (main.js loadMore IIFE) and the Website-Making
 * showcase's category filter (main.js webFilter IIFE). Each binder no-ops when its root is absent,
 * so the one module serves both the three masonry singles and the web-showcase single.
 */

function bindLoadMore() {
	const btn = document.getElementById( 'loadMore' );
	const grid = document.getElementById( 'workMore' );

	if ( ! btn || ! grid ) return;

	btn.addEventListener( 'click', () => {
		grid.hidden = false;
		btn.style.display = 'none';

		// Trigger the reveal animation on the newly shown cards.
		grid.querySelectorAll( '.reveal' ).forEach( ( el ) => {
			requestAnimationFrame( () => el.classList.add( 'is-visible' ) );
		} );
	} );
}

function bindWebFilter() {
	const grid = document.getElementById( 'webGrid' );

	if ( ! grid ) return;

	const filters = Array.from( document.querySelectorAll( '.web-filter' ) );
	const cards = Array.from( grid.querySelectorAll( '.web-card' ) );

	filters.forEach( ( btn ) => {
		btn.addEventListener( 'click', () => {
			filters.forEach( ( other ) => other.classList.remove( 'is-active' ) );
			btn.classList.add( 'is-active' );

			const filter = btn.getAttribute( 'data-filter' );

			cards.forEach( ( card ) => {
				const show = filter === 'all' || card.getAttribute( 'data-category' ) === filter;

				if ( show ) {
					card.classList.remove( 'is-hidden' );
					card.classList.add( 'is-filtering' );
					requestAnimationFrame( () => card.classList.remove( 'is-filtering' ) );
				} else {
					card.classList.add( 'is-hidden' );
				}
			} );
		} );
	} );
}

function init() {
	bindLoadMore();
	bindWebFilter();
}

if ( document.readyState === 'loading' ) document.addEventListener( 'DOMContentLoaded', init );
else init();
