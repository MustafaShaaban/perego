/**
 * Perego home-about-bg block — editor registration. Server-rendered (save returns null); the
 * front-end markup comes from HomeAboutBgRenderer.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit: () =>
		__(
			'Home About Background — the About section’s background image. Rendered by HomeAboutBgRenderer.',
			'perego-site'
		),
	save: () => null,
} );
