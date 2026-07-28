/**
 * The canvas's own copy of the portfolio query (spec 023; owner 2026-07-28).
 *
 * Reaches the same card list `ProjectRepository::allForGrid()` builds, but from REST, so `edit()` can
 * draw the real grid and let an editor drag it.
 *
 * THE CANVAS PREVIEWS ENGLISH, AND SAYS SO. `toGridCard()` falls back to the linked English post for
 * thumbnail, logo, role and site URL, because an Arabic project carries no media of its own. Polylang
 * Free does not expose a post's translations in the REST response, so reproducing that chain here is
 * not possible — and it is also unnecessary: both grid instances live in language-neutral FSE
 * templates, so there is no "current language" for the canvas to honour, and the English posts hold
 * all the media, so the fallback never fires. `index.js` says this in the editor rather than leaving
 * an Arabic editor wondering.
 */
import { useSelect } from '@wordpress/data';

import { DEFAULT_SHAPE, MOBILE_SHAPE, SHAPES, resolveCrop, shapeForSlot } from '../../Editor/projectSlots';

const PROJECT_POST_TYPE = 'perego_project';
const WEB_CATEGORY_SLUG = 'web';
const FEATURED_KEY = '_perego_project_featured';
const LOGO_KEY = '_perego_logo_id';
const SITE_URL_KEY = '_perego_site_url';
const ROLE_KEY = '_perego_role';

/**
 * Polylang gives every language its own term, so the Arabic Website Making category is `web-ar`.
 * Match the canonical slug or a `web-<locale>` variant — not a bare prefix, which would also swallow
 * an unrelated future term like `webinar`. Same test `EditorPanels/index.js` documents.
 */
const isWebSlug = ( slug ) => slug === WEB_CATEGORY_SLUG || slug?.startsWith( `${ WEB_CATEGORY_SLUG }-` );

const decode = ( value ) => ( value || '' ).replace( /&#0?39;/g, "'" ).replace( /&amp;/g, '&' );

/** Every attachment id a card might need, so they can be fetched in one batch rather than per card. */
function attachmentIds( projects ) {
	const ids = new Set();

	projects.forEach( ( project ) => {
		const meta = project.meta || {};

		[ LOGO_KEY, ...Object.values( SHAPES ).map( ( shape ) => shape.meta ) ].forEach( ( key ) => {
			const id = Number( meta[ key ] ) || 0;
			if ( id > 0 ) {
				ids.add( id );
			}
		} );

		if ( project.featured_media ) {
			ids.add( project.featured_media );
		}
	} );

	return [ ...ids ];
}

export function useProjectCards( { featuredOnly = false, projectOrder = [], filterLabels = {} } ) {
	const projects = useSelect( ( select ) => select( 'core' ).getEntityRecords( 'postType', PROJECT_POST_TYPE, {
		// Never -1: DECISIONS 2026-07-23 records that exact `rest_invalid_param` emptying a picker.
		per_page: 100,
		status: 'publish',
		// Valid only because the CPT declares `page-attributes`; core gates the enum on that support.
		orderby: 'menu_order',
		order: 'asc',
		_embed: true,
		lang: 'en',
	} ), [] );

	const ids = projects ? attachmentIds( projects ) : [];
	const attachments = useSelect( ( select ) => ( ids.length === 0
		? []
		: select( 'core' ).getEntityRecords( 'postType', 'attachment', { include: ids, per_page: 100 } ) ), [ ids.join( ',' ) ] );

	if ( ! projects ) {
		return { cards: null, total: 0 };
	}

	const urlById = new Map( ( attachments || [] ).map( ( item ) => [
		item.id,
		item.media_details?.sizes?.large?.source_url || item.source_url || '',
	] ) );

	// REST takes a single `orderby`, so the front end's `menu_order ASC, date DESC` needs its
	// tiebreak applied here — otherwise the canvas quietly reorders every project that shares the
	// default position 0, which is most of them.
	const sorted = [ ...projects ].sort( ( a, b ) => {
		const byOrder = ( a.menu_order || 0 ) - ( b.menu_order || 0 );
		return byOrder !== 0 ? byOrder : new Date( b.date ) - new Date( a.date );
	} );

	const eligible = featuredOnly
		? sorted.filter( ( project ) => Number( project.meta?.[ FEATURED_KEY ] ) > 0 )
		: sorted;

	// Mirror of `Content\ProjectOrder::apply()`: named first, everything else appended in query order.
	const byId = new Map( eligible.map( ( project ) => [ project.id, project ] ) );
	const taken = new Set();
	const ordered = [];

	projectOrder.forEach( ( id ) => {
		const project = byId.get( Number( id ) );
		if ( project && ! taken.has( project.id ) ) {
			ordered.push( project );
			taken.add( project.id );
		}
	} );
	eligible.forEach( ( project ) => {
		if ( ! taken.has( project.id ) ) {
			ordered.push( project );
		}
	} );

	const cards = ordered.map( ( project ) => {
		const meta = project.meta || {};
		const terms = ( project._embedded?.[ 'wp:term' ] || [] ).flat();
		const term = terms.find( ( candidate ) => candidate?.taxonomy === 'perego_project_category' );
		const slug = term?.slug || '';
		const category = isWebSlug( slug ) ? WEB_CATEGORY_SLUG : slug;

		const featuredId = Number( project.featured_media ) || 0;
		const cropId = ( shape ) => resolveCrop( shape, ( key ) => Number( meta[ key ] ) || 0, featuredId ).id;
		const urlFor = ( shape ) => urlById.get( cropId( shape ) ) || '';

		const desktop = urlFor( shapeForSlot( '' ) );
		const tablet = urlFor( DEFAULT_SHAPE );
		const mobile = urlFor( MOBILE_SHAPE );

		return {
			id: project.id,
			title: decode( project.title?.rendered ) || 'Untitled Project',
			category,
			categoryLabel: filterLabels[ category ] || decode( term?.name ) || '',
			excerpt: decode( ( project.excerpt?.rendered || '' ).replace( /<[^>]*>/g, '' ) ).trim(),
			role: String( meta[ ROLE_KEY ] || '' ),
			icon: String( meta._perego_project_icon || 'none' ),
			thumbAlt: decode( project.title?.rendered ),
			logoUrl: urlById.get( Number( meta[ LOGO_KEY ] ) || 0 ) || '',
			logoAlt: decode( project.title?.rendered ),
			siteUrl: String( meta[ SITE_URL_KEY ] || '' ),
			bands: desktop === '' ? null : { desktop, tablet, mobile, collapsed: tablet === desktop && mobile === desktop },
		};
	} );

	return { cards, total: cards.length };
}
