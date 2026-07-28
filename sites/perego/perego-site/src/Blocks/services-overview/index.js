/**
 * Perego services-overview block — editor registration (spec 021 C14; DECISIONS 2026-07-22). The canvas
 * renders the REAL composition (preview.js), styled by the theme's `main.css` via `add_editor_style`,
 * replacing an `edit()` that returned a bare sentence.
 *
 * **Locked** preview with no controls: the tabs are a projection of the four Services (each label is
 * edited on that Service's own screen) and the prose comes from `ServiceContent`. Server-rendered
 * (save returns null).
 *
 * ⚠️ This block's only template, `archive-perego_service.html`, is currently **unreachable** on the
 * front end: `ServicePostType` registers the CPT with `has_archive => false`, so `/services/` 404s
 * while the service singles at `/services/<slug>/` work. The block still needs a real canvas — it is
 * insertable, and the template renders the moment the archive is enabled — but nothing here changes
 * the public site until that decision is made. Recorded in PROGRESS/DECISIONS for the owner.
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import {
	ServicesOverviewCtaSkeleton,
	ServicesOverviewFixedSkeleton,
	ServicesOverviewWorkSkeleton,
} from './preview';
import metadata from './block.json';

function Edit() {
	return (
		<div { ...useBlockProps( { className: 'perego-services-overview__editor' } ) }
			onClick={ ( event ) => {
				if ( event.target.closest( 'a, button' ) ) {
					event.preventDefault();
				}
			} }>
			<ServicesOverviewFixedSkeleton />
			<ServicesOverviewWorkSkeleton />
			<ServicesOverviewCtaSkeleton />
		</div>
	);
}

registerBlockType( metadata.name, { edit: Edit, save: () => null } );
