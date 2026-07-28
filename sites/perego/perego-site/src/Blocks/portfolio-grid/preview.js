/**
 * The portfolio grid's real markup, rebuilt for the canvas (spec 023; owner 2026-07-28).
 *
 * WHY THIS BLOCK STOPPED USING ServerSideRender. Spec 021's rule was that a block whose content is a
 * runtime query keeps `<ServerSideRender>` with a styled placeholder, and it named this block. That
 * rule assumed the editing surface was the block's *settings*. The owner's request makes the editing
 * surface the query result itself — dragging the actual cards to order them — and you cannot drag
 * anything inside an SSR iframe. The rule is superseded for this block and for
 * `service-selected-work`, and only for the reason above; see DECISIONS 2026-07-28.
 *
 * Every export below mirrors one method of `Blocks/PortfolioGridRenderer.php` and is pinned to it by
 * `parity.test.js`. Per `../../Editor/parity.js`, what is compared is element tags, their class hooks
 * and their nesting — not text, and not any attribute except `class`. So the `<picture>`-vs-`<img>`
 * decision below IS part of the contract while the URLs inside it are not.
 */
import { PER_PAGE } from './constants';

/** Mirror of `PortfolioGridRenderer::renderFilters()`. */
export function PortfolioFilters( { labels, groupLabel } ) {
	return (
		<div
			className="portfolio-filters"
			role="group"
			aria-label={ groupLabel }
			style={ { display: 'flex', flexWrap: 'wrap', gap: '12px', justifyContent: 'center', margin: 'clamp(24px,3vw,40px) 0' } }
		>
			{ Object.entries( labels ).map( ( [ slug, label ] ) => (
				<button
					key={ slug }
					type="button"
					className={ `web-filter portfolio-filter${ slug === 'all' ? ' is-active' : '' }` }
					data-filter={ slug }
					aria-pressed={ slug === 'all' ? 'true' : 'false' }
					// Inert in the canvas: it shows the whole unfiltered set so a card at position 52
					// can be dragged to position 3, which no nine-card window allows.
					aria-disabled="true"
				>
					{ label }
				</button>
			) ) }
		</div>
	);
}

/** Mirror of the affordance helper `ServiceSelectedWorkRenderer::affordance()`. */
export function Affordance( { icon, galleryBadge } ) {
	if ( icon === 'play' ) {
		return <span className="play-btn" aria-hidden="true" />;
	}

	if ( icon === 'gallery' ) {
		return <span className="work-badge">{ galleryBadge }</span>;
	}

	return null;
}

/** Mirror of `PortfolioGridRenderer::namePlate()`. */
export function NamePlate( { title, siteUrl } ) {
	const host = ( () => {
		try {
			return new URL( siteUrl ).host.replace( /^www\./, '' );
		} catch {
			return '';
		}
	} )();

	return (
		<span className="post-card__plate" aria-hidden="true">
			<span className="post-card__plate-name">{ title }</span>
			{ host !== '' && <span className="post-card__plate-host">{ host }</span> }
		</span>
	);
}

/**
 * Mirror of `ProjectTileImage::render()`.
 *
 * The collapse rule matters structurally, not just in bytes: the PHP emits a bare `<img>` when one
 * crop serves every band, and parity compares element tags — so always wrapping in `<picture>` would
 * fail against the real markup for every project with no per-shape crops.
 */
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
 * Mirror of `PortfolioGridRenderer::renderCard()` — the media box only.
 *
 * A logo is a brand mark, not a scene: it has no per-shape crops and must not be swapped by
 * breakpoint, which is why it is a bare `<img>` rather than a `TileImage`.
 */
function CardMedia( { card, galleryBadge } ) {
	const isWebCard = card.category === 'web';
	const hasLogo = isWebCard && card.logoUrl !== '';

	const media = ( () => {
		if ( isWebCard && ! hasLogo ) {
			return <NamePlate title={ card.title } siteUrl={ card.siteUrl } />;
		}
		if ( hasLogo ) {
			return <img src={ card.logoUrl } alt={ card.logoAlt } loading="lazy" />;
		}
		if ( card.bands && card.bands.desktop !== '' ) {
			return <TileImage bands={ card.bands } alt={ card.thumbAlt } />;
		}

		return <span className="post-card__media-placeholder" data-category={ card.category } aria-hidden="true" />;
	} )();

	return (
		<div className="post-card__media">
			{ media }
			{ ! isWebCard && <Affordance icon={ card.icon || 'none' } galleryBadge={ galleryBadge } /> }
		</div>
	);
}

function CardBody( { card } ) {
	return (
		<div className="post-card__body">
			<span className="post-card__cat">{ card.categoryLabel }</span>
			<h2 className="post-card__title" style={ { fontSize: 'clamp(18px,1.6vw,22px)' } }>{ card.title }</h2>
			{ card.role
				? <p className="post-card__role">{ card.role }</p>
				: <p className="post-card__excerpt">{ card.excerpt }</p> }
		</div>
	);
}

/**
 * One card, in whichever of the three shapes the project calls for.
 *
 * `extraProps` carries the sorting wiring. The plate variant is an `<article>` on the front end —
 * an inert control must not be focusable — but in the canvas it still has to be reachable by keyboard
 * to be sortable, so it takes a `tabIndex` there. Parity ignores `tabindex`, so this costs nothing.
 */
