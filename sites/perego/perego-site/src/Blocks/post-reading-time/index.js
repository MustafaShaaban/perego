/**
 * Perego post-reading-time block — editor registration. Server-rendered; markup from PostReadingTimeRenderer.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit: () => __( 'Reading-time estimate. Rendered by PostReadingTimeRenderer.', 'perego-site' ),
	save: () => null,
} );
