/**
 * Perego legal-updated block — editor registration. Server-rendered; markup from LegalUpdatedRenderer.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit: () => __( 'Legal "Last updated" date. Rendered by LegalUpdatedRenderer.', 'perego-site' ),
	save: () => null,
} );
