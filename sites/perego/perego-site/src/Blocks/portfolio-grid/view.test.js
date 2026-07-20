/**
 * Jest — perego/portfolio-grid front-end (spec 003 / M3, spec 020 follow-up). The grid is server-
 * rendered; view.js does the client-side service filter + numbered pagination in plain DOM. These
 * tests build the rendered markup in jsdom, run initPortfolioGrid(), then drive real click events and
 * assert observable outcomes: which cards are hidden, the active chip, the pager state, and the
 * no-results message.
 */
import { initPortfolioGrid } from './view.js';

/**
 * Build the section markup PortfolioGridRenderer emits: filter chips, the grid of cards, the
 * no-results message, and (when >1 page) the numbered pager. Returns handles for assertions.
 */
function buildSection( { categories, filters = [ 'all', 'video', 'design' ], perPage = 9 } ) {
	const chips = filters
		.map(
			( slug ) =>
				`<button class="web-filter portfolio-filter${
					slug === 'all' ? ' is-active' : ''
				}" data-filter="${ slug }" aria-pressed="${ slug === 'all' }">${ slug }</button>`
		)
		.join( '' );

	const cards = categories
		.map(
			( category, i ) =>
				`<a class="post-card reveal" href="/work/p-${ i }" data-category="${ category }"></a>`
		)
		.join( '' );

	const maxPages = Math.max( 1, Math.ceil( categories.length / perPage ) );
	const pageButtons = Array.from(
		{ length: maxPages },
		( _, i ) => `<button data-page="${ i + 1 }">${ i + 1 }</button>`
	).join( '' );
	const pager =
		maxPages > 1
			? `<nav class="pagination" hidden>
					<button class="pagination__prev">&#8249;</button>
					${ pageButtons }
					<button class="pagination__next">&#8250;</button>
				</nav>`
			: '';

	document.body.innerHTML = `
		<section class="page-section">
			<div class="container">
				<div class="portfolio-filters">${ chips }</div>
				<div class="blog-grid" id="portfolioGrid" data-per-page="${ perPage }">${ cards }</div>
				<p id="portfolioEmpty" hidden>No projects in this category yet.</p>
				${ pager }
			</div>
		</section>`;

	initPortfolioGrid();

	const grid = document.getElementById( 'portfolioGrid' );
	const allCards = Array.from( grid.querySelectorAll( '.post-card' ) );

	return {
		cards: allCards,
		chip: ( slug ) => document.querySelector( `.portfolio-filter[data-filter="${ slug }"]` ),
		pageButton: ( n ) => document.querySelector( `.pagination button[data-page="${ n }"]` ),
		prev: () => document.querySelector( '.pagination__prev' ),
		next: () => document.querySelector( '.pagination__next' ),
		pager: () => document.querySelector( '.pagination' ),
		empty: () => document.getElementById( 'portfolioEmpty' ),
		visible: () => allCards.filter( ( c ) => ! c.hidden ),
		visibleCategories: () =>
			allCards.filter( ( c ) => ! c.hidden ).map( ( c ) => c.dataset.category ),
	};
}

afterEach( () => {
	document.body.innerHTML = '';
} );

describe( 'service filter', () => {
	test( 'shows only cards matching the clicked chip', () => {
		const s = buildSection( { categories: [ 'video', 'design', 'video', 'design' ] } );

		s.chip( 'video' ).click();

		expect( s.visibleCategories() ).toEqual( [ 'video', 'video' ] );
		expect( s.chip( 'video' ).classList.contains( 'is-active' ) ).toBe( true );
		expect( s.chip( 'video' ).getAttribute( 'aria-pressed' ) ).toBe( 'true' );
		expect( s.chip( 'all' ).classList.contains( 'is-active' ) ).toBe( false );
	} );

	test( 'the "all" chip restores every card', () => {
		const s = buildSection( { categories: [ 'video', 'design' ] } );

		s.chip( 'video' ).click();
		s.chip( 'all' ).click();

		expect( s.visible() ).toHaveLength( 2 );
	} );

	test( 'shows the no-results message for a filter with no projects', () => {
		const s = buildSection( { categories: [ 'video', 'design' ], filters: [ 'all', 'video', 'web' ] } );

		s.chip( 'web' ).click(); // no card has category 'web'

		expect( s.empty().hidden ).toBe( false );
		expect( s.visible() ).toHaveLength( 0 );
	} );
} );

describe( 'pagination', () => {
	test( 'shows only the first page on load and reveals the pager', () => {
		const s = buildSection( { categories: Array( 12 ).fill( 'video' ), perPage: 5 } );

		expect( s.visible() ).toHaveLength( 5 );
		expect( s.pager().hidden ).toBe( false );
		expect( s.pageButton( 1 ).classList.contains( 'is-current' ) ).toBe( true );
		expect( s.prev().disabled ).toBe( true );
		expect( s.next().disabled ).toBe( false );
	} );

	test( 'clicking a page number shows that page window', () => {
		const s = buildSection( { categories: Array( 12 ).fill( 'video' ), perPage: 5 } );

		s.pageButton( 3 ).click(); // last page: cards 11..12 (2 cards)

		expect( s.visible() ).toHaveLength( 2 );
		expect( s.pageButton( 3 ).getAttribute( 'aria-current' ) ).toBe( 'page' );
		expect( s.next().disabled ).toBe( true );
		expect( s.prev().disabled ).toBe( false );
	} );

	test( 'prev/next step through pages within bounds', () => {
		const s = buildSection( { categories: Array( 12 ).fill( 'video' ), perPage: 5 } );

		s.next().click();
		expect( s.pageButton( 2 ).classList.contains( 'is-current' ) ).toBe( true );

		s.prev().click();
		expect( s.pageButton( 1 ).classList.contains( 'is-current' ) ).toBe( true );

		s.prev().click(); // clamped at page 1
		expect( s.pageButton( 1 ).classList.contains( 'is-current' ) ).toBe( true );
	} );

	test( 'changing the filter resets to page 1 and repaginates the filtered set', () => {
		// 10 video + 3 design; perPage 5 → video spans 2 pages, design fits on 1.
		const categories = [ ...Array( 10 ).fill( 'video' ), ...Array( 3 ).fill( 'design' ) ];
		const s = buildSection( { categories, perPage: 5 } );

		s.next().click(); // go to video page 2
		s.chip( 'design' ).click();

		expect( s.visibleCategories() ).toEqual( [ 'design', 'design', 'design' ] );
		expect( s.pager().hidden ).toBe( true ); // design fits on one page
	} );

	test( 'page numbers beyond the filtered page count are hidden', () => {
		// 12 video + 3 design; perPage 5 → 3 pages max; filtering to design → 1 page.
		const categories = [ ...Array( 12 ).fill( 'video' ), ...Array( 3 ).fill( 'design' ) ];
		const s = buildSection( { categories, perPage: 5 } );

		s.chip( 'design' ).click();

		expect( s.pageButton( 2 ).hidden ).toBe( true );
		expect( s.pageButton( 3 ).hidden ).toBe( true );
	} );
} );

describe( 'safety', () => {
	test( 'no-ops when there is no portfolio grid on the page', () => {
		document.body.innerHTML = '<div>nothing here</div>';
		expect( () => initPortfolioGrid() ).not.toThrow();
	} );
} );
