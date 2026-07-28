/**
 * Service Selected Work editor: the Service record controls the portfolio query; this canvas
 * renders the approved public masonry (or Website Making showcase) for in-context review.
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';

function Edit( { attributes } ) {
	const blockProps = useBlockProps( { className: 'perego-service-selected-work__editor' } );

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
