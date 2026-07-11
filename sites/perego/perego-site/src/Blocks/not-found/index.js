/**
 * Perego not-found block — editor registration. Server-rendered (save returns null); the front-end
 * markup comes from NotFoundRenderer (language-aware via GlobalContent).
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit: () => __( '404 composition. Rendered by NotFoundRenderer.', 'perego-site' ),
	save: () => null,
} );
