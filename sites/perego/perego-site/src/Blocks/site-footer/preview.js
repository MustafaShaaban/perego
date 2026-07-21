/**
 * Live-canvas markup for perego-theme/site-footer (spec 021 C2 / T009; DECISIONS 2026-07-21).
 *
 * The footer is a HYBRID block: its contact column and bottom bar are static markup, but the quick-
 * message and careers columns embed live form blocks (`corex/form`, `footer-careers`, `join-form`) via
 * `do_blocks()` on the front end. Per the static-vs-dynamic rule, the canvas renders the REAL markup for
 * the static surfaces (the parts an editor composes) and a labelled locked placeholder for each dynamic
 * form column — a "visual locked preview". `FooterSkeleton` is pure so `parity.test.js` can render it and
 * assert the static subtrees (`.footer-contact`, `.site-footer__bottom`) equal a fixture of the PHP output.
 *
 * The seed contact channels / social links / blurbs and the social-icon glyphs mirror
 * `SiteFooterRenderer` (its PHP consts are the source of truth); this file carries the editor copies the
 * same way the block already mirrored the seeds. Parity compares only tags/classes/nesting, so the glyph
 * `d` values are visual-only and can never break the test.
 */
import { __, sprintf } from '@wordpress/i18n';

export const SEED_CONTACT_CHANNELS = [
	{ label: 'mostafa.emam3313@gmail.com', href: 'mailto:mostafa.emam3313@gmail.com' },
	{ label: 'yehemam2@gmail.com', href: 'mailto:yehemam2@gmail.com' },
	{ label: '+996 56 293 2759', href: 'tel:+996562932759' },
	{ label: '+20 111 54 855 72', href: 'tel:+201115485572' },
];

export const SOCIAL_NETWORKS = [ 'Instagram', 'Facebook', 'LinkedIn', 'YouTube', 'WhatsApp' ];

export const SEED_SOCIAL_LINKS = [
	{ network: 'Instagram', href: 'https://www.instagram.com/' },
	{ network: 'Facebook', href: 'https://www.facebook.com/' },
	{ network: 'LinkedIn', href: 'https://www.linkedin.com/' },
	{ network: 'YouTube', href: 'https://www.youtube.com/' },
	{ network: 'WhatsApp', href: 'https://wa.me/' },
];

export const SEED_BLURB_EN = 'We would be delighted to hear from you to provide creative technical solutions, assistance, and tailored recommendations that best suit your needs.';
export const SEED_BLURB_AR = 'يسعدنا التواصل معك لتقديم حلول تقنية إبداعية والمساعدة والتوصيات المخصصة التي تناسب احتياجاتك على أفضل وجه.';

/** Mirrors `SiteFooterRenderer::SOCIAL_ICON_PATHS` (source of truth); visual-only for the editor canvas. */
const SOCIAL_ICON_PATHS = {
	Instagram: 'M12 2.163c3.204 0 3.584.012 4.85.07 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227a3.8 3.8 0 0 1-.899 1.382 3.7 3.7 0 0 1-1.38.896c-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07s-3.585-.015-4.859-.074c-1.17-.061-1.815-.256-2.236-.421a3.7 3.7 0 0 1-1.379-.899 3.6 3.6 0 0 1-.9-1.38c-.163-.42-.359-1.065-.42-2.235-.045-1.26-.06-1.649-.06-4.844s.015-3.585.06-4.859c.061-1.17.257-1.814.42-2.236.21-.562.479-.96.9-1.381.419-.419.817-.679 1.379-.896.422-.164 1.057-.36 2.227-.421 1.266-.045 1.646-.06 4.859-.06M12 0C8.741 0 8.332.014 7.052.072 5.775.132 4.904.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.904.131 5.775.072 7.052.014 8.332 0 8.741 0 12s.014 3.668.072 4.948c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.986 8.741 24 12 24s3.668-.014 4.948-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.058-1.28.072-1.689.072-4.948s-.014-3.667-.072-4.947c-.06-1.277-.262-2.913-.558-2.913a5.9 5.9 0 0 0-1.384-2.126A5.9 5.9 0 0 0 19.861.63c-.765-.297-1.636-.499-2.913-.558C15.668.014 15.259 0 12 0m0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324M12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8m6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 1 0 0-2.881',
	Facebook: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073',
	LinkedIn: 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 0-2.063-2.065 2.064 2.064 0 1 0 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0z',
	YouTube: 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93.502-5.814zM9.545 15.568V8.432L15.818 12z',
	WhatsApp: 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413',
};

