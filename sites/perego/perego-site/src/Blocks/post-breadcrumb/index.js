/**
 * Perego post-breadcrumb block — editor registration. Server-rendered; markup from PostBreadcrumbRenderer.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit: () => __( 'Post breadcrumb. Rendered by PostBreadcrumbRenderer.', 'perego-site' ),
	save: () => null,
} );
