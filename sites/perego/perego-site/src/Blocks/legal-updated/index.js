/**
 * Perego legal-updated block — editor registration (spec 021 C15; DECISIONS 2026-07-22). The canvas renders the
 * REAL markup (`LegalUpdatedSkeleton` in preview.js), styled by the theme's `main.css` via
 * `add_editor_style`, replacing an `edit()` that returned a bare sentence.
 *
 * **Locked** preview with no controls — see preview.js for what derives each part.
 * Server-rendered (save returns null).
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { LegalUpdatedSkeleton } from './preview';
import metadata from './block.json';
import './style.scss';

function Edit() {
	return (
		<div { ...useBlockProps( { className: 'perego-legal-updated__editor' } ) }
			onClick={ ( event ) => {
				if ( event.target.closest( 'a, button' ) ) {
					event.preventDefault();
				}
			} }>
			<LegalUpdatedSkeleton />
		</div>
	);
}

registerBlockType( metadata.name, { edit: Edit, save: () => null } );