export function PortfolioCard( { card, galleryBadge, extraProps = {}, isEditor = false } ) {
	const isWebCard = card.category === 'web';
	const hasLogo = isWebCard && card.logoUrl !== '';
	const className = [
		'post-card reveal',
		isWebCard ? 'post-card--logo' : '',
		isWebCard && ! hasLogo ? 'post-card--plate' : '',
	].filter( Boolean ).join( ' ' );

	const inner = (
		<>
			<CardMedia card={ card } galleryBadge={ galleryBadge } />
			<CardBody card={ card } />
		</>
	);

	if ( isWebCard && card.siteUrl !== '' ) {
		return (
			<a
				className={ className }
				href={ card.siteUrl }
				target="_blank"
				rel="noopener"
				aria-label={ `Visit ${ card.title }` }
				data-category={ card.category }
				{ ...extraProps }
				// A live link inside the canvas would navigate the editor away.
				onClick={ isEditor ? ( event ) => event.preventDefault() : undefined }
			>
				{ inner }
			</a>
		);
	}

	if ( isWebCard ) {
		return (
			<article
				className={ className }
				data-category={ card.category }
				{ ...( isEditor ? { tabIndex: 0 } : {} ) }
				{ ...extraProps }
			>
				{ inner }
			</article>
		);
	}

	return (
		<button
			type="button"
			className={ className }
			aria-label={ `Open ${ card.title }` }
			data-category={ card.category }
			{ ...extraProps }
		>
			{ inner }
		</button>
	);
}

/** Mirror of `PortfolioGridRenderer::renderPager()`. */
export function Pager( { projectCount } ) {
	const maxPages = Math.max( 1, Math.ceil( projectCount / PER_PAGE ) );

	if ( maxPages <= 1 ) {
		return null;
	}

	return (
		<nav className="pagination" hidden aria-label="Projects pagination" aria-disabled="true">
			<button type="button" className="pagination__prev" aria-label="Previous page">&#8249;</button>
			{ Array.from( { length: maxPages }, ( _, i ) => i + 1 ).map( ( page ) => (
				<button key={ page } type="button" data-page={ page } aria-label={ `Page ${ page }` }>{ page }</button>
			) ) }
			<button type="button" className="pagination__next" aria-label="Next page">&#8250;</button>
		</nav>
	);
}

/** Mirror of `PortfolioGridRenderer::renderCta()`. */
export function PortfolioCta( { strings } ) {
	if ( ! strings.ctaTitle ) {
		return null;
	}

	return (
		<section className="page-section" aria-labelledby="pfCta" style={ { borderTop: '1px solid rgba(255,255,255,0.08)' } }>
			<div className="container" style={ { textAlign: 'center', maxWidth: '820px' } }>
				<h2 className="section-title" id="pfCta">{ strings.ctaTitle }</h2>
				<p className="section-lead" style={ { margin: '16px auto 28px' } }>{ strings.ctaBody || '' }</p>
				<a className="btn btn--accent" href="#">{ strings.ctaButton || '' }</a>
			</div>
		</section>
	);
}

/** Mirror of `PortfolioGridRenderer::render()` — the whole section. */
export function PortfolioGridSkeleton( { strings = {}, filterLabels = {}, cards = [], renderCard } ) {
	const galleryBadge = strings.galleryBadge || 'Gallery';

	return (
		<>
			<section className="page-section">
				<div className="container">
					{ strings.heading && (
						<div className="post-hero__inner" style={ { textAlign: 'center' } }>
							{ strings.uiHome && (
								<nav className="page-crumb" style={ { justifyContent: 'center' } } aria-label="Breadcrumb">
									<a href="#">{ strings.uiHome }</a>
									<span aria-hidden="true">/</span>
									<span aria-current="page">{ strings.heading }</span>
								</nav>
							) }
							<h1 className="post-title" style={ { fontSize: 'clamp(34px,4.5vw,60px)', marginTop: '14px' } }>
								{ strings.heading }
							</h1>
							{ strings.intro && <p className="section-lead">{ strings.intro }</p> }
							{ strings.demoNote && (
								<p className="section-lead" style={ { fontSize: '14px', color: 'var(--muted-2)', marginTop: '6px' } }>
									{ strings.demoNote }
								</p>
							) }
						</div>
					) }

					<PortfolioFilters labels={ filterLabels } groupLabel={ strings.groupLabel || '' } />

					<div className="blog-grid" id="portfolioGrid" data-per-page={ PER_PAGE }>
						{ cards.map( ( card, index ) =>
							renderCard
								? renderCard( card, index )
								: <PortfolioCard key={ card.id ?? index } card={ card } galleryBadge={ galleryBadge } />
						) }
					</div>

					<p
						id="portfolioEmpty"
						hidden
						className="section-lead"
						role="status"
						style={ { fontSize: 'var(--fs-lead)', padding: 'clamp(40px,6vw,80px) 0' } }
					>
						{ strings.noResults || '' }
					</p>

					<Pager projectCount={ cards.length } />
				</div>
			</section>

			<PortfolioCta strings={ strings } />
		</>
	);
}

/** Seed cards so the block previews as itself before REST resolves. */
export function placeholderCards( count = 6 ) {
	return Array.from( { length: count }, ( _, index ) => ( {
		id: -( index + 1 ),
		title: 'Project',
		category: 'design',
		categoryLabel: 'Graphic Design',
		excerpt: '',
		role: '',
		icon: 'none',
		thumbAlt: '',
		logoUrl: '',
		logoAlt: '',
		siteUrl: '',
		bands: null,
	} ) );
}
