/**
 * The service mosaic's real markup, rebuilt for the canvas (spec 023; owner 2026-07-28).
 *
 * WHY THIS BLOCK MOVED OFF ServerSideRender, twice over. First, the owner asked to drag the tiles to
 * order them, and nothing inside an SSR iframe can be dragged. Second, the SSR preview was almost
 * certainly blank anyway: the render callback resolves its service from `get_queried_object()`, which
 * the Site Editor never provides while editing a template — so `$currentSlug` came back empty and the
 * callback returned ''. Moving the block into each Service post's own content fixes both.
 *
 * WHY DRAGGING HERE MEANS MORE THAN ORDER. A tile's index becomes its slot (`m1`…`m15`), the slot
 * decides its shape, and the shape decides which crop it is drawn from. Moving a tile from position 5
 * to position 3 changes it from a `card` crop to a `tall` one. The canvas resolves that chain through
 * `../../Editor/projectSlots` so the picture visibly changes under the cursor, and says so when the
 * project has no crop for its new shape rather than silently borrowing one.
 *
 * Mirrors `Blocks/ServiceSelectedWorkRenderer.php`; pinned by `parity.test.js`. Per
 * `../../Editor/parity.js` only tags, class hooks and nesting are compared — which means the
 * `.m1`…`.m15` classes ARE compared, so the parity test doubles as a guard on the drag's own output.
 */
import { Fragment } from '@wordpress/element';
import { MASONRY_TILES, slotForIndex } from '../../Editor/projectSlots';

/** Mirror of `ServiceSelectedWorkRenderer::affordance()`. */
export function Affordance( { icon, galleryBadge } ) {
	if ( icon === 'play' ) {
		return <span className="play-btn" aria-hidden="true" />;
	}

	if ( icon === 'gallery' ) {
		return <span className="work-badge">{ galleryBadge }</span>;
	}

	return null;
}

/** Mirror of `ProjectTileImage::render()` — see `portfolio-grid/preview.js` for the collapse rule. */
export function TileImage( { bands, alt } ) {
	if ( ! bands || bands.desktop === '' ) {
		return null;
	}

	const img = <img src={ bands.desktop } alt={ alt } loading="lazy" />;

	if ( bands.collapsed ) {
		return img;
	}

	return (
		<picture>
			{ bands.mobile !== '' && <source media="(max-width: 560px)" srcSet={ bands.mobile } /> }
			{ bands.tablet !== '' && <source media="(max-width: 900px)" srcSet={ bands.tablet } /> }
			{ img }
		</picture>
	);
}

/**
 * What a tile contains, without the element that contains it.
 *
 * Split out because the canvas cannot reuse the front end's `<button>`: the sorting affordance is
 * itself a pair of buttons, and a button inside a button is invalid and unusable. In the editor the
 * tile becomes a `<div>` carrying the same classes — which is what the grid places and what the
 * stylesheet targets — with these contents inside it.
 */
export function WorkCardContent( { project, galleryBadge } ) {
	return (
		<>
			<TileImage bands={ project.bands } alt={ project.thumbAlt } />
			<span className="work-card__overlay" />
			<Affordance icon={ project.icon || 'none' } galleryBadge={ galleryBadge } />
		</>
	);
}

/** Mirror of `ServiceSelectedWorkRenderer::card()`. */
export function WorkCard( { project, className, galleryBadge, extraProps = {} } ) {
	return (
		<button
			type="button"
			className={ className }
			aria-label={ `Open ${ project.title }` }
			{ ...extraProps }
		>
			<WorkCardContent project={ project } galleryBadge={ galleryBadge } />
		</button>
	);
}

/**
 * The inert brand tile the handoff places right after the lead card.
 *
 * Not a project, so it is not a drop target and index arithmetic must skip it — it sits in the DOM
 * between tiles 1 and 2 but occupies its own fixed grid area.
 */
export function BrandCard( { logoUrl = '' } ) {
	return (
		<div className="work-card work-brand" aria-hidden="true">
			<img className="work-brand__logo" src={ logoUrl } alt="" />
		</div>
	);
}

/**
 * Mirror of `ServiceSelectedWorkRenderer::masonry()` + `render()`.
 *
 * `renderTile` lets the editor wrap each card in its sorting affordance while the front-end-shaped
 * default stays available to the parity test.
 */
export function ServiceMasonrySkeleton( {
	projects = [],
	labels = {},
	logoUrl = '',
	wavyUrl = '',
	renderTile,
	notes = null,
} ) {
	const galleryBadge = labels.galleryBadge || 'Gallery';

	// Mirror of the usability filter masonry() applies first: a tile with no image is not a tile.
	const usable = projects.filter( ( project ) => project.thumbUrl !== '' || ( project.gallerySrcs || [] ).length > 1 );

	if ( usable.length === 0 ) {
		return null;
	}

	const placed = usable.slice( 0, MASONRY_TILES );
	const overflow = usable.slice( MASONRY_TILES );

	const tile = ( project, index ) => {
		const className = `work-card ${ slotForIndex( index ) } reveal`;

		return renderTile
			? renderTile( project, index, className )
			: <WorkCard key={ project.id ?? index } project={ project } className={ className } galleryBadge={ galleryBadge } />;
	};

	return (
		<section className="portfolio page-section">
			<div className="wavy-bg" aria-hidden="true">
				<img src={ wavyUrl } alt="" />
			</div>
			<div className="container">
				<div className="work-masonry">
					{ placed.map( ( project, index ) => (
						<Fragment key={ project.id ?? index }>
							{ tile( project, index ) }
							{ index === 0 && <BrandCard logoUrl={ logoUrl } /> }
						</Fragment>
					) ) }
				</div>

				{ overflow.length > 0 && (
					<>
						<div className="work-more-grid" id="workMore" hidden>
							{ overflow.map( ( project, index ) => (
								<WorkCard
									key={ project.id ?? `overflow-${ index }` }
									project={ project }
									className="work-card reveal"
									galleryBadge={ galleryBadge }
									extraProps={ index % 4 > 0 ? { 'data-delay': index % 4 } : {} }
								/>
							) ) }
						</div>
						<div className="load-more-wrap">
							<button type="button" className="btn btn--dark load-more-btn" id="loadMore">
								{ labels.loadMore || 'Load more' }
							</button>
						</div>
					</>
				) }

				{ notes }
			</div>
		</section>
	);
}
