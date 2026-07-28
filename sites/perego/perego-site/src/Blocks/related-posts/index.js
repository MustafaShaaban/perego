/**
 * Perego related-posts block — editor registration (spec 021 C12; DECISIONS 2026-07-22). The canvas
 * renders the REAL related-articles section (`RelatedPostsSkeleton` in preview.js), styled by the
 * theme's `main.css` via `add_editor_style`, replacing an `edit()` that returned a bare sentence.
 *
 * **Locked** preview with no controls — which posts appear is a same-category query the renderer owns.
 * The cards shown are recent published posts: a design preview of the card structure, not a
 * reimplementation of the renderer's query. Server-rendered (save returns null).
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { RELATED_CARD_COUNT, RelatedPostsSkeleton, placeholderCards } from './preview';
import metadata from './block.json';

function Edit() {
	const cards = useSelect( ( select ) => {
		const core = select( 'core' );
		const currentId = select( 'core/editor' )?.getCurrentPostId?.();
		const posts = core.getEntityRecords( 'postType', 'post', {
			per_page: RELATED_CARD_COUNT + 1,
			status: 'publish',
			orderby: 'date',
			order: 'desc',
			_embed: true,
		} );

		if ( ! posts ) {
			return placeholderCards();
		}

		const resolved = posts
			.filter( ( post ) => post.id !== currentId )
			.slice( 0, RELATED_CARD_COUNT )
			.map( ( post ) => ( {
				id: post.id,
				title: decodeEntities( post.title?.rendered || '' ) || __( 'Untitled', 'perego-site' ),
				categoryLabel: decodeEntities( post._embedded?.[ 'wp:term' ]?.flat?.()?.[ 0 ]?.name || '' ),
				meta: '',
				thumbUrl: post._embedded?.[ 'wp:featuredmedia' ]?.[ 0 ]?.source_url || '',
			} ) );

		return resolved.length > 0 ? resolved : placeholderCards();
	}, [] );

	return (
		<div { ...useBlockProps( { className: 'perego-related-posts__editor' } ) }
			onClick={ ( event ) => {
				if ( event.target.closest( 'a' ) ) {
					event.preventDefault();
				}
			} }>
			<RelatedPostsSkeleton cards={ cards } />
		</div>
	);
}

registerBlockType( metadata.name, { edit: Edit, save: () => null } );
