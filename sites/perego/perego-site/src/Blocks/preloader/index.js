/**
 * Perego preloader block — editor registration. Server-rendered (save returns null); the
 * front-end markup comes from PreloaderRenderer, the timing/session logic from view.js.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: () => __( 'Preloader — shown on first homepage visit per session. Rendered by PreloaderRenderer.', 'perego-site' ),
	save: () => null,
} );
