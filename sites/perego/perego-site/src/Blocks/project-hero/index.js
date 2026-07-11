/**
 * Perego project-hero block — editor registration. Server-rendered (save returns null); the
 * front-end markup comes from ProjectHeroRenderer, driven by the queried project + PortfolioContent.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit: () =>
		__(
			'Project hero — breadcrumb, category, title (H1), and meta. Rendered by ProjectHeroRenderer.',
			'perego-site'
		),
	save: () => null,
} );
