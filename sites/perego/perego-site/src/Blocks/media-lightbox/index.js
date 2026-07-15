/**
 * Editor registration for perego-theme/media-lightbox. Server-rendered (save returns null); the
 * dialog markup comes from MediaLightboxRenderer and view.js wires every [data-image]/[data-video]/
 * [data-gallery] trigger on the page to it.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit: () =>
		__(
			'Media lightbox — the site-wide accessible dialog for image/video/gallery triggers. Rendered once by MediaLightboxRenderer.',
			'perego-site'
		),
	save: () => null,
} );
