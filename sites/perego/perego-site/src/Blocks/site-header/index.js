/**
 * Perego site-header block — editor registration. Server-rendered (save returns null); the
 * front-end markup comes from SiteHeaderRenderer, the interactivity from view.js.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit: () => __( 'Site header — rendered on the front end by SiteHeaderRenderer.', 'perego-site' ),
	save: () => null,
} );
