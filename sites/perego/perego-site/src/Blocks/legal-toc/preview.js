/**
 * Live-canvas markup for perego-theme/legal-toc (spec 021 C15; DECISIONS 2026-07-22).
 *
 * Renders the REAL "On this page" table of contents — the `.legal-toc` aside, its heading, and the
 * ordered list of section anchors — the same skeleton `LegalTocRenderer::render()` emits.
 *
 * **Locked** preview: the entries are derived by scanning the legal page's own headings, so there is
 * nothing to configure. The canvas shows sample entries because scanning the post content in editor JS
 * would duplicate the renderer's heading parser and drift from it.
 */

export const SEED_TITLE = 'On this page';

export const SEED_ENTRIES = [
	'1. Acceptance of Terms',
	'2. Services We Provide',
	'3. Client Responsibilities',
	'4. Intellectual Property',
	'5. Payments & Refunds',
	'6. Limitation of Liability',
	'7. Changes to These Terms',
	'8. Contact',
];

export function LegalTocSkeleton( { title = SEED_TITLE, entries = SEED_ENTRIES } ) {
	return (
		<aside className="legal-toc" aria-label={ title }>
			<h2>{ title }</h2>
			<ol>
				{ entries.map( ( entry, index ) => (
					<li key={ entry }><a href={ `#s${ index + 1 }` }>{ entry }</a></li>
				) ) }
			</ol>
		</aside>
	);
}
