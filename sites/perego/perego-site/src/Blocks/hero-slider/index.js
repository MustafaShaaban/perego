/**
 * Perego hero-slider block — editor registration (spec 021 C3 / T012; DECISIONS 2026-07-21).
 *
 * The canvas renders the REAL hero markup (see preview.js), styled by the theme's `main.css` (loaded via
 * `add_editor_style`), instead of a `ServerSideRender` iframe — the static-layout live-canvas standard,
 * matching site-header (C1) and site-footer (C2). The selected slide's English headline + supporting text
 * and the CTA are edited in place with `RichText`; the dots switch which slide is being composed. Each
 * slide's Arabic copy, the Arabic CTA, and slide management (add / duplicate / reorder / remove) live in
 * the Inspector on the shared `../../Editor` primitives.
 *
 * Slides are stored as one structured `slides` array attribute (`{ titleEn, textEn, titleAr, textAr }`,
 * spec 021 structured repeater); `normalizeSlides` upgrades the legacy per-slide attributes on read so
 * copy an editor already entered isn't lost. `HeroContent::resolve()` reads the same array on the front
 * end (attribute → old page-meta fallback → seed) and picks the current-locale variant. Server-rendered
 * (save returns null); the rotation/dot behaviour is unchanged, driven by view.js.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';
import { Button, TextControl, TextareaControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { LinkPicker, linkFromAttributes, linkToAttributes } from '../../Editor/LinkPicker';
import { PanelSection } from '../../Editor/PanelSection';
import { RepeaterControls } from '../../Editor/RepeaterControls';
import {
	HeroSkeleton,
	MAX_SLIDES,
	SEED_CTA_AR,
	SEED_CTA_EN,
	SEED_SLIDES_AR,
	SEED_SLIDES_EN,
	normalizeSlides,
	slideTitleTag,
} from './preview';
import metadata from './block.json';
import './style.scss';

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'perego-hero-slider__editor' } );
	const [ slides, setSlidesState ] = useState( () => normalizeSlides( attributes ) );
	const [ activeIndex, setActiveIndex ] = useState( 0 );
	const active = Math.min( activeIndex, slides.length - 1 );

	const setSlides = ( next ) => {
		const clamped = next.slice( 0, MAX_SLIDES );
		setSlidesState( clamped );
		setAttributes( { slides: clamped } );
	};
	const updateSlide = ( patch ) =>
		setSlides( slides.map( ( slide, index ) => ( index === active ? { ...slide, ...patch } : slide ) ) );

	const displaySlides = slides.map( ( slide, index ) => ( {
		title: slide.titleEn || SEED_SLIDES_EN[ index ]?.title || '',
		text: slide.textEn || SEED_SLIDES_EN[ index ]?.text || '',
	} ) );

	const TitleTag = slideTitleTag( active );
	const activeTitle = (
		<RichText tagName={ TitleTag } className="hero__title"
			value={ slides[ active ].titleEn }
			onChange={ ( titleEn ) => updateSlide( { titleEn } ) }
			placeholder={ SEED_SLIDES_EN[ active ]?.title || __( 'Headline', 'perego-site' ) } />
	);
	const activeText = (
		<RichText tagName="p" className="hero__text"
			value={ slides[ active ].textEn }
			onChange={ ( textEn ) => updateSlide( { textEn } ) }
			placeholder={ SEED_SLIDES_EN[ active ]?.text || __( 'Supporting text', 'perego-site' ) } />
	);
	const ctaNode = (
		<RichText tagName="a" className="btn btn--accent" allowedFormats={ [] }
			value={ attributes.ctaEn }
			onChange={ ( ctaEn ) => setAttributes( { ctaEn } ) }
			placeholder={ SEED_CTA_EN } />
	);

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelSection title={ sprintf( /* translators: %d: slide number */ __( 'Slide %d — Arabic', 'perego-site' ), active + 1 ) }>
					<TextControl __nextHasNoMarginBottom label={ __( 'Arabic headline', 'perego-site' ) }
						value={ slides[ active ].titleAr }
						onChange={ ( titleAr ) => updateSlide( { titleAr } ) }
						placeholder={ SEED_SLIDES_AR[ active ]?.title } />
					<TextareaControl __nextHasNoMarginBottom label={ __( 'Arabic supporting text', 'perego-site' ) }
						value={ slides[ active ].textAr }
						onChange={ ( textAr ) => updateSlide( { textAr } ) }
						placeholder={ SEED_SLIDES_AR[ active ]?.text } />
				</PanelSection>
				<PanelSection title={ __( 'Slides', 'perego-site' ) }>
					<p className="perego-editor-langpair__label">
						{ sprintf( /* translators: 1: current slide number, 2: total slides */ __( 'Editing slide %1$d of %2$d', 'perego-site' ), active + 1, slides.length ) }
					</p>
					<RepeaterControls items={ slides } index={ active } itemLabel={ __( 'slide', 'perego-site' ) }
						createCopy={ ( slide ) => ( { ...slide } ) }
						canDuplicate={ slides.length < MAX_SLIDES }
						canRemove={ slides.length > 1 }
						onChange={ ( next ) => { setSlides( next ); setActiveIndex( Math.min( active, next.length - 1 ) ); } } />
					<Button variant="secondary" disabled={ slides.length >= MAX_SLIDES }
						onClick={ () => {
							const next = [ ...slides, { titleEn: '', textEn: '', titleAr: '', textAr: '' } ];
							setSlides( next );
							setActiveIndex( next.length - 1 );
						} }>
						{ __( 'Add slide', 'perego-site' ) }
					</Button>
				</PanelSection>
				<PanelSection title={ __( 'CTA — Arabic', 'perego-site' ) }>
					<TextControl __nextHasNoMarginBottom label={ __( 'Arabic CTA label', 'perego-site' ) }
						value={ attributes.ctaAr }
						onChange={ ( ctaAr ) => setAttributes( { ctaAr } ) }
						placeholder={ SEED_CTA_AR } />
				</PanelSection>
				<PanelSection title={ __( 'CTA link', 'perego-site' ) }>
					<LinkPicker
						showLabel={ false }
						link={ linkFromAttributes( attributes ) }
						hrefText={ __( 'Button link', 'perego-site' ) }
						hrefHelp={ __( 'Leave empty for the contact page. A path such as /work is localized automatically.', 'perego-site' ) }
						onChange={ ( next ) => setAttributes( linkToAttributes( next ) ) } />
				</PanelSection>
			</InspectorControls>
			<div className="perego-hero-slider__preview" onClick={ ( event ) => {
				// Neutralize the preview's real links/dots so a click never navigates the editor away; the
				// in-canvas RichText title/text/CTA manage their own clicks, and the dots switch slides.
				const control = event.target.closest( 'a, button' );
				if ( control && ! control.isContentEditable ) {
					event.preventDefault();
				}
			} }>
				<HeroSkeleton slides={ displaySlides } cta={ attributes.ctaEn || SEED_CTA_EN }
					activeIndex={ active } activeTitle={ activeTitle } activeText={ activeText }
					ctaNode={ ctaNode } onSelectSlide={ setActiveIndex } />
			</div>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
