/**
 * Live-canvas markup for perego-theme/site-header (spec 021 C1 / T007; DECISIONS 2026-07-21).
 *
 * This renders the REAL front-end header structure — the same tag + class skeleton that
 * `SiteHeaderRenderer::render()` emits — so the block editor canvas shows the actual Perego header
 * (styled by the theme's `main.css`, loaded into the canvas via `add_editor_style`) instead of a
 * `ServerSideRender` iframe. Behavioural attributes present on the front end (`data-wp-*`, the
 * Interactivity context, aria state) are intentionally omitted: the editor preview is static, and the
 * markup-parity test compares only tags, class hooks, and nesting (see `../../Editor/parity.js`).
 *
 * `HeaderSkeleton` is deliberately pure (no editor-store components) so `parity.test.js` can render it
 * to a string and assert it against a fixture of the PHP output. `edit()` (index.js) renders the SAME
 * component, passing structurally identical `logo`/`cta` nodes that add in-canvas editing (a clickable
 * logo and a `RichText` CTA) without changing the skeleton — so what ships is what the test verified.
 */
import { __ } from '@wordpress/i18n';

/**
 * The seed nav shown until an editor sets `navItemsEn`/`navItemsAr`. Mirrors
 * `SiteHeaderRenderer::seedNavItems()` (labels differ per locale), so the default editor canvas matches
 * the default front-end header. Shared by `index.js` and the markup-parity test.
 *
 * **Hrefs must mirror the PHP seed too**, even though the parity test ignores them: the first Site
 * Editor save writes these values into the header template part, and from then on they are the site's
 * real nav. That is how "Contact Us" came to point at `/contact` — this seed said so while the PHP seed
 * said `#contact` (the footer anchor the handoff intends, present on every page). See
 * scripts/migrate-header-contact-anchor.php for the matching data fix.
 */
export const SEED_EN = [
	{ label: 'Home', href: '/' },
	{ label: 'About Us', href: '/#about' },
	{
		label: 'Services', href: '/#services',
		children: [
			{ label: 'Video Editing', href: '/services/video-editing' },
			{ label: '2D Motion Graphics', href: '/services/motion-graphics' },
			{ label: 'Graphic Design', href: '/services/graphic-design' },
			{ label: 'Website Making', href: '/services/website-making' },
		],
	},
	{ label: 'Work', href: '/work' },
	{ label: 'Journal', href: '/journal' },
	{ label: 'Clients', href: '/#clients' },
	{ label: 'Contact Us', href: '#contact' },
];

export const SEED_AR = [
	{ label: 'الرئيسية', href: '/' },
	{ label: 'من نحن', href: '/#about' },
	{
		label: 'الخدمات', href: '/#services',
		children: [
			{ label: 'مونتاج الفيديو', href: '/services/video-editing' },
			{ label: 'موشن جرافيك ثنائي الأبعاد', href: '/services/motion-graphics' },
			{ label: 'تصميم جرافيك', href: '/services/graphic-design' },
			{ label: 'صناعة المواقع', href: '/services/website-making' },
		],
	},
	{ label: 'أعمالنا', href: '/work' },
	{ label: 'المدونة', href: '/journal' },
	{ label: 'العملاء', href: '/#clients' },
	{ label: 'تواصل معنا', href: '#contact' },
];

/** Parse a stored nav-items JSON string, falling back to `seed` when empty or invalid. */
export function parseNavItems( raw, seed ) {
	if ( ! raw ) {
		return seed;
	}
	try {
		const parsed = JSON.parse( raw );
		return Array.isArray( parsed ) && parsed.length ? parsed : seed;
	} catch ( e ) {
		return seed;
	}
}

/** The theme's shipped logo, used when no `logoId` is set. `src` is visual-only (parity ignores it). */
export function defaultLogoUrl() {
	const origin = typeof window !== 'undefined' && window.location ? window.location.origin : '';
	return `${ origin }/wp-content/themes/perego-theme/assets/images/logo-full.png`;
}

const NavCaret = () => (
	<svg className="nav-caret" width="12" height="8" viewBox="0 0 12 8" aria-hidden="true">
		<path d="M1 1l5 5 5-5" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
	</svg>
);

const NavList = ( { items } ) => (
	<ul className="main-nav__list">
		{ items.map( ( item, index ) => {
			const hasChildren = Array.isArray( item.children ) && item.children.length > 0;
			return (
				<li key={ index } className={ hasChildren ? 'has-dropdown' : undefined }>
					<a className="main-nav__link" href={ item.href || '#' }
						{ ...( hasChildren ? { 'aria-haspopup': 'true', 'aria-expanded': 'false' } : {} ) }>
						{ item.label }
						{ hasChildren ? <>{ ' ' }<NavCaret /></> : null }
					</a>
					{ hasChildren && (
						<ul className="dropdown">
							{ item.children.map( ( child, childIndex ) => (
								<li key={ childIndex }><a href={ child.href || '#' }>{ child.label }</a></li>
							) ) }
						</ul>
					) }
				</li>
			);
		} ) }
	</ul>
);

/** The static AR/EN switcher as the front end renders it for the (editor-primary) English locale. */
const LanguageToggle = () => (
	<div className="lang-toggle" role="group" aria-label={ __( 'Language', 'perego-site' ) }>
		<a className="lang-toggle__btn" href="#" lang="ar">AR</a>
		<span className="lang-toggle__btn is-active" aria-current="true" lang="en">EN</span>
	</div>
);

/**
 * The real header markup for the editor canvas. `navItems` are the shown-locale nav entries; `logo` and
 * `cta` optionally override the logo and CTA anchors with in-canvas editing widgets from `edit()` — they
 * must keep the `a.logo > img.logo__img` and `a.btn.btn--accent.header-cta` skeletons, which the parity
 * test pins by rendering this component with the plain defaults.
 */
export function HeaderSkeleton( { navItems, logoUrl, ctaLabel, logo, cta } ) {
	return (
		<header className="site-header">
			<div className="container site-header__inner">
				{ logo || (
					<a className="logo" href="#" aria-label={ __( 'Perego — home', 'perego-site' ) }>
						<img src={ logoUrl || defaultLogoUrl() } alt="" className="logo__img" />
					</a>
				) }
				<nav className="main-nav" aria-label={ __( 'Primary', 'perego-site' ) }>
					{ /* Panel-only logo + CTA — CSS reveals these only below the nav breakpoint. */ }
					<div className="main-nav__mobile-head" aria-hidden="true">
						<img src={ logoUrl || defaultLogoUrl() } alt="" className="logo__img" />
					</div>
					<NavList items={ navItems } />
					<div className="main-nav__mobile-cta">
						<a className="btn btn--accent header-cta" href="#">{ ctaLabel }</a>
					</div>
				</nav>
				{ cta || (
					<a className="btn btn--accent header-cta" href="#">{ ctaLabel }</a>
				) }
				<LanguageToggle />
				<button type="button" className="nav-toggle" aria-label={ __( 'Menu', 'perego-site' ) } aria-expanded="false">
					<span><span className="screen-reader-text">{ __( 'Menu', 'perego-site' ) }</span></span>
				</button>
			</div>
		</header>
	);
}
