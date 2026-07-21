/**
 * Live-canvas markup for perego-theme/services-teaser (spec 021 C4 / T013; DECISIONS 2026-07-22).
 *
 * Renders the REAL front-end services-teaser structure — the same tag + class skeleton that
 * `ServicesTeaserRenderer::render()` emits — so the block editor canvas shows the actual section
 * (heading, "See All Services" link, and the four service cards), styled by the theme's `main.css`
 * (loaded into the canvas via `add_editor_style`), instead of a `ServerSideRender` iframe. Behavioural /
 * volatile attributes (`href`, `src`, `data-delay`, the reveal animation) are omitted or static; the
 * markup-parity test compares only tags, class hooks, and nesting (see `../../Editor/parity.js`).
 *
 * `ServicesTeaserSkeleton` is pure (no editor-store components) so `parity.test.js` can render it to a
 * string and assert it against a fixture of the PHP output. `edit()` (index.js) renders the SAME
 * component, passing `headingNode`/`seeAllNode` that add in-canvas `RichText` editing without changing
 * the skeleton — so what ships is what the test verified. The service cards are a design preview of the
 * seed set (their label/image are edited on each Service's own screen; which cards appear on the live
 * site follows the Inspector composer), so they render from the seed the same way the block already did.
 *
 * The seed heading / "See All" text and the seed cards mirror `PeregoSite\Content\HomeContent` (its PHP
 * arrays are the source of truth); parity compares only tags/classes/nesting, so the seed copy can never
 * break the test.
 */

export const SEED_HEADING_EN = 'Services we can help you with';
export const SEED_SEE_ALL_EN = 'See All Services';
export const SEED_HEADING_AR = 'خدمات يمكننا مساعدتك بها';
export const SEED_SEE_ALL_AR = 'عرض كل الخدمات';

/** The four seed service cards, in fixed order — mirror of `HomeContent::SERVICE_ORDER` + the EN names. */
export const SEED_CARDS = [
	{ slug: 'video-editing', name: 'Video Editing', image: 'card-video-editing', alt: 'Video editing timeline' },
	{ slug: 'motion-graphics', name: '2D Motion Graphics', image: 'card-motion-graphics', alt: '2D motion graphics workspace' },
	{ slug: 'graphic-design', name: 'Graphic Design', image: 'card-graphic-design', alt: 'Graphic design on a laptop' },
	{ slug: 'website-making', name: 'Website Making', image: 'card-website-making', alt: 'Website code editor' },
];

/** A theme image URL. `src` is visual-only (parity ignores it). */
function themeImage( name ) {
	const origin = typeof window !== 'undefined' && window.location ? window.location.origin : '';
	return `${ origin }/wp-content/themes/perego-theme/assets/images/${ name }.png`;
}

/** The "See All Services" arrow — mirrors `ServicesTeaserRenderer::arrowSvg()` (visual-only). */
const ArrowSvg = () => (
	<svg width="34" height="16" viewBox="0 0 34 16" fill="none" aria-hidden="true">
		<path d="M0 8h31M25 2l7 6-7 6" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" />
	</svg>
);

const ServiceCard = ( { card, index } ) => (
	<a className="service-card reveal" href="#" { ...( index > 0 ? { 'data-delay': index } : {} ) }>
		<img src={ themeImage( card.image ) } alt={ card.alt } loading="lazy" />
		<span className="service-card__overlay" />
		<span className="service-card__label">{ card.name }</span>
	</a>
);

/**
 * The real services-teaser markup for the editor canvas. `heading`/`seeAll` are the shown-locale head text
 * and `cards` the shown service cards; `headingNode`/`seeAllNode` optionally replace the heading text and
 * the "See All" label with in-canvas `RichText` widgets from `edit()` — they nest inside the real `h2` and
 * `a` (which keep their class hooks and the arrow), so the parity test, rendering this component with plain
 * defaults, still matches the PHP output.
 */
export function ServicesTeaserSkeleton( { heading, seeAll, cards = SEED_CARDS, headingNode, seeAllNode } ) {
	return (
		<section className="services-teaser" id="services" aria-labelledby="servicesTeaserTitle">
			<div className="wavy-bg" aria-hidden="true">
				<img src={ themeImage( 'wavy-corners' ) } alt="" />
			</div>
			<div className="container services-teaser__inner">
				<div className="services-teaser__head">
					<h2 className="services-teaser__title reveal" id="servicesTeaserTitle">
						{ headingNode || heading }
					</h2>
					<a className="link-arrow services-teaser__link reveal" data-delay="1" href="#">
						{ seeAllNode || seeAll }
						<ArrowSvg />
					</a>
				</div>
				<div className="service-cards">
					{ cards.map( ( card, index ) => (
						<ServiceCard key={ card.slug || index } card={ card } index={ index } />
					) ) }
				</div>
			</div>
		</section>
	);
}
