/**
 * Perego clients-carousel block — editor registration. Server-rendered; markup from
 * ClientsCarouselRenderer, enhanced by Swiper in view.js.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit: () => __( 'Clients carousels (Corporate + Individual). Rendered by ClientsCarouselRenderer.', 'perego-site' ),
	save: () => null,
} );
