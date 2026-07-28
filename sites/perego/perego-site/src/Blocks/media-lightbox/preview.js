/**
 * Live-canvas markup for perego-theme/media-lightbox (spec 021 C15; DECISIONS 2026-07-22).
 *
 * Renders the REAL site-wide media dialog — backdrop, close, prev/next, frame, dots and counter — the
 * same skeleton `MediaLightboxRenderer::render()` emits.
 *
 * **Locked** preview: the block has no attributes. The front-end dialog ships `hidden`; the canvas
 * deliberately renders it **visible** so an editor can see that the one shared dialog is present on
 * the page — a `hidden` element would show nothing at all, which is what the bare sentence already did.
 */

export const SEED = { close: 'Close', prev: 'Previous', next: 'Next' };

export function MediaLightboxSkeleton() {
	return (
		<div className="lightbox" id="perego-media-lightbox" role="dialog" aria-modal="true" aria-label={ SEED.close }>
			<div className="lightbox__backdrop"></div>
			<div className="lightbox__inner">
				<button type="button" className="lightbox__close" aria-label={ SEED.close }>&times;</button>
				<button type="button" className="lightbox__nav lightbox__nav--prev" aria-label={ SEED.prev }>&#8249;</button>
				<div className="lightbox__frame"></div>
				<button type="button" className="lightbox__nav lightbox__nav--next" aria-label={ SEED.next }>&#8250;</button>
				<div className="lightbox__dots"></div>
				<p className="lightbox__counter" role="status" aria-live="polite"></p>
			</div>
		</div>
	);
}
