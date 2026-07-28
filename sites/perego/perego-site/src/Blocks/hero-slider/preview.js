/**
 * Live-canvas markup for perego-theme/hero-slider (spec 021 C3 / T011–T012; DECISIONS 2026-07-21).
 *
 * This renders the REAL front-end hero structure — the same tag + class skeleton that
 * `HeroSliderRenderer::render()` emits — so the block editor canvas shows the actual Perego hero
 * (styled by the theme's `main.css`, loaded into the canvas via `add_editor_style`) instead of a
 * `ServerSideRender` iframe. The behavioural attributes present on the front end (`data-wp-*`, the
 * Interactivity context, aria/hidden state) are intentionally omitted or static: the editor preview is
 * a single, currently-selected slide, and the markup-parity test compares only tags, class hooks, and
 * nesting (see `../../Editor/parity.js`).
 *
 * `HeroSkeleton` is deliberately pure (no editor-store components) so `parity.test.js` can render it to
 * a string and assert it against a fixture of the PHP output. `edit()` (index.js) renders the SAME
 * component, passing `activeTitle`/`activeText`/`ctaNode` that add in-canvas `RichText` editing for the
 * selected slide + CTA without changing the skeleton — so what ships is what the test verified.
 *
 * The seed slides + CTA mirror `PeregoSite\Content\HomeContent::COPY` (its PHP array is the source of
 * truth), so the default editor canvas matches the default front-end hero. Parity compares only
 * tags/classes/nesting, so the seed text can never break the test.
 */
import { __, sprintf } from '@wordpress/i18n';

/** English seed slides — mirror of `HomeContent::COPY['en']['hero']['slides']`. */
export const SEED_SLIDES_EN = [
	{
		title: 'What We Believe',
		text: 'Not every artist holds a brush. A brush is only a tool; what truly matters are the ideas you bring to life. You can be an artist through your mindset, your dedication, your passion, and the way you shape your priorities. Art lives in what you have mastered or in what you have yet to discover, but it is already within you.',
	},
	{
		title: 'Ideas, In Motion',
		text: 'From the first spark of a concept to the final frame, we shape stories that move people. Video editing, motion graphics, design and web — one studio, one obsessive standard for craft, delivered on time across the Arab region.',
	},
	{
		title: 'Built To Be Seen',
		text: 'We turn brands into experiences worth watching. Bold visuals, clear messages, and creative technical solutions tailored to exactly what your audience needs — crafted to elevate your business and your digital presence.',
	},
];

/** Arabic seed slides — mirror of `HomeContent::COPY['ar']['hero']['slides']`. */
export const SEED_SLIDES_AR = [
	{
		title: 'بماذا نؤمن',
		text: 'ليس كل فنان يمسك فرشاة؛ فالفرشاة مجرد أداة، وما يهم حقًا هو الأفكار التي تمنحها الحياة. يمكنك أن تكون فنانًا بعقليتك وتفانيك وشغفك وطريقتك في ترتيب أولوياتك. الفن يكمن فيما أتقنته أو فيما لم تكتشفه بعد، لكنه موجود بداخلك بالفعل.',
	},
	{
		title: 'أفكار تتحرك',
		text: 'من أول شرارة للفكرة حتى اللقطة الأخيرة، نصنع قصصًا تُحرّك المشاعر. مونتاج فيديو، وموشن جرافيك، وتصميم، ومواقع — استوديو واحد بمعيار واحد لا يهادن في جودة الصنعة، ويُسلَّم في موعده في جميع أنحاء المنطقة العربية.',
	},
	{
		title: 'صُنع ليُرى',
		text: 'نحوّل العلامات التجارية إلى تجارب تستحق المشاهدة. عناصر بصرية جريئة، ورسائل واضحة، وحلول تقنية إبداعية مصممة تمامًا وفق ما يحتاجه جمهورك — لنرتقي بأعمالك وحضورك الرقمي.',
	},
];

export const SEED_CTA_EN = 'Say Hello!';
export const SEED_CTA_AR = 'قل مرحبًا!';

/** The hero holds at most this many slides (matches the block's `Add slide` guard). */
export const MAX_SLIDES = 6;

