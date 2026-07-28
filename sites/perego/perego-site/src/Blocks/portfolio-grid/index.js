/**
 * Perego portfolio-grid block — editor registration (spec 021 C11; spec 023 canvas ordering).
 *
 * WHY THIS IS NO LONGER ServerSideRender. Spec 021 classed this a dynamic/query block and kept
 * `<ServerSideRender>` on the rule that a block whose content is a runtime query previews rather than
 * rebuilds. That rule assumed the editing surface was the block's *settings*. The owner's 2026-07-28
 * request makes the editing surface the query result itself — dragging the real cards to order them —
 * and nothing inside an SSR iframe can be dragged. The rule is superseded here and in
 * `service-selected-work`, for that reason only; see DECISIONS 2026-07-28. `preview.js` now carries
 * the markup and `parity.test.js` pins it to the PHP, which the SSR version had no need of.
 *
 * WHAT THE CANVAS DELIBERATELY DOES NOT REPRODUCE. It shows every project, unfiltered and unpaginated,
 * with the chips and pager rendered but inert. `PER_PAGE` is 9; dragging a card from position 52 to
 * position 3 is impossible through a nine-card window, and reproducing `view.js`'s filter and pager
 * would be a second implementation of behaviour that already has its own tests.
 *
 * The Inspector still makes the archive's editorial copy editable per locale. Empty means "use the seed
 * copy from `PortfolioContent`", so an unedited block renders byte-identically to before.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Button, TextareaControl, ToggleControl } from '@wordpress/components';
import { __, sprintf, _n } from '@wordpress/i18n';
import { LanguagePair } from '../../Editor/LanguagePair';
import { LinkPicker, linkFromAttributes, linkToAttributes } from '../../Editor/LinkPicker';
import { PanelSection } from '../../Editor/PanelSection';
import { SortableItem } from '../../Editor/SortableItem';
import { useCanvasSort } from '../../Editor/useCanvasSort';
import { PortfolioCard, PortfolioGridSkeleton, placeholderCards } from './preview';
import { useProjectCards } from './useProjectCards';
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

/** The chip labels, mirroring `PortfolioContent::filterLabels()` — 'all' first, then the four categories. */
const FILTER_LABELS = {
	all: __( 'All', 'perego-site' ),
	video: __( 'Video Editing', 'perego-site' ),
	motion: __( '2D Motion Graphics', 'perego-site' ),
	design: __( 'Graphic Design', 'perego-site' ),
	web: __( 'Website Making', 'perego-site' ),
};

function Edit( { attributes, setAttributes, isSelected } ) {
	const blockProps = useBlockProps( { className: 'perego-portfolio-grid__editor' } );
	const projectOrder = attributes.projectOrder || [];

	const { cards } = useProjectCards( {
		featuredOnly: attributes.featuredOnly,
		projectOrder,
		filterLabels: FILTER_LABELS,
	} );

	// Seed cards until REST resolves, so the block previews as itself rather than as nothing.
	const resolved = cards || placeholderCards();
	const isReady = Array.isArray( cards ) && cards.length > 0;

	const sort = useCanvasSort( {
		length: resolved.length,
		isEnabled: isSelected && isReady,
		describeItem: ( index ) => resolved[ index ]?.title || '',
		onReorder: ( reorder ) => {
			// The stored order names every card, not just the moved one — an order that named only
			// what had been touched could not express "this one now comes third".
			setAttributes( { projectOrder: reorder( resolved.map( ( card ) => card.id ) ) } );
		},
	} );

	const unplaced = projectOrder.length === 0
		? 0
		: resolved.filter( ( card ) => ! projectOrder.includes( card.id ) ).length;
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
				<PanelSection title={ __( 'Order', 'perego-site' ) } initialOpen>
					<p className="perego-editor-help">
						{ isSelected
							? __( 'Drag a card to reorder it, or use the Move earlier / Move later buttons on each card.', 'perego-site' )
							: __( 'Select this block to reorder its cards.', 'perego-site' ) }
					</p>
					<p className="perego-editor-help">
						{ __( 'This order applies to this grid only — the Work archive keeps its own.', 'perego-site' ) }
					</p>
					{ unplaced > 0 && (
						<p className="perego-editor-help">
							{ sprintf(
								/* translators: %d: number of projects published since the order was set. */
								_n(
									'%d project published since this order was set appears at the end.',
									'%d projects published since this order was set appear at the end.',
									unplaced,
									'perego-site'
								),
								unplaced
							) }
						</p>
					) }
					{ projectOrder.length > 0 && (
						<Button variant="secondary" onClick={ () => setAttributes( { projectOrder: [] } ) }>
							{ __( 'Reset to automatic order', 'perego-site' ) }
						</Button>
					) }
				</PanelSection>
				<PanelSection title={ __( 'Which projects', 'perego-site' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Featured projects only', 'perego-site' ) }
						help={ __( 'The home page leads with a shortlist. The Work archive should show everything.', 'perego-site' ) }
						checked={ attributes.featuredOnly }
						onChange={ ( featuredOnly ) => setAttributes( { featuredOnly } ) }
					/>
				</PanelSection>
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

			<p className="perego-editor-help perego-portfolio-grid__note">
				{ isReady
					? sprintf(
						/* translators: %d: total number of projects shown in the canvas. */
						__( 'All %d projects are shown here so you can order them. Visitors see 9 at a time, filtered by service. The preview uses the English projects; the Arabic page shows their translations in the same order.', 'perego-site' ),
						resolved.length
					)
					: __( 'Loading projects…', 'perego-site' ) }
			</p>

			<PortfolioGridSkeleton
				strings={ {
					heading: attributes.headingEn || SEED.headingEn,
					uiHome: attributes.showBreadcrumb ? __( 'Home', 'perego-site' ) : '',
					intro: attributes.introEn || SEED.introEn,
					demoNote: attributes.showDemoNote ? __( 'Example projects shown below.', 'perego-site' ) : '',
					groupLabel: __( 'Filter projects by service', 'perego-site' ),
					noResults: __( 'No projects match this filter yet.', 'perego-site' ),
					galleryBadge: __( 'Gallery', 'perego-site' ),
					ctaTitle: attributes.ctaTitleEn || SEED.ctaTitleEn,
					ctaBody: attributes.ctaBodyEn || SEED.ctaBodyEn,
					ctaButton: attributes.ctaButtonEn || SEED.ctaButtonEn,
				} }
				filterLabels={ FILTER_LABELS }
				cards={ resolved }
				renderCard={ ( card, index ) => (
					<SortableItem
						key={ card.id }
						index={ index }
						length={ resolved.length }
						sort={ sort }
						isEnabled={ isSelected && isReady }
						label={ card.title }
					>
						<PortfolioCard card={ card } galleryBadge={ __( 'Gallery', 'perego-site' ) } isEditor />
					</SortableItem>
				) }
			/>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
