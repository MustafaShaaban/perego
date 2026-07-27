/**
 * Live-canvas markup for perego-theme/post-share. Mirrors `PostShareRenderer::render()` so the editor
 * shows the real row rather than a placeholder; `parity.test.js` asserts the two structures match.
 *
 * The hrefs are inert here — the real ones are built from the post's permalink on the front end, and
 * parity ignores `href` — so the canvas can render the row without a post context.
 */
import { __, sprintf } from '@wordpress/i18n';

/** Mirrors `PostShareRenderer::ICONS` (its PHP consts are the source of truth); visual-only. */
const ICONS = {
	X: 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
	LinkedIn: 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286z',
	Facebook: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073',
	WhatsApp: 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059z',
};

const COPY_ICON = 'M16 1H4a2 2 0 0 0-2 2v14h2V3h12zm3 4H8a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2m0 16H8V7h11z';

export function PostShareSkeleton() {
	return (
		<div className="post-share" data-perego-share>
			<span className="post-share__label">{ __( 'Share this article', 'perego-site' ) }</span>
			<ul className="post-share__list">
				{ Object.keys( ICONS ).map( ( name ) => (
					<li key={ name }>
						<a className="post-share__link" href="#" target="_blank" rel="noopener noreferrer"
							/* translators: %s: social network name */
							aria-label={ sprintf( __( 'Share on %s', 'perego-site' ), name ) }>
							<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
								<path fill="currentColor" d={ ICONS[ name ] } />
							</svg>
						</a>
					</li>
				) ) }
				<li>
					<button type="button" className="post-share__link post-share__copy"
						aria-label={ __( 'Copy link', 'perego-site' ) }>
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
							<path fill="currentColor" d={ COPY_ICON } />
						</svg>
						<span className="post-share__copied" hidden>{ __( 'Copied', 'perego-site' ) }</span>
					</button>
				</li>
			</ul>
		</div>
	);
}
