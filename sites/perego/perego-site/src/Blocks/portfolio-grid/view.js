/**
 * Front-end behaviour for the work/portfolio grid (spec 003 / M3, spec 020 follow-up): the service
 * filter + client-side numbered pagination. Plain DOM (like service-selected-work's bindWebFilter,
 * the handoff main.js port) rather than the Interactivity API — the block is server-rendered and this
 * avoids the directive-hydration fragility that silently wiped the filter chips on WP 7.0.2.
 *
 * Filtering and paging share one `matching()` set so the page count always reflects the filtered
 * cards; a filter change resets to page 1; the pager and no-results message hide themselves when not
 * needed. Each binder no-ops when the grid is absent, so the module is safe to load site-wide.
 */

function initPortfolioGrid() {
	const grid = document.getElementById( 'portfolioGrid' );

	if ( ! grid ) {
		return;
	}

	const scope = grid.closest( 'section' ) || document;
	const perPage = Math.max( 1, Number( grid.dataset.perPage ) || 9 );
	const filters = Array.from( scope.querySelectorAll( '.portfolio-filter' ) );
	const cards = Array.from( grid.querySelectorAll( '.post-card' ) );
	const present = new Set( cards.map( ( card ) => card.dataset.category ) );
	const emptyMessage = scope.querySelector( '#portfolioEmpty' );
	const pager = scope.querySelector( '.pagination' );
	const pageButtons = pager ? Array.from( pager.querySelectorAll( 'button[data-page]' ) ) : [];
	const prevButton = pager ? pager.querySelector( '.pagination__prev' ) : null;
	const nextButton = pager ? pager.querySelector( '.pagination__next' ) : null;

	let activeFilter = 'all';
	let page = 1;

	const matching = () =>
		cards.filter(
			( card ) => activeFilter === 'all' || card.dataset.category === activeFilter
		);

	const pageCount = () => Math.max( 1, Math.ceil( matching().length / perPage ) );

	function render() {
		const matched = matching();
		const totalPages = Math.max( 1, Math.ceil( matched.length / perPage ) );

		if ( page > totalPages ) {
			page = totalPages;
		}

		const start = ( page - 1 ) * perPage;

		cards.forEach( ( card ) => {
			const index = matched.indexOf( card );
			card.hidden = index < start || index >= start + perPage;
		} );

		filters.forEach( ( button ) => {
			const isActive = button.dataset.filter === activeFilter;
			button.classList.toggle( 'is-active', isActive );
			button.setAttribute( 'aria-pressed', isActive ? 'true' : 'false' );
		} );

		if ( emptyMessage ) {
			emptyMessage.hidden = activeFilter === 'all' || present.has( activeFilter );
		}

		if ( pager ) {
			pager.hidden = totalPages <= 1;
		}

		pageButtons.forEach( ( button ) => {
			const number = Number( button.dataset.page );
			button.hidden = number > totalPages;
			const isCurrent = number === page;
			button.classList.toggle( 'is-current', isCurrent );

			if ( isCurrent ) {
				button.setAttribute( 'aria-current', 'page' );
			} else {
				button.removeAttribute( 'aria-current' );
			}
		} );

		if ( prevButton ) {
			prevButton.disabled = page <= 1;
		}

		if ( nextButton ) {
			nextButton.disabled = page >= totalPages;
		}
	}

	filters.forEach( ( button ) =>
		button.addEventListener( 'click', () => {
			activeFilter = button.dataset.filter;
			page = 1;
			render();
		} )
	);

	pageButtons.forEach( ( button ) =>
		button.addEventListener( 'click', () => {
			page = Number( button.dataset.page );
			render();
		} )
	);

	if ( prevButton ) {
		prevButton.addEventListener( 'click', () => {
			if ( page > 1 ) {
				page -= 1;
				render();
			}
		} );
	}

	if ( nextButton ) {
		nextButton.addEventListener( 'click', () => {
			if ( page < pageCount() ) {
				page += 1;
				render();
			}
		} );
	}

	render();
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initPortfolioGrid );
} else {
	initPortfolioGrid();
}

// Exported for unit tests; the module self-initialises on load in the browser.
export { initPortfolioGrid };
