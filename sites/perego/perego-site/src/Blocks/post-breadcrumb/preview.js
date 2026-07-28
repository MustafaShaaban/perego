/**
 * Live-canvas markup for perego-theme/post-breadcrumb (spec 021 C12; DECISIONS 2026-07-22).
 *
 * Renders the REAL breadcrumb — the same tag + class skeleton `PostBreadcrumbRenderer::render()` emits —
 * so the editor canvas shows the actual trail instead of a bare sentence.
 *
 * This is a **locked** preview with no controls: every part is derived. The Home and Journal steps are
 * route links (deliberately not editable — see DECISIONS 2026-07-22 T036), and the last step is the
 * post's own title. The Journal step is omitted on a page, exactly as the renderer omits it.
 */

/** Mirror of `GlobalContent` (English) — the canvas seed labels. */
export const SEED = { home: 'Home', journal: 'The Perego Journal' };

/**
 * @param {Object}  props
 * @param {string}  props.title    The current post's title.
 * @param {boolean} props.isPage   A page skips the Journal step, matching the renderer.
 */
export function PostBreadcrumbSkeleton( { title = 'Post title', isPage = false } ) {
	return (
		<nav className="page-crumb" aria-label="Breadcrumb">
			<a href="#">{ SEED.home }</a>
			<span aria-hidden="true">/</span>
			{ ! isPage && (
				<>
					<a href="#">{ SEED.journal }</a>
					<span aria-hidden="true">/</span>
				</>
			) }
			<span aria-current="page">{ title }</span>
		</nav>
	);
}
