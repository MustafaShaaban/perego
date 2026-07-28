/**
 * Perego post-breadcrumb block — editor registration (spec 021 C12; DECISIONS 2026-07-22). The canvas
 * renders the REAL breadcrumb (`PostBreadcrumbSkeleton` in preview.js), styled by the theme's `main.css`
 * via `add_editor_style`, replacing an `edit()` that returned a bare sentence.
 *
 * **Locked** preview with no controls — every part is derived: the Home/Journal steps are route links and
 * the last step is the post's own title, which the canvas mirrors live from the post being edited.
 * Server-rendered (save returns null).
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { PostBreadcrumbSkeleton } from './preview';
import metadata from './block.json';

function Edit() {
	const blockProps = useBlockProps( { className: 'perego-post-breadcrumb__editor' } );

	const { title, isPage } = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		const type = editor?.getCurrentPostType?.();

		return {
			title: editor?.getEditedPostAttribute?.( 'title' ) || __( 'Post title', 'perego-site' ),
			// The renderer drops the Journal step on a page; mirror that so the canvas matches.
			isPage: type === 'page',
		};
	}, [] );

	return (
		<div { ...blockProps } onClick={ ( event ) => {
			// Neutralize the preview's route links so a click never navigates the editor away.
			if ( event.target.closest( 'a' ) ) {
				event.preventDefault();
			}
		} }>
			<PostBreadcrumbSkeleton title={ title } isPage={ isPage } />
		</div>
	);
}

registerBlockType( metadata.name, { edit: Edit, save: () => null } );