/**
 * Resolve the stored slides to a structured `{ titleEn, textEn, titleAr, textAr }` list. Prefers the
 * `slides` array attribute (spec 021 structured repeater); otherwise upgrades the legacy per-slide
 * attributes (`slide{n}Title{En,Ar}` / `slide{n}Text{En,Ar}`, spec 020 round 4) so hero copy an editor
 * already entered keeps rendering. Empty fields fall back to the seed, per locale.
 *
 * @param {Object} attributes Block attributes.
 * @return {Array<{titleEn:string,textEn:string,titleAr:string,textAr:string}>} Structured slides.
 */
export function normalizeSlides( attributes ) {
	const stored = attributes.slides;
	if ( Array.isArray( stored ) && stored.length ) {
		return stored.map( ( slide, index ) => ( {
			titleEn: slide.titleEn || SEED_SLIDES_EN[ index ]?.title || '',
			textEn: slide.textEn || SEED_SLIDES_EN[ index ]?.text || '',
			titleAr: slide.titleAr || SEED_SLIDES_AR[ index ]?.title || '',
			textAr: slide.textAr || SEED_SLIDES_AR[ index ]?.text || '',
		} ) );
	}

	return SEED_SLIDES_EN.map( ( seed, index ) => ( {
		titleEn: attributes[ `slide${ index + 1 }TitleEn` ] || seed.title,
		textEn: attributes[ `slide${ index + 1 }TextEn` ] || seed.text,
		titleAr: attributes[ `slide${ index + 1 }TitleAr` ] || SEED_SLIDES_AR[ index ].title,
		textAr: attributes[ `slide${ index + 1 }TextAr` ] || SEED_SLIDES_AR[ index ].text,
	} ) );
}

/** The theme's shipped hero background. `src` is visual-only (parity ignores it). */
export function prismUrl() {
	const origin = typeof window !== 'undefined' && window.location ? window.location.origin : '';
	return `${ origin }/wp-content/themes/perego-theme/assets/images/hero-bg.png`;
}

/** The tag the front end uses for a slide title: `h1` for the first slide, `p` for the rest. */
export function slideTitleTag( index ) {
	return index === 0 ? 'h1' : 'p';
}

/**
 * The real hero markup for the editor canvas. `slides` are the shown-locale `{ title, text }` entries and
 * `cta` the shown-locale CTA label; `activeIndex` is the currently-selected (visible) slide. `activeTitle`,
 * `activeText`, and `ctaNode` optionally replace the selected slide's title/text and the CTA anchor with
 * in-canvas `RichText` widgets from `edit()` — they must keep the `.hero__title` / `.hero__text` /
 * `a.btn.btn--accent` skeletons, which the parity test pins by rendering this component with plain defaults.
 * `onSelectSlide`, when provided, makes the dots switch the active slide.
 */
export function HeroSkeleton( {
	slides,
	cta,
	activeIndex = 0,
	activeTitle,
	activeText,
	ctaNode,
	onSelectSlide,
} ) {
	return (
		<section className="hero" id="hero" aria-label={ __( 'Introduction', 'perego-site' ) }>
			<div className="hero__prism" aria-hidden="true">
				<img src={ prismUrl() } alt="" />
			</div>
			<div className="container hero__inner">
				<div className="hero__content hero-enter">
					{ slides.map( ( slide, index ) => {
						const TitleTag = slideTitleTag( index );
						const isActive = index === activeIndex;
						return (
							<div key={ index } className="hero__slide" data-slide={ index } hidden={ ! isActive }>
								{ isActive && activeTitle ? activeTitle : (
									<TitleTag className="hero__title">{ slide.title }</TitleTag>
								) }
								{ isActive && activeText ? activeText : (
									<p className="hero__text">{ slide.text }</p>
								) }
							</div>
						);
					} ) }
					<div className="hero__cta">
						{ ctaNode || <a className="btn btn--accent" href="#">{ cta }</a> }
					</div>
				</div>
			</div>
			<div className="hero__dots" role="tablist" aria-label={ __( 'Hero slides', 'perego-site' ) }>
				{ slides.map( ( slide, index ) => {
					const isActive = index === activeIndex;
					return (
						<button key={ index } type="button"
							className={ isActive ? 'hero__dot is-active' : 'hero__dot' }
							role="tab" aria-selected={ isActive ? 'true' : 'false' }
							aria-label={ sprintf( /* translators: %d: slide number */ __( 'Slide %d', 'perego-site' ), index + 1 ) }
							onClick={ onSelectSlide ? () => onSelectSlide( index ) : undefined } />
					);
				} ) }
			</div>
			<p className="hero__status screen-reader-text" aria-live="polite" />
		</section>
	);
}
