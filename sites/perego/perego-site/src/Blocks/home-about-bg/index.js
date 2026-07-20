/**
 * Home About Background editor: the image is locked decorative chrome, so the canvas presents
 * the real public background while the adjacent native blocks own the editable content.
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import './style.scss';

function Edit( { attributes } ) {
	const blockProps = useBlockProps( { className: 'perego-home-about-bg__editor' } );

	return (
		<div { ...blockProps }>
			<ServerSideRender block={ metadata.name } attributes={ attributes } />
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
