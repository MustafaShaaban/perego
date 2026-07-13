/**
 * Perego portfolio-grid block — editor registration. Server-rendered (save returns null); the
 * front-end markup comes from PortfolioGridRenderer, the filter logic from view.js.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit: () =>
		__(
			'Portfolio grid — filterable project cards. Rendered by PortfolioGridRenderer.',
			'perego-site'
		),
	save: () => null,
} );
