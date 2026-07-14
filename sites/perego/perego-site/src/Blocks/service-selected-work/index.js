/**
 * Editor registration for perego-theme/service-selected-work. Server-rendered (save returns null);
 * the frontend markup comes from ServiceSelectedWorkRenderer, category-filtered to the current
 * service.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: () =>
		__(
			'Selected work — real projects for this service, opening the media lightbox. Rendered by ServiceSelectedWorkRenderer.',
			'perego-site'
		),
	save: () => null,
} );
