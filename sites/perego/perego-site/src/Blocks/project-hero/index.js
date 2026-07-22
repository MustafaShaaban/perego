/**
 * Perego project-hero block — editor registration (spec 021 C8; DECISIONS 2026-07-22). The canvas renders
 * the REAL hero markup (`ProjectHeroSkeleton` in preview.js), styled by the theme's `main.css` via
 * `add_editor_style`, replacing an `edit()` that returned a bare sentence and showed the editor nothing.
 *
 * The block has no attributes — every value comes from the queried Project post — so this is a **locked**
 * preview with no controls; the copy is edited on the Project itself. When the block is edited on a
 * `perego_project` post the canvas mirrors that post live (title, category term, featured image, and the
 * client/year/role/deliverables meta); in the shared template, where there is no specific project, it shows
 * the structure with placeholders. Every lookup degrades safely. Server-rendered (save returns null).
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { ProjectHeroSkeleton, metaRows } from './preview';
import metadata from './block.json';
import './style.scss';

function Edit() {
	const blockProps = useBlockProps( { className: 'perego-project-hero__editor' } );

	const { title, category, featuredUrl, rows } = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		const empty = { title: '', category: '', featuredUrl: '', rows: [] };
		// In the shared single-project template there is no queried project, so only mirror a real
		// `perego_project` post; anything else falls back to the placeholder structure.
		if ( ! editor || editor.getCurrentPostType?.() !== 'perego_project' ) {
			return empty;
		}

		const core = select( 'core' );
		const meta = editor.getEditedPostAttribute( 'meta' ) || {};
		const featuredId = editor.getEditedPostAttribute( 'featured_media' );
		const media = featuredId ? core.getMedia( featuredId ) : null;
		const termIds = editor.getEditedPostAttribute( 'perego_project_category' ) || [];
		const term = termIds.length
			? core.getEntityRecord( 'taxonomy', 'perego_project_category', termIds[ 0 ] )
			: null;

		return {
			title: editor.getEditedPostAttribute( 'title' ) || '',
			category: term?.name || '',
			featuredUrl: media?.source_url || '',
			rows: metaRows( {
				client: meta._perego_client,
				year: meta._perego_year,
				role: meta._perego_role,
				deliverables: meta._perego_deliverables,
			} ),
		};
	}, [] );

	return (
		<div { ...blockProps } onClick={ ( event ) => {
			// Neutralize the preview's real links so a click never navigates the editor away.
			if ( event.target.closest( 'a, button' ) ) {
				event.preventDefault();
			}
		} }>
			<ProjectHeroSkeleton title={ title } category={ category } featuredUrl={ featuredUrl } rows={ rows } />
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
