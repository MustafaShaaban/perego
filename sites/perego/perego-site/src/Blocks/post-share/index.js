/**
 * Perego post-share block — editor registration (client request 2026-07-27).
 *
 * Locked preview with no controls: which networks appear and where the links point are decided by
 * `PostShareRenderer` from the post's own permalink, so there is nothing for an editor to configure.
 * The canvas renders the real row so its placement in the article is visible. Server-rendered
 * (save returns null).
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { PostShareSkeleton } from './preview';
import metadata from './block.json';
import './style.scss';

function Edit() {
	return (
		<div { ...useBlockProps( { className: 'perego-post-share__editor' } ) }>
			<PostShareSkeleton />
		</div>
	);
}

registerBlockType( metadata.name, { edit: Edit, save: () => null } );
