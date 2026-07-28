/**
 * Live-canvas markup for perego-theme/journal-header (spec 021 C12; DECISIONS 2026-07-22).
 *
 * Renders the REAL journal-archive header — the same tag + class skeleton `JournalHeaderRenderer::render()`
 * emits (the centred `.post-hero__inner` with its breadcrumb, `h1`, and lead paragraph) — so the editor
 * canvas shows the actual header, styled by the theme's `main.css` via `add_editor_style`. It replaces an
 * `edit()` that returned a bare sentence and showed the editor nothing.
 *
 * The copy comes from `GlobalContent::journal()`, and C12 makes the title and lead editable per locale
 * through the same seed-override seam C11 established (`journal( array $overrides )` +
 * `LocalizedAttributes::pick`), so an unedited block renders exactly what it always did. The breadcrumb is
 * a derived route link and stays underived — see DECISIONS 2026-07-22 (T036) for that boundary.
 */

/** Mirror of `GlobalContent::journal()` (English) — the canvas seed and the Inspector placeholders. */
export const SEED = {
	home: 'Home',
	title: 'The Perego Journal',
	lead: 'Notes on video, motion, design and the web from the studio floor.',
};

/**
 * The real journal-header markup for the editor canvas.
 *
 * @param {Object} props
 * @param {string} props.title    Archive title; empty falls back to the seed.
 * @param {string} props.lead     Lead paragraph; empty falls back to the seed.
 * @param {Object} props.titleNode Optional in-canvas RichText node for the title.
 * @param {Object} props.leadNode  Optional in-canvas RichText node for the lead.
 */
export function JournalHeaderSkeleton( { title, lead, titleNode, leadNode } ) {
	const heading = title || SEED.title;

	return (
		<div className="post-hero__inner">
			<nav className="page-crumb" aria-label="Breadcrumb">
				<a href="#">{ SEED.home }</a>
				<span aria-hidden="true">/</span>
				<span aria-current="page">{ heading }</span>
			</nav>
			<h1 className="post-title">{ titleNode || heading }</h1>
			<p className="section-lead">{ leadNode || lead || SEED.lead }</p>
		</div>
	);
}
