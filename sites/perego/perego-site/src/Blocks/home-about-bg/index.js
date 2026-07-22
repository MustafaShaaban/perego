/**
 * Home About Background editor (spec 021 C5 / T014; DECISIONS 2026-07-22). The image is locked decorative
 * chrome, so the canvas renders the REAL background markup (`HomeAboutBgSkeleton` in preview.js), styled by
 * the theme's `main.css` via `add_editor_style`, instead of a `ServerSideRender` iframe — the static-layout
 * live-canvas standard shared with the other homepage blocks. The About section's editable content is the
 * adjacent native `wp:post-content` (the "locked structural wrappers" of T014); this block has no controls.
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { HomeAboutBgSkeleton } from './preview';
import metadata from './block.json';
import './style.scss';

function Edit() {
	const blockProps = useBlockProps( { className: 'perego-home-about-bg__editor' } );

	return (
		<div { ...blockProps }>
			<HomeAboutBgSkeleton />
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
