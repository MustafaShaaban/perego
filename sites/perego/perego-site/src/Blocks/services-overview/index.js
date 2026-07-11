/**
 * Perego services-overview block — editor registration. Server-rendered (save returns null); the
 * front-end markup comes from ServicesOverviewRenderer, driven by ServiceContent (language-aware).
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit: () =>
		__(
			'Services overview — Our Services intro, four cards, process, CTA. Rendered by ServicesOverviewRenderer.',
			'perego-site'
		),
	save: () => null,
} );
