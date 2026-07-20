/**
 * Service Hero editor: native Service fields remain the source of copy; this canvas renders the
 * approved front-end component so editors can verify the H1, tabs, and background in context.
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import './style.scss';

function Edit( { attributes } ) {
	const blockProps = useBlockProps( { className: 'perego-service-hero__editor' } );

	return (
		<div { ...blockProps } onClick={ ( event ) => {
			if ( event.target.closest( 'a, button' ) ) {
				event.preventDefault();
			}
		} }>
			<ServerSideRender block={ metadata.name } attributes={ attributes } />
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
