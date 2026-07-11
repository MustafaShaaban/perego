/**
 * Perego service-hero block — editor registration. Server-rendered (save returns null); the
 * front-end markup (eyebrow + current-service H1 + shared service tabs) comes from
 * ServiceHeroRenderer, driven by the queried service and ServiceContent.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit: () =>
		__(
			'Service hero — eyebrow, service name (H1), and the four-service tabs. Rendered by ServiceHeroRenderer.',
			'perego-site'
		),
	save: () => null,
} );
