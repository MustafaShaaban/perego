/**
 * Perego journal-header block — editor registration. Server-rendered; markup from JournalHeaderRenderer.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit: () => __( 'Journal header. Rendered by JournalHeaderRenderer.', 'perego-site' ),
	save: () => null,
} );
