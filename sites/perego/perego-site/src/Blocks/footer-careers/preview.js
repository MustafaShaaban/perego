/**
 * Live-canvas markup for perego-theme/footer-careers (spec 021 C15; DECISIONS 2026-07-22).
 *
 * Renders the REAL footer careers column — the "Join us" heading and its blurb — the same skeleton
 * `FooterCareersRenderer::render()` emits.
 *
 * This block already had bilingual editing, but through **two stacked `<fieldset>`s** showing English
 * and Arabic side by side — an editor-only layout that looked nothing like the footer. It now follows
 * the spec 021 standard shared with every other block: the English copy is edited in place in the real
 * markup, and Arabic moves to the Inspector on `LanguagePair`.
 */

/** Mirror of `GlobalContent` careers copy (English). */
export const SEED = {
	heading: 'Join us',
	blurb: "We're constantly evolving. If you're ready to grow with us, share your CV and portfolio.",
};

export function FooterCareersSkeleton( { headingNode, blurbNode, heading, blurb } ) {
	return (
		<>
			<h2 className="wp-block-heading footer-heading">{ headingNode || heading || SEED.heading }</h2>
			<p className="footer-blurb wp-block-paragraph">{ blurbNode || blurb || SEED.blurb }</p>
		</>
	);
}
