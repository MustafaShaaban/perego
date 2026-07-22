/**
 * Perego portfolio-grid block — editor registration (spec 021 C11; DECISIONS 2026-07-22).
 *
 * This is a **dynamic/query block**: every card is a projection of the whole `perego_project` archive for the
 * current language, so per the spec's static-vs-dynamic rule it keeps `<ServerSideRender>` for the canvas
 * rather than a hand-rebuilt skeleton — there is no parity test for it by design. (A skeleton would also have
 * needed a 43 KB, 77-card fixture to pin, which pins nothing useful.) The SSR preview replaces an `edit()` that
 * returned a bare sentence and showed the editor nothing.
 *
 * Its render callback is context-free — it queries the archive itself rather than the queried object — so SSR
 * renders the real grid in the editor, including the filter chips and pager.
 *
 * The Inspector makes the archive's editorial copy editable per locale (heading, intro, and the closing CTA),
 * on the shared `../../Editor` primitives. Empty means "use the seed copy from `PortfolioContent`", so an
 * unedited block renders byte-identically to before. The demo note is the exception: it is a launch placeholder
 * ("Example projects shown below — to be replaced with Perego's real work"), so it gets an explicit toggle that
 * genuinely removes it.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { TextareaControl, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';
import { LanguagePair } from '../../Editor/LanguagePair';
import { LinkPicker, linkFromAttributes, linkToAttributes } from '../../Editor/LinkPicker';
import { PanelSection } from '../../Editor/PanelSection';
import metadata from './block.json';
import './style.scss';

/** Seed copy mirrored from `PortfolioContent` — shown as placeholders so "empty" reads as "this is what you get". */
const SEED = {
	headingEn: 'Our Work',
	headingAr: 'أعمالنا',
	introEn: 'A selection of projects across video, motion, design, and web.',
	introAr: 'مجموعة مختارة من المشاريع في الفيديو والموشن والتصميم والويب.',
	ctaTitleEn: 'Have a project in mind?',
	ctaTitleAr: 'هل لديك مشروع في ذهنك؟',
	ctaBodyEn: "Tell us what you're working on and we'll help you shape the plan.",
	ctaBodyAr: 'أخبرنا بما تعمل عليه وسنساعدك في بلورة الخطة.',
	ctaButtonEn: 'Start a Project',
	ctaButtonAr: 'ابدأ مشروعك',
};

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'perego-portfolio-grid__editor' } );
	// Every editable field is the same En/Ar attribute pair, so the wiring is built once. The seed copy
	// becomes the placeholder, making "empty" read as "this is what the page will say".
	const pair = ( key ) => ( {
		en: attributes[ `${ key }En` ],
		ar: attributes[ `${ key }Ar` ],
		onChangeEn: ( value ) => setAttributes( { [ `${ key }En` ]: value } ),
		onChangeAr: ( value ) => setAttributes( { [ `${ key }Ar` ]: value } ),
		placeholderEn: SEED[ `${ key }En` ],
		placeholderAr: SEED[ `${ key }Ar` ],
	} );

	return (
		<div { ...blockProps } onClick={ ( event ) => {
			// Neutralize the preview's real links and filter buttons so a click never leaves the editor.
			if ( event.target.closest( 'a, button' ) ) {
				event.preventDefault();
			}
		} }>
			<InspectorControls>
				<PanelSection title={ __( 'Archive heading', 'perego-site' ) } initialOpen>
					<LanguagePair
						label={ __( 'Title', 'perego-site' ) }
						{ ...pair( 'heading' ) }
					/>
					<LanguagePair
						label={ __( 'Intro', 'perego-site' ) }
						Control={ TextareaControl }
						{ ...pair( 'intro' ) }
						help={ __( 'Leave a field empty to keep the current wording.', 'perego-site' ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show the demo note', 'perego-site' ) }
						help={ __( 'The temporary "example projects" line under the intro. Turn this off once real projects are published.', 'perego-site' ) }
						checked={ attributes.showDemoNote }
						onChange={ ( showDemoNote ) => setAttributes( { showDemoNote } ) }
					/>
				</PanelSection>
				<PanelSection title={ __( 'Closing call to action', 'perego-site' ) }>
					<LanguagePair
						label={ __( 'Title', 'perego-site' ) }
						{ ...pair( 'ctaTitle' ) }
					/>
					<LanguagePair
						label={ __( 'Body', 'perego-site' ) }
						Control={ TextareaControl }
						{ ...pair( 'ctaBody' ) }
					/>
					<LanguagePair
						label={ __( 'Button text', 'perego-site' ) }
						{ ...pair( 'ctaButton' ) }
					/>
					<LinkPicker
						showLabel={ false }
						link={ linkFromAttributes( attributes ) }
						hrefText={ __( 'Button link', 'perego-site' ) }
						hrefHelp={ __( 'Leave empty for the contact page.', 'perego-site' ) }
						onChange={ ( next ) => setAttributes( linkToAttributes( next ) ) }
					/>
				</PanelSection>
			</InspectorControls>
			<ServerSideRender block={ metadata.name } attributes={ attributes } />
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
