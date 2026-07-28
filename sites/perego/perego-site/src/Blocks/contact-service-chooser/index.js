/**
 * Perego contact-service-chooser block — editor registration (spec 021 C13; DECISIONS 2026-07-22). The
 * canvas renders the REAL chooser (`ContactServiceChooserSkeleton` in preview.js), styled by the
 * theme's `main.css` via `add_editor_style`, replacing an `edit()` that returned a bare sentence.
 *
 * **Locked** preview with no controls: the buttons are a projection of the four Services (each label is
 * edited on that Service's own screen) and the copy is translation-catalogue text. Server-rendered
 * (save returns null); the multi-select behaviour is view.js.
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { ContactServiceChooserSkeleton } from './preview';
import metadata from './block.json';

function Edit() {
	return (
		<div { ...useBlockProps( { className: 'perego-contact-chooser__editor' } ) }
			onClick={ ( event ) => {
				if ( event.target.closest( 'button' ) ) {
					event.preventDefault();
				}
			} }>
			<ContactServiceChooserSkeleton />
		</div>
	);
}

registerBlockType( metadata.name, { edit: Edit, save: () => null } );
