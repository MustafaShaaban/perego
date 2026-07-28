/**
 * Live-canvas markup for perego-theme/project-navigation (spec 021 C10; DECISIONS 2026-07-22).
 *
 * Renders the REAL adjacent/related surfaces — the same tag + class skeleton `ProjectNavigationRenderer`
 * emits: the `.pagination` prev/next nav (`adjacent`) and the "Related projects" heading + `.blog-grid` of
 * `.post-card`s + the closing `.btn--accent` CTA (`related`) — so the editor canvas shows the actual surfaces,
 * styled by the theme's `main.css` via `add_editor_style`.
 *
 * This block had **no editor script at all**, so FSE rendered "Your site doesn't include support for this
 * block" on the single-project template — worse than the bare-sentence blocks. It is a **locked** preview:
 * the block's one attribute (`surface`) picks which of the two surfaces to render, and everything else is
 * queried, so there are no content controls; the copy is edited on the Project posts themselves.
 *
 * **The related cards are a design preview.** `ProjectNavigationRenderer` fills them from
 * `ProjectRepository::relatedFor()` — same-category work in the current language, topped up with other recent
 * projects when a category holds fewer than three. Reproducing that query in editor JS would duplicate the
 * repository and drift from it, so the canvas shows recent published projects instead, which exercises the
 * identical card structure. Same precedent as the services-teaser seed cards (DECISIONS 2026-07-22 C4).
 *
 * The skeletons are pure so `parity.test.js` can render them to strings and assert them against fixtures of
 * the PHP output; per `../../Editor/parity.js` only tags, class hooks, and nesting are compared, so the
 * renderer's inline `style` attributes and volatile href/src values are ignored.
 */

/** Mirror of `PortfolioContent` PROJECT.en — the English canvas labels. */
export const SEED_LABELS = {
	prev: 'Previous project',
	next: 'Next project',
	relatedTitle: 'Related projects',
	ctaButton: 'Start a Project',
	sectionLabel: 'Our Work',
};

/** How many cards the related grid previews — the renderer's own `relatedFor()` limit. */
export const RELATED_CARD_COUNT = 3;

/**
 * The prev/next nav. The renderer emits an empty `<span>` in place of a missing neighbour so the two
 * links stay at opposite ends of the row; the canvas reproduces that.
 *
 * @param {Object}  props
 * @param {boolean} props.hasPrevious Whether an older project exists.
 * @param {boolean} props.hasNext     Whether a newer project exists.
 */
export function ProjectAdjacentSkeleton( { hasPrevious = true, hasNext = true } ) {
	return (
		<nav className="pagination" aria-label={ SEED_LABELS.sectionLabel }>
			{ hasPrevious ? <a rel="prev" href="#">{ `‹ ${ SEED_LABELS.prev }` }</a> : <span></span> }
			{ hasNext ? <a rel="next" href="#">{ `${ SEED_LABELS.next } ›` }</a> : <span></span> }
		</nav>
	);
}

/**
 * The related-projects surface: heading, card grid, closing CTA.
 *
 * @param {Object}        props
 * @param {Array<Object>} props.cards `{ id, title, categoryLabel, excerpt, thumbUrl, thumbAlt }`.
 */
export function ProjectRelatedSkeleton( { cards = [] } ) {
	return (
		<>
			<h2 className="section-title">{ SEED_LABELS.relatedTitle }</h2>
			<div className="blog-grid">
				{ cards.map( ( card ) => (
					<a className="post-card" href="#" key={ card.id }>
						{ /* The renderer omits the media wrapper entirely when a project has no thumbnail. */ }
						{ card.thumbUrl ? (
							<div className="post-card__media">
								<img src={ card.thumbUrl } alt={ card.thumbAlt } loading="lazy" />
							</div>
						) : null }
						<div className="post-card__body">
							<span className="post-card__cat">{ card.categoryLabel }</span>
							<h3 className="post-card__title">{ card.title }</h3>
							<p className="post-card__excerpt">{ card.excerpt }</p>
						</div>
					</a>
				) ) }
			</div>
			<div>
				<a className="btn btn--accent" href="#">{ SEED_LABELS.ctaButton }</a>
			</div>
		</>
	);
}

/**
 * Placeholder cards, so the shared template (where no project is queried) still previews the grid rhythm.
 *
 * @return {Array<Object>} `RELATED_CARD_COUNT` cards with placeholder copy and no thumbnail.
 */
export function placeholderCards() {
	return Array.from( { length: RELATED_CARD_COUNT }, ( _, index ) => ( {
		id: `placeholder-${ index }`,
		title: 'Project title',
		categoryLabel: 'Category',
		excerpt: 'Client · Year',
		thumbUrl: '',
		thumbAlt: '',
	} ) );
}
