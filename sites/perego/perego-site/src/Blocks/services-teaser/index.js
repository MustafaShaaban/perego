/**
 * Perego services-teaser block — editor registration. Server-rendered (save returns null); the
 * front-end markup comes from ServicesTeaserRenderer.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit: () =>
		__(
			'Services teaser — the four home service cards. Rendered by ServicesTeaserRenderer.',
			'perego-site'
		),
	save: () => null,
} );
