/**
 * Editor registration for perego-theme/join-form. Server-rendered (PHP render_callback), so the
 * editor shows a static placeholder; the real form + upload lifecycle render on the front end.
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import './style.scss';

registerBlockType( 'perego-theme/join-form', {
	edit() {
		return (
			<div { ...useBlockProps() }>
				{ __( 'Join-us / CV form — rendered on the front end.', 'perego-site' ) }
			</div>
		);
	},
	save() {
		return null;
	},
} );
