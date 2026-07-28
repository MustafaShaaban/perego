/**
 * Service Selected Work editor — a live canvas you can drag to order (spec 023; owner 2026-07-28).
 *
 * WHY THIS BLOCK MOVED INTO POST CONTENT. It used to sit in `single-perego_service.html` and read the
 * Service from `get_queried_object()`. The Site Editor never provides one while editing a template, so
 * the SSR preview resolved no service and rendered nothing — and a block in a template has no service
 * to save an order against either. `scripts/migrate-service-selected-work-block.php` moves it into each
 * Service post's own content, where `useEntityProp` reaches that service's meta directly and the order
 * is the block instance's own attribute.
 *
 * WHY DRAGGING HERE DOES MORE THAN REORDER. Index → slot (`m1`…`m15`) → shape → crop. Moving a tile
 * from position 5 to position 3 turns a `card` crop into a `tall` one, so the picture changes as it
 * lands. When the project has no crop for its new shape the tile says which one it borrowed rather
 * than swapping silently — that honesty is the reason to drag here instead of in a sidebar list.
 *
 * Website Making keeps `ServerSideRender`: that service branches to `WebShowcaseRenderer`, a uniform
 * grid of browser-chrome cards, so ordering it changes nothing about how any card is cropped and the
 * mosaic skeleton would be a preview of the wrong section entirely.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Button } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';
import { __, sprintf } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

import { PanelSection } from '../../Editor/PanelSection';
import { SortableItem } from '../../Editor/SortableItem';
import { useCanvasSort } from '../../Editor/useCanvasSort';
import {
	DEFAULT_SHAPE,
	MOBILE_SHAPE,
	SHAPES,
	resolveCrop,
	shapeForSlot,
	slotForIndex,
	workCapFor,
} from '../../Editor/projectSlots';
import { resolveSelection } from '../../Editor/servicePortfolio';
import { ServiceMasonrySkeleton, WorkCardContent } from './preview';
import metadata from './block.json';

const SERVICE_POST_TYPE = 'perego_service';
const PROJECT_POST_TYPE = 'perego_project';
const PROJECT_TAXONOMY = 'perego_project_category';

/** Mirror of the provider's service-slug → project-category map. */
const SERVICE_CATEGORY = {
	'video-editing': 'video',
	'motion-graphics': 'motion',
	'graphic-design': 'design',
	'website-making': 'web',
};

/** Polylang gives every language its own term, so the Arabic Video category is `video-ar`. */
const matchesCategory = ( slug, canonical ) => slug === canonical || slug?.startsWith( `${ canonical }-` );

