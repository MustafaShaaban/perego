/**
 * Live-canvas markup for perego-theme/post-reading-time (spec 021 C12; DECISIONS 2026-07-22).
 *
 * Renders the REAL reading-estimate span that `PostReadingTimeRenderer::render()` emits, so the editor
 * canvas shows the actual chip instead of a bare sentence.
 *
 * **Locked** preview with no controls: the estimate is computed from the post's own word count, and the
 * "%d min read" wording is a translation-catalogue format string, not editorial copy.
 */

/** Mirror of `GlobalContent::readTime()` (English) for a one-minute post. */
export const SEED_LABEL = '1 min read';

export function PostReadingTimeSkeleton( { label = SEED_LABEL } ) {
	return <span className="post-single__readtime">{ label }</span>;
}
