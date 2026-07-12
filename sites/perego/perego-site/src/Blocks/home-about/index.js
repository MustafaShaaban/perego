/**
 * Perego home-about block — editor registration. Server-rendered (save returns null); the
 * front-end markup comes from HomeAboutRenderer.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit: () =>
		__(
			'Home About — the About Us / Our mission panels. Rendered by HomeAboutRenderer.',
			'perego-site'
		),
	save: () => null,
} );
