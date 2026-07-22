/**
 * Live-canvas markup for perego-theme/home-about-bg (spec 021 C5 / T014; DECISIONS 2026-07-22).
 *
 * Renders the REAL home-about background — the same `div.home-about__bg > img` skeleton that
 * `HomeAboutBgRenderer::render()` emits — so the block editor canvas shows the actual full-bleed
 * background (styled by the theme's `main.css`, loaded via `add_editor_style`) instead of a
 * `ServerSideRender` iframe. The block is locked decorative chrome with no editable content: the About
 * section's editorial content is the adjacent native `wp:post-content`, which the editor already edits
 * directly (the "locked structural wrappers" of T014). `HomeAboutBgSkeleton` is pure so `parity.test.js`
 * can render it to a string and assert it against a fixture of the PHP output; per `../../Editor/parity.js`
 * only tags, class hooks, and nesting are compared (the `src` is volatile and ignored).
 */

/** A theme image URL. `src` is visual-only (parity ignores it). */
function themeImage( name ) {
	const origin = typeof window !== 'undefined' && window.location ? window.location.origin : '';
	return `${ origin }/wp-content/themes/perego-theme/assets/images/${ name }.png`;
}

/** The real, locked home-about background markup for the editor canvas. */
export function HomeAboutBgSkeleton() {
	return (
		<div className="home-about__bg" aria-hidden="true">
			<img src={ themeImage( 'about-hooded' ) } alt="" loading="lazy" />
		</div>
	);
}
