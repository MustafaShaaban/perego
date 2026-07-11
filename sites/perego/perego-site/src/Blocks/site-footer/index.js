/**
 * Perego site-footer block — editor registration. Server-rendered (save returns null); the
 * front-end markup comes from SiteFooterRenderer.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit: () => __( 'Site footer — rendered on the front end by SiteFooterRenderer.', 'perego-site' ),
	save: () => null,
} );
