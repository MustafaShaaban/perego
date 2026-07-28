/**
 * Live-canvas markup for perego-theme/legal-updated (spec 021 C15; DECISIONS 2026-07-22).
 *
 * Renders the REAL "Last updated" line that `LegalUpdatedRenderer::render()` emits.
 *
 * **Locked** preview: the date is the page's curated `_perego_legal_updated` meta, falling back to the
 * post's modified date. It is edited on the page itself (the Legal panel), not here.
 */

export const SEED_LABEL = 'Last updated: July 1, 2026';

export function LegalUpdatedSkeleton( { label = SEED_LABEL } ) {
	return <p className="legal-updated">{ label }</p>;
}