const themeUri = () => {
	const link = document.querySelector( 'link[href*="/themes/perego-theme/"]' );
	const match = link?.href?.match( /^(.*\/themes\/perego-theme)\// );

	return match ? match[ 1 ] : '';
};

function Edit( { attributes, setAttributes, isSelected, context } ) {
	const blockProps = useBlockProps( { className: 'perego-service-selected-work__editor perego-ssw__editor' } );
	const projectOrder = attributes.projectOrder || [];

	const postId = context?.postId;
	const [ meta ] = useEntityProp( 'postType', SERVICE_POST_TYPE, 'meta', postId );

	const serviceSlug = String( meta?._perego_service_slug || '' );
	const canonical = SERVICE_CATEGORY[ serviceSlug ] || '';

	const term = useSelect( ( select ) => {
		const terms = select( 'core' ).getEntityRecords( 'taxonomy', PROJECT_TAXONOMY, { per_page: 100 } ) || [];

		return terms.find( ( candidate ) => matchesCategory( candidate.slug, canonical ) );
	}, [ canonical ] );

	const projects = useSelect( ( select ) => ( term
		? select( 'core' ).getEntityRecords( 'postType', PROJECT_POST_TYPE, {
			per_page: 100,
			status: 'publish',
			orderby: 'date',
			order: 'desc',
			[ PROJECT_TAXONOMY ]: [ term.id ],
			_embed: true,
		} )
		: [] ), [ term?.id ] );

	const attachmentIds = ( projects || [] ).flatMap( ( project ) => [
		project.featured_media,
		...Object.values( SHAPES ).map( ( shape ) => Number( project.meta?.[ shape.meta ] ) || 0 ),
	] ).filter( ( id ) => id > 0 );

	const attachments = useSelect( ( select ) => ( attachmentIds.length === 0
		? []
		: select( 'core' ).getEntityRecords( 'postType', 'attachment', {
			include: [ ...new Set( attachmentIds ) ],
			per_page: 100,
		} ) ), [ attachmentIds.join( ',' ) ] );

	// Website Making renders a different section entirely; previewing the mosaic there would preview
	// something the page never shows.
	if ( serviceSlug === 'website-making' ) {
		return (
			<div { ...blockProps }>
				<p className="perego-editor-help">
					{ __( 'Website Making shows the browser-chrome showcase rather than the mosaic, so its cards are all one shape and ordering them changes no crops.', 'perego-site' ) }
				</p>
				<ServerSideRender block={ metadata.name } attributes={ attributes } />
			</div>
		);
	}

	const urlById = new Map( ( attachments || [] ).map( ( item ) => [
		item.id,
		item.media_details?.sizes?.large?.source_url || item.source_url || '',
	] ) );

	const composed = resolveSelection(
		projects || [],
		( meta?._perego_service_portfolio_project_ids || [] )
			.map( ( id ) => ( projects || [] ).find( ( project ) => project.id === Number( id ) ) )
			.filter( Boolean ),
		meta?._perego_service_portfolio_mode,
		meta?._perego_service_portfolio_exclude_ids
	);

	// The block's own order, then the cap the front end applies — a canvas that showed more tiles
	// than the page does would not be a preview of it.
	const byId = new Map( composed.map( ( project ) => [ project.id, project ] ) );
	const taken = new Set();
	const ordered = [];
	projectOrder.forEach( ( id ) => {
		const project = byId.get( Number( id ) );
		if ( project && ! taken.has( project.id ) ) {
			ordered.push( project );
			taken.add( project.id );
		}
	} );
	composed.forEach( ( project ) => {
		if ( ! taken.has( project.id ) ) {
			ordered.push( project );
		}
	} );

	const capped = ordered.slice( 0, workCapFor( serviceSlug ) );

	/** Resolve each tile's crop for the slot it currently occupies, and note anything it had to borrow. */
	const tiles = capped.map( ( project, index ) => {
		const slot = slotForIndex( index );
		const shape = shapeForSlot( slot );
		const featuredId = Number( project.featured_media ) || 0;
		const getId = ( key ) => Number( project.meta?.[ key ] ) || 0;

		const crop = resolveCrop( shape, getId, featuredId );
		const urlFor = ( wanted ) => urlById.get( resolveCrop( wanted, getId, featuredId ).id ) || '';

		const desktop = urlById.get( crop.id ) || '';
		const tablet = urlFor( DEFAULT_SHAPE );
		const mobile = urlFor( MOBILE_SHAPE );

		return {
			id: project.id,
			title: decodeEntities( project.title?.rendered || '' ) || __( 'Untitled Project', 'perego-site' ),
			thumbUrl: desktop,
			thumbAlt: decodeEntities( project.title?.rendered || '' ),
			gallerySrcs: project.meta?._perego_gallery_attachment_ids || [],
			icon: String( project.meta?._perego_project_icon || 'none' ),
			shape,
			crop,
			bands: desktop === '' ? null : { desktop, tablet, mobile, collapsed: tablet === desktop && mobile === desktop },
		};
	} );

	const cropNote = ( tile ) => {
		if ( ! tile.crop.borrowed ) {
			return sprintf( /* translators: %s: crop shape name. */ __( '%s crop', 'perego-site' ), tile.shape );
		}

		return tile.crop.shape
			? sprintf(
				/* translators: 1: the shape this slot wants. 2: the shape actually being used. */
				__( 'No %1$s crop — using %2$s', 'perego-site' ),
				tile.shape,
				tile.crop.shape
			)
			: sprintf( /* translators: %s: crop shape name. */ __( 'No %s crop — using the featured image', 'perego-site' ), tile.shape );
	};

	const sort = useCanvasSort( {
		length: tiles.length,
		isEnabled: isSelected && tiles.length > 1,
		describeItem: ( index ) => tiles[ index ]?.title || '',
		describeLanding: ( index ) => cropNote( tiles[ index ] || { crop: {}, shape: '' } ),
		onReorder: ( reorder ) => setAttributes( { projectOrder: reorder( tiles.map( ( tile ) => tile.id ) ) } ),
	} );

	const borrowed = tiles.filter( ( tile ) => tile.crop.borrowed );

	return (
		<div { ...blockProps } onClick={ ( event ) => {
			if ( event.target.closest( 'a' ) ) {
				event.preventDefault();
			}
		} }>
			<InspectorControls>
				<PanelSection title={ __( 'Order', 'perego-site' ) } initialOpen>
					<p className="perego-editor-help">
						{ isSelected
							? __( 'Drag a tile to reorder it. Its position decides its shape, so a tile is re-cropped as it lands.', 'perego-site' )
							: __( 'Select this block to reorder its tiles.', 'perego-site' ) }
					</p>
					{ projectOrder.length > 0 && (
						<Button variant="secondary" onClick={ () => setAttributes( { projectOrder: [] } ) }>
							{ __( 'Reset to automatic order', 'perego-site' ) }
						</Button>
					) }
				</PanelSection>
				<PanelSection title={ __( 'Crops in use', 'perego-site' ) }>
					{ /* The same information the on-tile badges carry, in a form a screen reader can
					     review without entering the grid. */ }
					{ tiles.length === 0 && (
						<p className="perego-editor-help">{ __( 'No projects with usable images yet.', 'perego-site' ) }</p>
					) }
					{ tiles.map( ( tile, index ) => (
						<p className="perego-editor-help" key={ tile.id }>
							{ sprintf(
								/* translators: 1: position. 2: project name. 3: which crop it uses. */
								__( 'Tile %1$d — %2$s: %3$s', 'perego-site' ),
								index + 1,
								tile.title,
								cropNote( tile )
							) }
						</p>
					) ) }
					{ borrowed.length > 0 && (
						<p className="perego-editor-help">
							{ __( 'Upload the missing crops on each project’s own screen, under “In the grids”.', 'perego-site' ) }
						</p>
					) }
				</PanelSection>
			</InspectorControls>

			<ServiceMasonrySkeleton
				projects={ tiles }
				labels={ { galleryBadge: __( 'Gallery', 'perego-site' ), loadMore: __( 'Load more', 'perego-site' ) } }
				logoUrl={ `${ themeUri() }/assets/images/logo-full.png` }
				wavyUrl={ `${ themeUri() }/assets/images/wavy-corners.png` }
				renderTile={ ( tile, index, className ) => (
					<SortableItem
						key={ tile.id }
						index={ index }
						length={ tiles.length }
						sort={ sort }
						isEnabled={ isSelected && tiles.length > 1 }
						label={ tile.title }
						// The wrapper IS the tile: the slot class must sit on the element the grid
						// places, and `.work-card` on the element the stylesheet targets.
						className={ className }
					>
						<WorkCardContent project={ tile } galleryBadge={ __( 'Gallery', 'perego-site' ) } />
						<span
							className="perego-sortable__crop-note"
							data-borrowed={ tile.crop.borrowed ? 'true' : 'false' }
							aria-hidden="true"
						>
							{ cropNote( tile ) }
						</span>
					</SortableItem>
				) }
			/>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
