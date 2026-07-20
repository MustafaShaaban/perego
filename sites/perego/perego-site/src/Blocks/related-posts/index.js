/**
 * Perego related-posts block — editor registration. Server-rendered; markup from RelatedPostsRenderer.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: () => __( 'Related articles. Rendered by RelatedPostsRenderer.', 'perego-site' ),
	save: () => null,
} );
