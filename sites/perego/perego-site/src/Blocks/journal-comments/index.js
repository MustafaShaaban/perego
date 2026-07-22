/**
 * Perego journal-comments block — editor registration (spec 021 C12; DECISIONS 2026-07-22). The canvas
 * renders the REAL comments section (`JournalCommentsSkeleton` in preview.js), styled by the theme's
 * `main.css` via `add_editor_style`, replacing an `edit()` that returned a bare sentence.
 *
 * **Locked** preview with no controls: comments are reader data and the form's labels are
 * translation-catalogue strings. The three comments shown are a design preview, not the post's own
 * thread — see preview.js. Server-rendered (save returns null); the submit/reply behaviour is view.js.
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { JournalCommentsSkeleton } from './preview';
import metadata from './block.json';

function Edit() {
	return (
		<div { ...useBlockProps( { className: 'perego-journal-comments__editor' } ) }
			onClick={ ( event ) => {
				// Neutralize the preview's real form and reply links so a click never submits or
				// navigates from inside the editor.
				if ( event.target.closest( 'a, button' ) ) {
					event.preventDefault();
				}
			} }
			onSubmit={ ( event ) => event.preventDefault() }>
			<JournalCommentsSkeleton />
		</div>
	);
}

registerBlockType( metadata.name, { edit: Edit, save: () => null } );
