/**
 * Perego project-gallery-lightbox block — editor registration (spec 021 C9; DECISIONS 2026-07-22). The canvas
 * renders the REAL gallery markup (`ProjectGallerySkeleton` in preview.js), styled by the theme's `main.css`
 * via `add_editor_style`, replacing an `edit()` that returned a bare sentence and showed the editor nothing.
 *
 * The block has no attributes — the images are the Project's `_perego_gallery_attachment_ids` meta, edited by
 * the gallery meta box — so this is a **locked** preview with no controls. When the block is edited on a
 * `perego_project` post the canvas mirrors that post's own gallery live; in the shared single-project template,
 * where there is no specific project, it shows the grid structure with placeholder tiles. Every lookup degrades
 * safely. Server-rendered (save returns null); the dialog itself belongs to the one site-wide media-lightbox
 * block, so nothing here opens on click.
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { ProjectGallerySkeleton } from './preview';
import metadata from './block.json';

/** Mirror of `ProjectPostType::META_GALLERY`. */
const GALLERY_META_KEY = '_perego_gallery_attachment_ids';

function Edit() {
	const blockProps = useBlockProps( { className: 'perego-project-gallery__editor' } );

	const images = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		// In the shared single-project template there is no queried project, so only mirror a real
		// `perego_project` post; anything else falls back to the placeholder tiles.
		if ( ! editor || editor.getCurrentPostType?.() !== 'perego_project' ) {
			return [];
		}

		const core = select( 'core' );
		const meta = editor.getEditedPostAttribute( 'meta' ) || {};
		const ids = meta[ GALLERY_META_KEY ];

		if ( ! Array.isArray( ids ) ) {
			return [];
		}

		return ids
			.map( ( id ) => {
				const media = core.getMedia( id );
				// Drop attachments still resolving so a half-loaded gallery never renders empty tiles;
				// the placeholder grid covers the "nothing to show yet" case instead.
				return media ? { id, url: media.source_url || '', alt: media.alt_text || '' } : null;
			} )
			.filter( Boolean );
	}, [] );

	return (
		<div { ...blockProps } onClick={ ( event ) => {
			// Neutralize the preview's real triggers so a click never acts on the editor.
			if ( event.target.closest( 'a, button' ) ) {
				event.preventDefault();
			}
		} }>
			<ProjectGallerySkeleton images={ images } />
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
