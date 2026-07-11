/**
 * Perego legal-toc block — editor registration. Server-rendered (save returns null); the front-end
 * aside (review note + last-updated + heading-derived TOC) comes from LegalTocRenderer.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit: () => __( 'Legal TOC (generated from the page headings). Rendered by LegalTocRenderer.', 'perego-site' ),
	save: () => null,
} );
