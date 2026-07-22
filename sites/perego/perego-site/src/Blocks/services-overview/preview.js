/**
 * Live-canvas markup for perego-theme/services-overview (spec 021 C14; DECISIONS 2026-07-22).
 *
 * Renders the REAL services-overview composition — the same tag + class skeleton
 * `ServicesOverviewRenderer::render()` emits: the `.svc-hero` with its four service tabs, the
 * `.svc-whatwedo` two-column intro, the `.process` step rail (steps interleaved with arrow items),
 * the selected-work masonry, and the closing CTA — so the editor canvas shows the actual page
 * instead of a bare sentence.
 *
 * **Locked** preview with no controls: the tabs are a projection of the four Services (each label is
 * edited on that Service's own screen) and the prose comes from `ServiceContent`.
 *
 * **The four fixed sections are exported separately from the selected-work masonry, on purpose.**
 * The hero, intro, process rail, and CTA are fixed markup, so `parity.test.js` pins them against a
 * live fixture. The masonry is not: it renders one tile per project (23 on this install, 9 KB), so a
 * fixture of it would pin today's content rather than the contract. Same split as search-results (C12).
 */

import { Fragment } from '@wordpress/element';

/** Mirror of `ServiceContent` (English) — the canvas seed. */
export const SEED = {
	title: 'Our Services',
	tabsLabel: 'Our Services',
	tabCta: 'Start your project',
	whatWeDoTitle: 'One studio, four services',
	whatWeDoKicker: 'Video · Motion · Design · Web',
	processTitle: 'Our Process',
	selectedWorkTitle: 'Selected work',
	ctaTitle: 'Have a project in mind?',
	ctaButton: 'Start a Project',
};

export const SEED_TABS = [
	{ slug: 'video-editing', label: 'Video Editing' },
	{ slug: 'motion-graphics', label: '2D Motion Graphics' },
	{ slug: 'graphic-design', label: 'Graphic Design' },
	{ slug: 'website-making', label: 'Website Making' },
];

export const SEED_STEPS = [
	{ label: 'Discover', desc: 'We learn your brand, goals, and audience.' },
	{ label: 'Concept', desc: 'We shape the idea, direction, and plan.' },
	{ label: 'Create', desc: 'We produce the work with an obsessive standard for craft.' },
	{ label: 'Deliver', desc: 'We refine with your feedback and deliver on time.' },
];

/** How many masonry tiles the canvas samples — the live page renders one per project. */
export const SAMPLE_TILE_COUNT = 4;

function ProcessArrow() {
	return (
		<li className="process-arrow" aria-hidden="true">
			<svg viewBox="0 0 24 24">
				<path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" strokeWidth="2.6"
					strokeLinecap="round" strokeLinejoin="round" />
			</svg>
		</li>
	);
}

/** The hero, intro, process rail and closing CTA — the sections whose markup is fixed. */
export function ServicesOverviewFixedSkeleton( { tabs = SEED_TABS, steps = SEED_STEPS } ) {
	return (
		<section className="services-overview">
			<section className="svc-hero" aria-labelledby="services-overview-title">
				<div className="svc-hero__bg" aria-hidden="true"><img src="" alt="" /></div>
				<div className="svc-hero__inner">
					<h1 className="svc-hero__title" id="services-overview-title">{ SEED.title }</h1>
					<nav className="svc-tabs" aria-label={ SEED.tabsLabel }>
						{ tabs.map( ( tab ) => (
							<div className="svc-tab" key={ tab.slug }>
								<a className="svc-tab__main" href="#">
									<span className="svc-tab__label">{ tab.label }</span>
								</a>
								<a className="svc-tab__cta" href="#">{ SEED.tabCta }</a>
							</div>
						) ) }
					</nav>
				</div>
			</section>

			<section className="svc-whatwedo">
				<div className="svc-whatwedo__grid">
					<div className="svc-whatwedo__text">
						<h2 className="wp-block-heading">{ SEED.whatWeDoTitle }</h2>
						<p><strong>{ SEED.whatWeDoKicker }</strong></p>
						<p>Perego is a creative agency specialising in advertising and digital production.</p>
						<p>We bring video editing, motion graphics, design and website making under one roof.</p>
					</div>
					<div className="svc-whatwedo__media"><img src="" alt={ SEED.title } loading="lazy" /></div>
				</div>
			</section>

			<section className="process" aria-labelledby="services-overview-process">
				<div className="container">
					<h2 className="section-title reveal" id="services-overview-process">{ SEED.processTitle }</h2>
					<ol className="process-list">
						{ steps.map( ( step, index ) => (
							// The renderer interleaves an arrow between steps, never after the last.
							<Fragment key={ step.label }>
								<li className="process-step reveal">
									<span className="process-step__icon"><img src="" alt="" /></span>
									<span className="process-step__label">{ step.label }</span>
									<span className="process-step__desc">{ step.desc }</span>
								</li>
								{ index < steps.length - 1 && <ProcessArrow /> }
							</Fragment>
						) ) }
					</ol>
				</div>
			</section>
		</section>
	);
}

/** The selected-work masonry — a sample, not a pinned fixture. See the file docblock. */
export function ServicesOverviewWorkSkeleton( { tiles = SAMPLE_TILE_COUNT } ) {
	return (
		<section className="portfolio page-section" aria-labelledby="services-overview-selected-work">
			<div className="container">
				<h2 className="section-title reveal" id="services-overview-selected-work">{ SEED.selectedWorkTitle }</h2>
				<div className="work-masonry">
					{ Array.from( { length: tiles }, ( _, index ) => (
						<button type="button" className="work-card m1 reveal" key={ index } aria-label={ SEED.selectedWorkTitle }>
							<img src="" alt="" loading="lazy" />
							<span className="work-card__overlay"></span>
							<span className="play-btn" aria-hidden="true"></span>
						</button>
					) ) }
				</div>
			</div>
		</section>
	);
}

/** The closing CTA. */
export function ServicesOverviewCtaSkeleton() {
	return (
		<section className="services-overview__cta">
			<div className="services-overview__cta-inner">
				<h2 className="wp-block-heading">{ SEED.ctaTitle }</h2>
				<p>Tell us what you&rsquo;re working on and we&rsquo;ll help you shape the plan.</p>
				<a className="perego-btn perego-btn--accent" href="#">{ SEED.ctaButton }</a>
			</div>
		</section>
	);
}
