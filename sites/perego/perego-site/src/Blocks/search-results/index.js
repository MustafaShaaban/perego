/**
 * Perego search-results block — editor registration (spec 021 C12; DECISIONS 2026-07-22). The canvas
 * renders the REAL search page (`SearchResultsSkeleton` in preview.js), styled by the theme's
 * `main.css` via `add_editor_style`, replacing an `edit()` that returned a bare sentence.
 *
 * **Locked** preview with no controls: everything on this page is derived from the visitor's `?s=`
 * query. The result cards are a small sample — a real search renders as many as it matched, so the
 * canvas shows the grid rhythm rather than pretending to run a query. Server-rendered (save returns
 * null).
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { SearchResultsSkeleton } from './preview';
import metadata from './block.json';
import './style.scss';

function Edit() {
	return (
		<div { ...useBlockProps( { className: 'perego-search-results__editor' } ) }
			onClick={ ( event ) => {
				if ( event.target.closest( 'a, button' ) ) {
					event.preventDefault();
				}
			} }
			onSubmit={ ( event ) => event.preventDefault() }>
			<SearchResultsSkeleton />
		</div>
	);
}

registerBlockType( metadata.name, { edit: Edit, save: () => null } );
