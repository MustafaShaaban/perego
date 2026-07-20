/**
 * Perego journal-comments block — editor registration. Server-rendered; markup from
 * JournalCommentsRenderer (the current post's approved comments in the handoff card design).
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: () => __( 'Journal comments. Rendered by JournalCommentsRenderer.', 'perego-site' ),
	save: () => null,
} );
