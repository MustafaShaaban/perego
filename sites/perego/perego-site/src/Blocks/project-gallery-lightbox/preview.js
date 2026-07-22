/**
 * Live-canvas markup for perego-theme/project-gallery-lightbox (spec 021 C9; DECISIONS 2026-07-22).
 *
 * Renders the REAL gallery section — the same tag + class skeleton `ProjectGalleryLightboxRenderer::render()`
 * emits (the `.portfolio.project-gallery` section, its `#pjGallery` heading, and the `.work-masonry` grid of
 * `.work-card` trigger buttons) — so the editor canvas shows the actual grid, styled by the theme's `main.css`
 * via `add_editor_style`. It replaces an `edit()` that returned a bare sentence and showed the editor nothing.
 *
 * The block has no attributes: the images come from the Project's `_perego_project_gallery` meta, edited by the
 * gallery meta box (`PeregoSite\Admin\ProjectGalleryMetaBox`), so this is a **locked** preview with no controls.
 * `ProjectGallerySkeleton` is pure so `parity.test.js` can render it to a string and assert it against a fixture
 * of the PHP output; per `../../Editor/parity.js` only tags, class hooks, and nesting are compared, so the
 * renderer's inline `style` attributes and the volatile `data-gallery`/`src` values are ignored.
 *
 * The heading mirrors `PortfolioContent::projectLabel('galleryTitle')` (the PHP array is the source of truth);
 * parity compares structure only, so seed copy can never break the test.
 */
import { __, sprintf } from '@wordpress/i18n';

/** Mirror of `PortfolioContent` PROJECT.en.galleryTitle — the English canvas heading. */
export const SEED_GALLERY_TITLE = 'Project gallery';

/**
 * How many placeholder tiles the canvas shows when the Project has no gallery images yet, so the
 * editor still sees the masonry rhythm rather than an empty box.
 */
export const PLACEHOLDER_TILE_COUNT = 3;

/**
 * The real gallery markup for the editor canvas.
 *
 * @param {Object}        props
 * @param {Array<Object>} props.images `{ id, url, alt }` tiles; empty renders placeholder tiles.
 * @param {string}        props.title  Section heading.
 */
export function ProjectGallerySkeleton( { images = [], title = SEED_GALLERY_TITLE } ) {
	const heading = title || SEED_GALLERY_TITLE;
	// The renderer joins every full-size src into one data-gallery value shared by all triggers; the
	// canvas reproduces the attribute so the structure matches, not the runtime lightbox wiring.
	const gallerySources = images.map( ( image ) => image.url ).join( ',' );
	const tiles = images.length > 0
		? images
		: Array.from( { length: PLACEHOLDER_TILE_COUNT }, ( _, index ) => ( { id: `placeholder-${ index }`, url: '', alt: '' } ) );

	return (
		<section className="portfolio project-gallery" aria-labelledby="pjGallery">
			<h2 id="pjGallery" className="section-title">{ heading }</h2>
			<div className="work-masonry" aria-label={ heading }>
				{ tiles.map( ( tile, index ) => (
					<button
						key={ tile.id }
						type="button"
						className="work-card reveal"
						data-gallery={ gallerySources }
						data-gallery-index={ index }
						aria-label={ sprintf(
							/* translators: %d: 1-based position of the image within the project gallery. */
							__( 'Open project media %d', 'perego-site' ),
							index + 1
						) }
					>
						<img src={ tile.url } alt={ tile.alt } loading="lazy" />
						<span className="work-card__overlay"></span>
						<span className="work-zoom" aria-hidden="true"></span>
					</button>
				) ) }
			</div>
		</section>
	);
}
