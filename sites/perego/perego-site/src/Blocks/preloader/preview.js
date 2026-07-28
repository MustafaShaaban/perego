/**
 * Live-canvas markup for perego-theme/preloader (spec 021 C15; DECISIONS 2026-07-22).
 *
 * Renders the REAL first-visit preloader — the three rings, the glow, the logo, the progress bar, and
 * the wordmark — the same skeleton `PreloaderRenderer::render()` emits.
 *
 * **Locked** preview: the block has no attributes, and the show/hide lifecycle is the Interactivity
 * API directives the renderer emits (deliberately omitted here — an editor canvas must never run the
 * hide-on-load behaviour, or the preview would vanish).
 */

/** Mirror of the renderer's wordmark. */
export const SEED_WORD = 'بيريجو · PEREGO';

export function PreloaderSkeleton() {
	return (
		<div className="preloader" aria-hidden="true">
			<div className="preloader__stage">
				<span className="preloader__ring preloader__ring--1"></span>
				<span className="preloader__ring preloader__ring--2"></span>
				<span className="preloader__ring preloader__ring--3"></span>
				<span className="preloader__glow"></span>
				<img className="preloader__logo" src="" alt="" />
			</div>
			<div className="preloader__bar" aria-hidden="true"><span></span></div>
			<p className="preloader__word">{ SEED_WORD }</p>
		</div>
	);
}
