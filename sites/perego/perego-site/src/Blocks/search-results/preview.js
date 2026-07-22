/**
 * Live-canvas markup for perego-theme/search-results (spec 021 C12; DECISIONS 2026-07-22).
 *
 * Renders the REAL search page — the `.post-hero__inner` head (breadcrumb, `#search-title` h1, result
 * count lead, and the `.search-bar` form) plus a `.blog-grid` of result cards — so the editor canvas
 * shows the actual page instead of a bare sentence.
 *
 * **Locked** preview with no controls: everything is derived from the visitor's `?s=` query.
 *
 * **Why the head and the results are separate exports.** The head is fixed markup, so `parity.test.js`
 * pins it against a live fixture. The results list is not: a real search renders as many cards as it
 * matched — the captured `?s=design` page has **25 cards / 11 KB** — so a fixture of it would pin
 * today's content, not the contract, and would fail the moment a post is published. The card structure
 * is therefore rendered from the same shape the head fixture's own cards use, and the canvas shows a
 * small sample. Same reasoning that kept portfolio-grid on `ServerSideRender` (C11).
 */

/** Mirror of `GlobalContent` search copy (English) — the canvas seed. */
export const SEED = {
	home: 'Home',
	title: 'Search results',
	placeholder: 'Search the site…',
	submit: 'Search',
	sampleQuery: 'design',
};

/** How many sample result cards the canvas shows — enough to read the grid rhythm, not a real count. */
export const SAMPLE_CARD_COUNT = 3;

export function sampleCards() {
	return Array.from( { length: SAMPLE_CARD_COUNT }, ( _, index ) => ( {
		id: `sample-${ index }`,
		title: 'Matching result',
		categoryLabel: 'Project',
		excerpt: 'A short excerpt from the matching content.',
		meta: 'July 19, 2026',
		thumbUrl: '',
	} ) );
}

/** The fixed head: breadcrumb, title, result-count lead, and the search form. */
export function SearchHeadSkeleton( { lead } ) {
	return (
		<div className="post-hero__inner">
			<nav className="page-crumb" aria-label="Breadcrumb">
				<a href="#">{ SEED.home }</a>
				<span aria-hidden="true">/</span>
				<span aria-current="page">{ SEED.title }</span>
			</nav>
			<h1 className="post-title" id="search-title">{ SEED.title }</h1>
			<p className="section-lead">{ lead || <>Showing results for <strong>{ SEED.sampleQuery }</strong> — 25 matches found</> }</p>
			<form className="search-bar" role="search" method="get" action="#">
				<svg viewBox="0 0 24 24" aria-hidden="true">
					<circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" strokeWidth="2" />
					<path d="m20 20-3.5-3.5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
				</svg>
				<label className="screen-reader-text" htmlFor="perego-search-field">{ SEED.title }</label>
				<input type="search" id="perego-search-field" name="s" defaultValue={ SEED.sampleQuery } placeholder={ SEED.placeholder } />
				<button type="submit" className="btn btn--accent">{ SEED.submit }</button>
			</form>
		</div>
	);
}

export function SearchResultsSkeleton( { cards = sampleCards() } ) {
	return (
		<section className="page-section" aria-labelledby="search-title">
			<div className="container">
				<SearchHeadSkeleton />
				<div className="blog-grid">
					{ cards.map( ( card ) => (
						<a className="post-card reveal" href="#" key={ card.id }>
							{ card.thumbUrl ? (
								<div className="post-card__media">
									<img src={ card.thumbUrl } alt="" loading="lazy" />
								</div>
							) : null }
							<div className="post-card__body">
								<span className="post-card__cat">{ card.categoryLabel }</span>
								<h3 className="post-card__title">{ card.title }</h3>
								<p className="post-card__excerpt">{ card.excerpt }</p>
								<span className="post-card__meta">{ card.meta }</span>
							</div>
						</a>
					) ) }
				</div>
			</div>
		</section>
	);
}