/** Decode a JSON-array attribute, falling back to `seed` when empty or invalid. */
export function parseList( raw, seed ) {
	if ( ! raw ) {
		return seed;
	}
	try {
		const parsed = JSON.parse( raw );
		return Array.isArray( parsed ) && parsed.length ? parsed : seed;
	} catch ( e ) {
		return seed;
	}
}

/** The theme's shipped logo. `src` is visual-only (parity ignores it). */
function logoUrl() {
	const origin = typeof window !== 'undefined' && window.location ? window.location.origin : '';
	return `${ origin }/wp-content/themes/perego-theme/assets/images/logo-full.png`;
}

const ContactColumn = ( { channels, socialLinks, blurb } ) => (
	<div className="footer-col footer-contact">
		<a className="logo" href="#" aria-label={ __( 'Perego — home', 'perego-site' ) }>
			<img src={ logoUrl() } alt="" className="logo__img" />
		</a>
		<h2 className="footer-heading">{ __( 'Contact us', 'perego-site' ) }</h2>
		<ul className="footer-contacts">
			{ channels.map( ( channel, index ) => (
				<li key={ index }><a href={ channel.href || '#' }><bdi>{ channel.label }</bdi></a></li>
			) ) }
		</ul>
		{ blurb || <p className="footer-blurb">{ SEED_BLURB_EN }</p> }
		<ul className="footer-social" aria-label={ __( 'Social media', 'perego-site' ) }>
			{ socialLinks.filter( ( social ) => SOCIAL_ICON_PATHS[ social.network ] ).map( ( social, index ) => (
				<li key={ index }>
					<a href={ social.href || '#' } target="_blank" rel="noopener"
						aria-label={ sprintf( /* translators: %s: social network name */ __( 'Perego on %s', 'perego-site' ), social.network ) }>
						<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d={ SOCIAL_ICON_PATHS[ social.network ] } /></svg>
					</a>
				</li>
			) ) }
		</ul>
	</div>
);

/** A dynamic form column the front end renders via `do_blocks()`; shown as a labelled locked placeholder. */
const FormColumnPlaceholder = ( { className, label } ) => (
	<div className={ `footer-col ${ className }` }>
		<p className="perego-site-footer__ssr-note">{ label }</p>
	</div>
);

const BottomBar = () => (
	<div className="container site-footer__bottom">
		<p>{ sprintf(
			/* translators: %s: current year. */
			__( '© %s Perego Creative Studio — بيريجو. All rights reserved.', 'perego-site' ),
			new Date().getFullYear()
		) }</p>
		<nav className="footer-legal" aria-label={ __( 'Legal', 'perego-site' ) }>
			<a href="#">{ __( 'Journal', 'perego-site' ) }</a>
			<a href="#">{ __( 'Terms & Conditions', 'perego-site' ) }</a>
			<a href="#">{ __( 'Privacy Policy', 'perego-site' ) }</a>
		</nav>
	</div>
);

/**
 * The real footer markup for the editor canvas. `channels`/`socialLinks` are the shown lists; `blurb`
 * optionally overrides the blurb paragraph with an in-canvas `RichText` from `edit()` (it must keep the
 * `p.footer-blurb` skeleton). `flat` drops the quick-message column, matching the contact-page variant.
 */
export function FooterSkeleton( { channels, socialLinks, blurb, flat } ) {
	return (
		<footer className={ `site-footer${ flat ? ' site-footer--flat' : '' }` } id="contact">
			<div className="container site-footer__grid">
				<ContactColumn channels={ channels } socialLinks={ socialLinks } blurb={ blurb } />
				{ ! flat && (
					<FormColumnPlaceholder className="footer-quick-message"
						label={ __( 'Quick message form — shown on the live site.', 'perego-site' ) } />
				) }
				<FormColumnPlaceholder className="footer-careers"
					label={ __( 'Join us / careers — shown on the live site.', 'perego-site' ) } />
			</div>
			<BottomBar />
		</footer>
	);
}
