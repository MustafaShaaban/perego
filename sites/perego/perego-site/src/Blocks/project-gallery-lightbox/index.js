/**
 * Perego project-gallery-lightbox block — editor registration. Server-rendered (save returns null);
 * markup from ProjectGalleryLightboxRenderer, the dialog/focus-trap logic from view.js.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit: () =>
		__(
			'Project gallery lightbox — thumbnails that open an accessible dialog. Rendered by ProjectGalleryLightboxRenderer.',
			'perego-site'
		),
	save: () => null,
} );
