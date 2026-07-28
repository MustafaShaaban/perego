/**
 * Perego join-form block — editor registration (spec 021 C13; DECISIONS 2026-07-22). The canvas renders
 * the REAL careers form (`JoinFormSkeleton` in preview.js), styled by the theme's `main.css` via
 * `add_editor_style`, replacing a "rendered on the front end" placeholder.
 *
 * **Locked** preview with no controls: every label and message is a translation-catalogue string and
 * the upload/validation lifecycle belongs to view.js. Server-rendered (save returns null).
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { JoinFormSkeleton } from './preview';
import './style.scss';

function Edit() {
	return (
		<div { ...useBlockProps( { className: 'perego-join-form__editor' } ) }
			onClick={ ( event ) => {
				if ( event.target.closest( 'button, input, label' ) ) {
					event.preventDefault();
				}
			} }
			onSubmit={ ( event ) => event.preventDefault() }>
			<JoinFormSkeleton />
		</div>
	);
}

registerBlockType( 'perego-theme/join-form', { edit: Edit, save: () => null } );
