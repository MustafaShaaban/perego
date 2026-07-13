import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: () => __( 'Contact service choices are rendered from published services.', 'perego-site' ),
	save: () => null,
} );
