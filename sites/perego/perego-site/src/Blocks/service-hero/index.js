/**
 * Service Hero editor (spec 021 C7 / T025; DECISIONS 2026-07-22). The canvas renders the REAL hero markup
 * (`ServiceHeroSkeleton` in preview.js), styled by the theme's `main.css` via `add_editor_style`, instead of
 * a `ServerSideRender` iframe — the static-layout live-canvas standard shared with the homepage blocks.
 *
 * The block has no attributes: the `h1` is the queried Service post's title and the tab labels come from each
 * Service's teaser label, with the `ServiceContent` seed as the fallback — exactly how the renderer resolves
 * them. So this is a **locked** preview with no controls; the copy is edited on each Service itself. When the
 * block is edited on a `perego_service` post, the canvas mirrors that post (its live title as the `h1`, and
 * its canonical service slug as the active tab); anywhere else (e.g. the shared template) it shows the seed.
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { SEED_TABS_EN, ServiceHeroSkeleton } from './preview';
import metadata from './block.json';
import './style.scss';

function Edit() {
	const blockProps = useBlockProps( { className: 'perego-service-hero__editor' } );

	// Mirror the Service being edited, when there is one. `core/editor` is absent in some contexts and the
	// slug meta may not be REST-exposed, so every lookup falls back to the seed.
	const { postTitle, serviceSlug } = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		if ( ! editor || editor.getCurrentPostType?.() !== 'perego_service' ) {
			return { postTitle: '', serviceSlug: '' };
		}
		const meta = editor.getEditedPostAttribute( 'meta' ) || {};
		return {
			postTitle: editor.getEditedPostAttribute( 'title' ) || '',
			serviceSlug: meta._perego_service_slug || '',
		};
	}, [] );

	const activeSlug = SEED_TABS_EN.some( ( tab ) => tab.slug === serviceSlug )
		? serviceSlug
		: SEED_TABS_EN[ 0 ].slug;
	const seedTitle = ( SEED_TABS_EN.find( ( tab ) => tab.slug === activeSlug ) || SEED_TABS_EN[ 0 ] ).fullName;

	return (
		<div { ...blockProps } onClick={ ( event ) => {
			// Neutralize the preview's real links so a click never navigates the editor away.
			if ( event.target.closest( 'a, button' ) ) {
				event.preventDefault();
			}
		} }>
			<ServiceHeroSkeleton title={ postTitle || seedTitle } activeSlug={ activeSlug } />
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
