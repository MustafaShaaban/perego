/**
 * Markup-parity tests for the portfolio-grid live canvas (spec 023; owner 2026-07-28).
 *
 * This block used to be `<ServerSideRender>` by an explicit rule (spec 021: a block whose content is a
 * runtime query keeps SSR). Dragging the real cards to order them made that rule untenable — nothing
 * inside an SSR iframe can be dragged — so the canvas now rebuilds the markup and this pins it to what
 * PHP actually emits. Fixtures are live captures from `/work/`.
 *
 * Per `../../Editor/parity.js` only element tags, their class hooks and their nesting are compared:
 * text, inline `style`, `href`, `src`, `data-*` and `aria-*` are all invisible to it. So the
 * `<picture>`-vs-`<img>` decision below IS part of the contract while the URLs inside it are not, and
 * the `tabIndex` the canvas adds to the plate card costs nothing.
 *
 * Card fixtures rather than one whole-page fixture: parity compares sibling order, so a 33-card
 * fixture would assert the live content's order and break every time an editor reordered anything.
 */
import { renderToString } from '@wordpress/element';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizeMarkup } from '../../Editor/parity';
import { PortfolioCard, PortfolioGridSkeleton, TileImage, placeholderCards } from './preview';

const fixture = ( name ) => readFileSync( join( __dirname, '__fixtures__', name ), 'utf8' );

const baseCard = {
	id: 1,
	title: 'Sample Project',
	category: 'design',
	categoryLabel: 'Graphic Design',
	excerpt: 'Client: Sample Client · 2026',
	role: '',
	icon: 'none',
	thumbAlt: 'Sample Project',
	logoUrl: '',
	logoAlt: '',
	siteUrl: '',
	bands: null,
};

const card = ( overrides ) => ( { ...baseCard, ...overrides } );

describe( 'card shapes match the PHP renderer', () => {
	test( 'a web project with a logo is an anchor wrapping a bare image', () => {
		const html = renderToString(
			<PortfolioCard
				card={ card( {
					category: 'web',
					categoryLabel: 'Website Making',
					logoUrl: 'https://example.test/logo.png',
					logoAlt: 'Client',
					siteUrl: 'https://example.test',
				} ) }
				galleryBadge="Gallery"
			/>
		);

		expect( normalizeMarkup( html ) ).toEqual( normalizeMarkup( fixture( 'front-portfolio-grid-card-logo.html' ) ) );
	} );

	test( 'a web project awaiting its logo is the typographic name plate', () => {
		const html = renderToString(
			<PortfolioCard
				card={ card( {
					category: 'web',
					categoryLabel: 'Website Making',
					siteUrl: 'https://example.test',
				} ) }
				galleryBadge="Gallery"
			/>
		);

		expect( normalizeMarkup( html ) ).toEqual( normalizeMarkup( fixture( 'front-portfolio-grid-card-plate.html' ) ) );
	} );

	test( 'a non-web project with per-shape crops is a button wrapping a picture', () => {
		const html = renderToString(
			<PortfolioCard
				card={ card( {
					category: 'motion',
					categoryLabel: '2D Motion Graphics',
					icon: 'gallery',
					role: 'Example role',
					bands: {
						desktop: 'https://example.test/card.webp',
						tablet: 'https://example.test/card.webp',
						mobile: 'https://example.test/hero.webp',
						collapsed: false,
					},
				} ) }
				galleryBadge="Gallery"
			/>
		);

		expect( normalizeMarkup( html ) ).toEqual( normalizeMarkup( fixture( 'front-portfolio-grid-card-default.html' ) ) );
	} );

	/*
	 * Drift detection. Without this the three tests above would still pass if `normalizeMarkup` were
	 * ever loosened into comparing nothing.
	 */
	test( 'a card that lost its body is detected as a mismatch', () => {
		const drifted = '<button type="button" class="post-card reveal"><div class="post-card__media"></div></button>';

		expect( normalizeMarkup( drifted ) )
			.not.toEqual( normalizeMarkup( fixture( 'front-portfolio-grid-card-default.html' ) ) );
	} );
} );

describe( 'the picture-vs-image collapse rule', () => {
	/*
	 * Structural, not an optimization: `ProjectTileImage` emits a bare <img> when one crop serves every
	 * band, and parity compares element tags — so a canvas that always wrapped in <picture> would fail
	 * against the real markup of every project that has no per-shape crops.
	 */
	test( 'one crop for every band emits a bare image, with no picture wrapper', () => {
		const html = renderToString(
			<TileImage bands={ { desktop: 'a.png', tablet: 'a.png', mobile: 'a.png', collapsed: true } } alt="" />
		);

		expect( html ).toContain( '<img' );
		expect( html ).not.toContain( '<picture>' );
	} );

	test( 'distinct crops emit a picture with one source per band', () => {
		const html = renderToString(
			<TileImage bands={ { desktop: 'd.png', tablet: 't.png', mobile: 'm.png', collapsed: false } } alt="" />
		);

		expect( html ).toContain( '<picture>' );
		expect( html.match( /<source/g ) ).toHaveLength( 2 );
	} );
} );

describe( 'the section chrome', () => {
	test( 'heading, filters, grid, empty state and pager match the PHP section', () => {
		const html = renderToString(
			<PortfolioGridSkeleton
				strings={ {
					heading: 'Our Work',
					uiHome: 'Home',
					intro: 'A selection of projects across video, motion, design, and web.',
					demoNote: 'Example projects shown below.',
					groupLabel: 'Filter projects by service',
					noResults: 'No projects match this filter yet.',
				} }
				filterLabels={ {
					all: 'All',
					video: 'Video Editing',
					motion: '2D Motion Graphics',
					design: 'Graphic Design',
					web: 'Website Making',
				} }
				// The fixture's pager has four pages, which is 28-36 projects at PER_PAGE 9.
				cards={ Array.from( { length: 30 }, ( _, i ) => card( { id: i } ) ) }
				renderCard={ () => null }
			/>
		);

		// The skeleton renders the section AND the CTA; the fixture is the section alone.
		const section = html.slice( 0, html.indexOf( '</section>' ) + '</section>'.length );

		expect( normalizeMarkup( section ) )
			.toEqual( normalizeMarkup( fixture( 'front-portfolio-grid-chrome.html' ) ) );
	} );

	test( 'the pager disappears at a single page, exactly as the renderer returns ""', () => {
		const html = renderToString(
			<PortfolioGridSkeleton cards={ placeholderCards( 3 ) } renderCard={ () => null } />
		);

		expect( html ).not.toContain( 'class="pagination"' );
	} );
} );
