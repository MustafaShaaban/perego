/**
 * Perego search-results block — editor registration. Server-rendered (save returns null); the
 * front-end markup comes from SearchResultsRenderer (real WP search, language-aware).
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit: () => __( 'Search results. Rendered by SearchResultsRenderer.', 'perego-site' ),
	save: () => null,
} );
