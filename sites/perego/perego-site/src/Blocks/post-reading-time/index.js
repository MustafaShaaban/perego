/**
 * Perego post-reading-time block — editor registration (spec 021 C12; DECISIONS 2026-07-22). The canvas
 * renders the REAL estimate chip (`PostReadingTimeSkeleton` in preview.js), styled by the theme's
 * `main.css` via `add_editor_style`, replacing an `edit()` that returned a bare sentence.
 *
 * **Locked** preview with no controls: the estimate is computed from the post's word count on the front
 * end, and the wording is a translation-catalogue string. The canvas shows the seed label, because
 * recomputing the estimate in the editor would duplicate `PostReadingTimeRenderer::minutesFor()` and
 * drift from it — the chip's presence and styling is what an editor needs to see here.
 * Server-rendered (save returns null).
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { PostReadingTimeSkeleton } from './preview';
import metadata from './block.json';

function Edit() {
	return (
		<span { ...useBlockProps( { className: 'perego-post-reading-time__editor' } ) }>
			<PostReadingTimeSkeleton />
		</span>
	);
}

registerBlockType( metadata.name, { edit: Edit, save: () => null } );
