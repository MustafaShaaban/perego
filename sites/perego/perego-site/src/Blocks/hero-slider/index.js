/**
 * Perego hero-slider block — editor registration. Slide title/text (×3) and the CTA are
 * RichText-editable directly in the canvas (spec 020 round 4) — previously only a plain-text
 * wp-admin sidebar box (PostMetaBoxes, spec 012 T004). This block lives in the shared
 * `front-page.html` FSE template, so — matching `footer-careers`'/`clients-carousel`'s pattern for
 * the same problem — every string is edited as an En/Ar pair; `HeroContent::resolve()` picks the
 * current-locale variant (attribute → the old page-meta mechanism, kept as a fallback so already-
 * entered copy isn't lost → the handoff seed). A slide switcher shows one slide's fields at a time so
 * three slides × two languages stays manageable. Server-rendered (save returns null); the front-end
 * rotation/dot logic is unchanged, driven by view.js.
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, RichText } from '@wordpress/block-editor';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import { RepeaterControls } from '../../Editor/RepeaterControls';
import metadata from './block.json';
import './style.scss';

const SEED = {
	1: {
		titleEn: 'What We Believe', textEn: 'Not every artist holds a brush — what truly matters are the ideas you bring to life.',
		titleAr: 'بماذا نؤمن', textAr: 'ليس كل فنان يمسك فرشاة؛ فالفرشاة مجرد أداة، وما يهم حقًا هو الأفكار التي تمنحها الحياة.',
	},
	2: {
		titleEn: 'Ideas, In Motion', textEn: 'From the first spark of a concept to the final frame, we shape stories that move people.',
		titleAr: 'أفكار تتحرك', textAr: 'من أول شرارة للفكرة حتى اللقطة الأخيرة، نصنع قصصًا تُحرّك المشاعر.',
	},
	3: {
		titleEn: 'Built To Be Seen', textEn: 'We turn brands into experiences worth watching.',
		titleAr: 'صُنع ليُرى', textAr: 'نحوّل العلامات التجارية إلى تجارب تستحق المشاهدة.',
	},
};
const SEED_CTA = { en: 'Say Hello!', ar: 'قل مرحبًا!' };
const MAX_SLIDES = 6;

const initialSlides = ( attributes ) => [ 1, 2, 3 ].map( ( number ) => ( {
	titleEn: attributes[ `slide${ number }TitleEn` ] || SEED[ number ].titleEn,
	textEn: attributes[ `slide${ number }TextEn` ] || SEED[ number ].textEn,
	titleAr: attributes[ `slide${ number }TitleAr` ] || SEED[ number ].titleAr,
	textAr: attributes[ `slide${ number }TextAr` ] || SEED[ number ].textAr,
} ) );

function SlideFields( { slideNumber, attributes, setAttributes } ) {
	const seed = SEED[ slideNumber ];
	const titleEnKey = `slide${ slideNumber }TitleEn`;
	const textEnKey = `slide${ slideNumber }TextEn`;
	const titleArKey = `slide${ slideNumber }TitleAr`;
	const textArKey = `slide${ slideNumber }TextAr`;

	return (
		<>
			<fieldset className="perego-hero-slider__lang" dir="ltr">
				<legend>{ __( 'English', 'perego-site' ) }</legend>
				<RichText tagName="h3" className="hero__title"
					value={ attributes[ titleEnKey ] || seed.titleEn }
					onChange={ ( value ) => setAttributes( { [ titleEnKey ]: value } ) }
					placeholder={ seed.titleEn } />
				<RichText tagName="p" className="hero__text"
					value={ attributes[ textEnKey ] || seed.textEn }
					onChange={ ( value ) => setAttributes( { [ textEnKey ]: value } ) }
					placeholder={ seed.textEn } />
			</fieldset>
			<fieldset className="perego-hero-slider__lang" dir="rtl">
				<legend>{ __( 'Arabic', 'perego-site' ) }</legend>
				<RichText tagName="h3" className="hero__title"
					value={ attributes[ titleArKey ] || seed.titleAr }
					onChange={ ( value ) => setAttributes( { [ titleArKey ]: value } ) }
					placeholder={ seed.titleAr } />
				<RichText tagName="p" className="hero__text"
					value={ attributes[ textArKey ] || seed.textAr }
					onChange={ ( value ) => setAttributes( { [ textArKey ]: value } ) }
					placeholder={ seed.textAr } />
			</fieldset>
		</>
	);
}

function ComposedSlideFields( { slide, onChange } ) {
	return <>
		<fieldset className="perego-hero-slider__lang" dir="ltr"><legend>{ __( 'English', 'perego-site' ) }</legend>
			<RichText tagName="h3" className="hero__title" value={ slide.titleEn } onChange={ ( titleEn ) => onChange( { ...slide, titleEn } ) } placeholder={ __( 'Headline', 'perego-site' ) } />
			<RichText tagName="p" className="hero__text" value={ slide.textEn } onChange={ ( textEn ) => onChange( { ...slide, textEn } ) } placeholder={ __( 'Supporting text', 'perego-site' ) } />
		</fieldset>
		<fieldset className="perego-hero-slider__lang" dir="rtl"><legend>{ __( 'Arabic', 'perego-site' ) }</legend>
			<RichText tagName="h3" className="hero__title" value={ slide.titleAr } onChange={ ( titleAr ) => onChange( { ...slide, titleAr } ) } placeholder={ __( 'عنوان', 'perego-site' ) } />
			<RichText tagName="p" className="hero__text" value={ slide.textAr } onChange={ ( textAr ) => onChange( { ...slide, textAr } ) } placeholder={ __( 'نص داعم', 'perego-site' ) } />
		</fieldset>
	</>;
}

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'perego-hero-slider__editor' } );
	const [ activeSlide, setActiveSlide ] = useState( 0 );
	const slides = attributes.slides?.length ? attributes.slides : null;
	const updateSlides = ( slides ) => setAttributes( { slides } );
	const selectSlide = ( index ) => setActiveSlide( index );

	return (
		<div { ...blockProps }>
			<div className="perego-hero-slider__slide-switcher" role="tablist" aria-label={ __( 'Hero slides', 'perego-site' ) }>
				{ ( slides || [ 1, 2, 3 ] ).map( ( _, index ) => (
					<Button key={ index } variant={ activeSlide === index ? 'primary' : 'secondary' }
						aria-selected={ activeSlide === index } role="tab"
						onClick={ () => selectSlide( index ) }>
						{ /* translators: %d: slide number */ __( 'Slide', 'perego-site' ) } { index + 1 }
					</Button>
				) ) }
			</div>
			{ slides ? <ComposedSlideFields slide={ slides[ activeSlide ] } onChange={ ( slide ) => updateSlides( slides.map( ( current, index ) => index === activeSlide ? slide : current ) ) } /> : <SlideFields slideNumber={ activeSlide + 1 } attributes={ attributes } setAttributes={ setAttributes } /> }
			{ slides ? <RepeaterControls items={ slides } index={ activeSlide } itemLabel={ __( 'slide', 'perego-site' ) } createCopy={ ( slide ) => ( { ...slide } ) } canDuplicate={ slides.length < MAX_SLIDES } canRemove={ slides.length > 1 } onChange={ ( next ) => { updateSlides( next.slice( 0, MAX_SLIDES ) ); setActiveSlide( Math.min( activeSlide, next.length - 1 ) ); } } /> : null }
			<Button variant="secondary" disabled={ ( slides || initialSlides( attributes ) ).length >= MAX_SLIDES } onClick={ () => { const next = [ ...( slides || initialSlides( attributes ) ), { titleEn: '', textEn: '', titleAr: '', textAr: '' } ]; updateSlides( next ); setActiveSlide( next.length - 1 ); } }>{ __( 'Add slide', 'perego-site' ) }</Button>
			<fieldset className="perego-hero-slider__cta-group">
				<legend>{ __( 'CTA button', 'perego-site' ) }</legend>
				<RichText tagName="span" className="btn btn--accent" dir="ltr"
					value={ attributes.ctaEn || SEED_CTA.en }
					onChange={ ( ctaEn ) => setAttributes( { ctaEn } ) }
					placeholder={ SEED_CTA.en } />
				<RichText tagName="span" className="btn btn--accent" dir="rtl"
					value={ attributes.ctaAr || SEED_CTA.ar }
					onChange={ ( ctaAr ) => setAttributes( { ctaAr } ) }
					placeholder={ SEED_CTA.ar } />
			</fieldset>
			<div className="perego-hero-slider__preview" onClick={ ( event ) => {
				if ( event.target.closest( 'a, button' ) ) {
					event.preventDefault();
				}
			} }>
				<ServerSideRender block={ metadata.name } attributes={ attributes } />
			</div>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
