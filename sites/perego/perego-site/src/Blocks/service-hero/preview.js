/**
 * Live-canvas markup for perego-theme/service-hero (spec 021 C7 / T025; DECISIONS 2026-07-22).
 *
 * Renders the REAL service-single hero — the same tag + class skeleton that `ServiceHeroRenderer::render()`
 * emits (background, "Our Services" eyebrow, the service name as `h1`, and the shared four-service tab rail
 * with each tab's main link + "Start your project" CTA) — so the block editor canvas shows the actual hero,
 * styled by the theme's `main.css` via `add_editor_style`, instead of a `ServerSideRender` iframe.
 *
 * The block has no attributes of its own: the `h1` is the queried Service post's title and the tab labels come
 * from each Service's teaser label, falling back to the `ServiceContent` seed. So this is a **locked** visual
 * preview — there is nothing to edit in place; the copy is edited on each Service (its title field / teaser
 * label), exactly as the renderer resolves it. `ServiceHeroSkeleton` is pure so `parity.test.js` can render it
 * to a string and assert it against a fixture of the PHP output; per `../../Editor/parity.js` only tags, class
 * hooks, and nesting are compared (href/src and the `data-delay`/aria state are volatile and ignored).
 *
 * The seed eyebrow / names / "Start your project" label mirror `PeregoSite\Content\ServiceContent::COPY`
 * (its PHP array is the source of truth); parity compares structure only, so seed copy can never break it.
 */

export const SEED_EYEBROW_EN = 'Our Services';
export const SEED_EYEBROW_AR = 'خدماتنا';
export const SEED_START_LABEL_EN = 'Start your project';
export const SEED_START_LABEL_AR = 'ابدأ مشروعك';

/** The four services in fixed order — mirror of `ServiceContent::SLUG_KEY` + the locale's `names`. */
export const SEED_TABS_EN = [
	{ slug: 'video-editing', name: 'Video Editing', fullName: 'Video Editing & Post-Production' },
	{ slug: 'motion-graphics', name: '2D Motion Graphics', fullName: '2D Motion Graphics & Animation' },
	{ slug: 'graphic-design', name: 'Graphic Design', fullName: 'Graphic Design & Brand Identity' },
	{ slug: 'website-making', name: 'Website Making', fullName: 'Website Making' },
];

export const SEED_TABS_AR = [
	{ slug: 'video-editing', name: 'مونتاج الفيديو', fullName: 'مونتاج الفيديو وما بعد الإنتاج' },
	{ slug: 'motion-graphics', name: 'موشن جرافيك ثنائي الأبعاد', fullName: 'موشن جرافيك وأنيميشن ثنائي الأبعاد' },
	{ slug: 'graphic-design', name: 'التصميم الجرافيكي', fullName: 'التصميم الجرافيكي والهوية البصرية' },
	{ slug: 'website-making', name: 'إنشاء المواقع', fullName: 'إنشاء المواقع' },
];

/** A theme image URL. `src` is visual-only (parity ignores it). */
function themeImage( name ) {
	const origin = typeof window !== 'undefined' && window.location ? window.location.origin : '';
	return `${ origin }/wp-content/themes/perego-theme/assets/images/${ name }.png`;
}

/**
 * The real service-hero markup for the editor canvas. `title` is the shown `h1` (the Service post's title,
 * or the seed full name), `tabs` the shown-locale service tabs, and `activeSlug` the tab marked current.
 */
export function ServiceHeroSkeleton( {
	eyebrow = SEED_EYEBROW_EN,
	title,
	tabs = SEED_TABS_EN,
	activeSlug = SEED_TABS_EN[ 0 ].slug,
	startLabel = SEED_START_LABEL_EN,
} ) {
	return (
		<section className="svc-hero">
			<div className="svc-hero__bg" aria-hidden="true">
				<img src={ themeImage( 'svc-hero-bg' ) } alt="" />
			</div>
			<div className="container svc-hero__inner">
				<p className="svc-hero__eyebrow reveal">{ eyebrow }</p>
				<h1 className="svc-hero__title reveal" data-delay="1">{ title }</h1>
				<nav className="svc-tabs reveal" data-delay="1" aria-label={ eyebrow }>
					{ tabs.map( ( tab ) => {
						const isActive = tab.slug === activeSlug;
						return (
							<div key={ tab.slug } className={ isActive ? 'svc-tab is-active' : 'svc-tab' }>
								<a className="svc-tab__main" href="#" { ...( isActive ? { 'aria-current': 'page' } : {} ) }>
									<span className="svc-tab__label">{ tab.name }</span>
								</a>
								<a className="svc-tab__cta" href="#">{ startLabel }</a>
							</div>
						);
					} ) }
				</nav>
			</div>
		</section>
	);
}
