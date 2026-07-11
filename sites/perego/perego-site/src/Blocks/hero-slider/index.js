/**
 * Perego hero-slider block — editor registration. Server-rendered (save returns null); the
 * front-end markup comes from HeroSliderRenderer, the rotation/controls logic from view.js.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit: () =>
		__(
			'Hero slider — rotating homepage headlines. Rendered by HeroSliderRenderer.',
			'perego-site'
		),
	save: () => null,
} );
