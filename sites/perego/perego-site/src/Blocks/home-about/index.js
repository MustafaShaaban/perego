/**
 * Home About editor. The About copy is the front page's own content (spec 004 T012), which is why the
 * Front Page template canvas used to show only core's "This is the Content block…" placeholder:
 * `core/post-content` has no post to render while a template is being edited.
 *
 * This block gives that canvas the page. `useEntityBlockEditor` + controlled inner blocks is the same
 * mechanism core's own post-content block uses in the page editor, so the panels appear as the real
 * `glass-panel` blocks — styled by the theme's `main.css` via `add_editor_style` — and every heading and
 * paragraph is natively editable. Edits write to the Home page, so the Site Editor's save panel lists it
 * alongside the template.
 *
 * The template is shared by both languages, so the canvas binds to the English front page; Arabic copy is
 * edited on the Arabic page, which is where Polylang keeps it.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { PanelBody, Placeholder, Spinner } from '@wordpress/components';
import { useEntityBlockEditor } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

/** The front end's post-content wrapper classes, so canvas padding and width match the site. */
const CANVAS_CLASSNAME = 'entry-content wp-block-post-content has-global-padding is-layout-constrained';

function LanguageNote() {
	return (
		<InspectorControls>
			<PanelBody title={ __( 'About panels', 'perego-site' ) }>
				<p className="perego-editor-help">
					{ __(
						'These panels are the page’s own content — the same text whether you edit it here or on the page itself. Each language keeps its copy on its own front page.',
						'perego-site'
					) }
				</p>
			</PanelBody>
		</InspectorControls>
	);
}

/** The canvas, once the page to bind to is known. Split out so the hook order stays stable. */
function AboutCanvas( { postId, postType } ) {
	const blockProps = useBlockProps( { className: CANVAS_CLASSNAME } );
	const [ blocks, onInput, onChange ] = useEntityBlockEditor( 'postType', postType, { id: postId } );
	const innerBlocksProps = useInnerBlocksProps( blockProps, { value: blocks, onInput, onChange } );

	return (
		<>
			<LanguageNote />
			<div { ...innerBlocksProps } />
		</>
	);
}

function Edit( { context } ) {
	const blockProps = useBlockProps();
	// `page_on_front` comes from the site settings entity, so this resolves for anyone who can open the
	// Site Editor; `undefined` means the request is still in flight, `0` means no static front page.
	const frontPageId = useSelect(
		( select ) => select( 'core' ).getEntityRecord( 'root', 'site' )?.page_on_front,
		[]
	);

	// Editing a page (English or Arabic) gives us that page as context, and it is the one to bind to —
	// otherwise the Arabic page's editor would show the English panels while the site rendered Arabic.
	// Only the template editor has no post, and there the front page is the right subject.
	if ( context?.postId ) {
		return <AboutCanvas postId={ context.postId } postType={ context.postType || 'page' } />;
	}

	if ( frontPageId === undefined ) {
		return (
			<div { ...blockProps }>
				<Placeholder icon="align-left" label={ __( 'About panels', 'perego-site' ) }>
					<Spinner />
				</Placeholder>
			</div>
		);
	}

	if ( ! frontPageId ) {
		return (
			<div { ...blockProps }>
				<Placeholder icon="align-left" label={ __( 'About panels', 'perego-site' ) }>
					{ __(
						'No static front page is set, so there is no content to show here. Settings → Reading decides which page this is.',
						'perego-site'
					) }
				</Placeholder>
			</div>
		);
	}

	return <AboutCanvas postId={ frontPageId } postType="page" />;
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
