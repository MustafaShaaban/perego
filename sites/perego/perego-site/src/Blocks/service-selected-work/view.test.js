function loadView() {
	jest.resetModules();
	require( './view.js' );
}

describe( 'Load more (Selected-work masonry)', () => {
	beforeEach( () => {
		document.body.innerHTML = `
			<section class="portfolio page-section"><div class="container">
				<div class="work-masonry"><button class="work-card m1 reveal" data-image="a.jpg"></button></div>
				<div class="work-more-grid" id="workMore" hidden>
					<button class="work-card reveal" data-image="b.jpg"></button>
					<button class="work-card reveal" data-image="c.jpg" data-delay="1"></button>
				</div>
				<div class="load-more-wrap"><button id="loadMore" class="btn btn--dark load-more-btn">Load more</button></div>
			</div></section>`;
	} );

	test( 'reveals the hidden overflow grid, hides the button, and animates the new cards in', () => {
		const raf = jest.spyOn( window, 'requestAnimationFrame' ).mockImplementation( ( cb ) => cb( 0 ) );
		loadView();

		const grid = document.getElementById( 'workMore' );
		const btn = document.getElementById( 'loadMore' );

		expect( grid.hidden ).toBe( true );

		btn.click();

		expect( grid.hidden ).toBe( false );
		expect( btn.style.display ).toBe( 'none' );
		grid.querySelectorAll( '.reveal' ).forEach( ( card ) => {
			expect( card.classList.contains( 'is-visible' ) ).toBe( true );
		} );

		raf.mockRestore();
	} );

	test( 'no-ops without the load-more root (the three-project page has no overflow)', () => {
		document.body.innerHTML = '<div class="work-masonry"></div>';

		expect( () => loadView() ).not.toThrow();
	} );
} );

describe( 'Web-showcase category filter (Website Making page)', () => {
	beforeEach( () => {
		document.body.innerHTML = `
			<section class="web-showcase page-section"><div class="container">
				<div class="web-filters" role="group">
					<button class="web-filter is-active" data-filter="all">All</button>
					<button class="web-filter" data-filter="ecommerce">E-Commerce</button>
					<button class="web-filter" data-filter="landing">Landing Page</button>
				</div>
				<div class="web-grid" id="webGrid">
					<article class="web-card" data-category="ecommerce"></article>
					<article class="web-card" data-category="landing"></article>
					<article class="web-card" data-category="ecommerce"></article>
				</div>
			</div></section>`;
	} );

	test( 'isolates the picked category and moves the active pill', () => {
		const raf = jest.spyOn( window, 'requestAnimationFrame' ).mockImplementation( ( cb ) => cb( 0 ) );
		loadView();

		const [ all, ecommerce ] = document.querySelectorAll( '.web-filter' );
		const cards = document.querySelectorAll( '.web-card' );

		ecommerce.click();

		expect( ecommerce.classList.contains( 'is-active' ) ).toBe( true );
		expect( all.classList.contains( 'is-active' ) ).toBe( false );
		expect( cards[ 0 ].classList.contains( 'is-hidden' ) ).toBe( false );
		expect( cards[ 1 ].classList.contains( 'is-hidden' ) ).toBe( true );
		expect( cards[ 2 ].classList.contains( 'is-hidden' ) ).toBe( false );

		all.click();

		expect( all.classList.contains( 'is-active' ) ).toBe( true );
		document.querySelectorAll( '.web-card' ).forEach( ( card ) => {
			expect( card.classList.contains( 'is-hidden' ) ).toBe( false );
		} );

		raf.mockRestore();
	} );

	test( 'no-ops without a #webGrid root (the masonry singles)', () => {
		document.body.innerHTML = '<div class="work-masonry"></div>';

		expect( () => loadView() ).not.toThrow();
	} );
} );
