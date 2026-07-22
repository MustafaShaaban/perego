/**
 * Live-canvas markup for perego-theme/related-posts (spec 021 C12; DECISIONS 2026-07-22).
 *
 * Renders the REAL related-articles section — the same tag + class skeleton `RelatedPostsRenderer` emits
 * (the `.page-section.post-related` landmark, its `#relatedArticles` heading, and a `.blog-grid` of three
 * `.post-card.reveal` cards) — so the editor canvas shows the actual section instead of a bare sentence.
 *
 * **Locked** preview with no controls: which posts appear is a same-category query the renderer owns.
 * **The three cards are a design preview** — reproducing `RelatedPostsRenderer`'s query in editor JS
 * would duplicate it and drift from it, and what an editor needs from the canvas is where the section
 * sits and how a card is built. Same precedent as the project-navigation related grid (C10).
 */

/** Mirror of `GlobalContent` (English) — the canvas seed heading. */
export const SEED_TITLE = 'Related articles';

/** How many cards the grid previews — the renderer's own limit. */
export const RELATED_CARD_COUNT = 3;

/** Placeholder cards, used when the editor has no real posts to show yet. */
export function placeholderCards() {
	return Array.from( { length: RELATED_CARD_COUNT }, ( _, index ) => ( {
		id: `placeholder-${ index }`,
		title: 'Article title',
		categoryLabel: 'Category',
		meta: 'Date · 1 min read',
		thumbUrl: '',
	} ) );
}

export function RelatedPostsSkeleton( { title = SEED_TITLE, cards = placeholderCards() } ) {
	return (
		<section className="page-section post-related" aria-labelledby="relatedArticles">
			<div className="container">
				<h2 className="section-title" id="relatedArticles">{ title }</h2>
				<div className="blog-grid">
					{ cards.map( ( card ) => (
						<a className="post-card reveal" href="#" key={ card.id }>
							{ /* The renderer omits the media wrapper when a post has no thumbnail. */ }
							{ card.thumbUrl ? (
								<div className="post-card__media">
									<img src={ card.thumbUrl } alt="" loading="lazy" />
								</div>
							) : null }
							<div className="post-card__body">
								<span className="post-card__cat">{ card.categoryLabel }</span>
								<h3 className="post-card__title">{ card.title }</h3>
								<span className="post-card__meta">{ card.meta }</span>
							</div>
						</a>
					) ) }
				</div>
			</div>
		</section>
	);
}
