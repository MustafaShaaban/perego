/**
 * Perego journal-header block — editor registration (spec 021 C12; DECISIONS 2026-07-22). The canvas
 * renders the REAL archive header (`JournalHeaderSkeleton` in preview.js), styled by the theme's
 * `main.css` via `add_editor_style`, replacing an `edit()` that returned a bare sentence.
 *
 * The title and lead are edited in place with `RichText` (English canvas) and per locale in the
 * Inspector; empty means "use the seed copy from `GlobalContent::journal()`", so an unedited block
 * renders byte-identically to before. The breadcrumb is a derived route link and has no control.
 * Server-rendered (save returns null).
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';
import { TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { LanguagePair } from '../../Editor/LanguagePair';
import { PanelSection } from '../../Editor/PanelSection';
import { JournalHeaderSkeleton, SEED } from './preview';
import metadata from './block.json';
import './style.scss';

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'perego-journal-header__editor' } );

	const titleNode = (
		<RichText tagName="span" allowedFormats={ [] }
			value={ attributes.titleEn }
			onChange={ ( titleEn ) => setAttributes( { titleEn } ) }
			placeholder={ SEED.title } />
	);
	const leadNode = (
		<RichText tagName="span" allowedFormats={ [] }
			value={ attributes.leadEn }
			onChange={ ( leadEn ) => setAttributes( { leadEn } ) }
			placeholder={ SEED.lead } />
	);

	return (
		<div { ...blockProps } onClick={ ( event ) => {
			// Neutralize the preview's breadcrumb link so a click never navigates the editor away;
			// the in-canvas RichText title/lead manage their own clicks.
			const link = event.target.closest( 'a' );
			if ( link && ! link.isContentEditable ) {
				event.preventDefault();
			}
		} }>
			<InspectorControls>
				<PanelSection title={ __( 'Journal heading', 'perego-site' ) } initialOpen>
					<LanguagePair
						label={ __( 'Title', 'perego-site' ) }
						en={ attributes.titleEn } ar={ attributes.titleAr }
						onChangeEn={ ( titleEn ) => setAttributes( { titleEn } ) }
						onChangeAr={ ( titleAr ) => setAttributes( { titleAr } ) }
						placeholderEn={ SEED.title } placeholderAr="مدونة بيريجو" />
					<LanguagePair
						label={ __( 'Lead', 'perego-site' ) }
						Control={ TextareaControl }
						en={ attributes.leadEn } ar={ attributes.leadAr }
						onChangeEn={ ( leadEn ) => setAttributes( { leadEn } ) }
						onChangeAr={ ( leadAr ) => setAttributes( { leadAr } ) }
						placeholderEn={ SEED.lead }
						help={ __( 'Leave a field empty to keep the current wording.', 'perego-site' ) } />
				</PanelSection>
			</InspectorControls>
			<JournalHeaderSkeleton titleNode={ titleNode } leadNode={ leadNode } />
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
