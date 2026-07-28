/**
 * Perego theme front-end entry (assets/src/js/main.js). `npm run scripts` builds it to
 * assets/js/main.js with a hashed assets/js/main.asset.php (cache-busting via the CoreX Script
 * helper). Styles compile separately from assets/src/scss/ (`npm run styles`). Keep this small
 * and dependency-free — progressive enhancement only.
 */
import { initCustomSelects } from './select';
import { initFormToasts } from './toast';

// Progressive-enhancement flag (handoff main.js:10): the reference stylesheet only hides
// `.reveal` elements under a `.js` root, so a no-JS visit renders everything visible.
document.documentElement.classList.add( 'js' );

/**
 * Scroll reveals — a verbatim port of the handoff's reveals IIFE (main.js:124-141): each `.reveal`
 * element fades/slides in once when it enters the viewport (CSS + reduced-motion guard live in
 * perego-reference.scss). Without IntersectionObserver everything shows immediately.
 */
function initReveals() {
	const els = document.querySelectorAll( '.reveal' );

	if ( ! els.length ) return;

	if ( ! ( 'IntersectionObserver' in window ) ) {
		els.forEach( ( el ) => el.classList.add( 'is-visible' ) );
		return;
	}

	const io = new IntersectionObserver(
		( entries ) => {
			entries.forEach( ( entry ) => {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'is-visible' );
					io.unobserve( entry.target );
				}
			} );
		},
		{ threshold: 0.12, rootMargin: '0px 0px -8% 0px' }
	);

	els.forEach( ( el ) => io.observe( el ) );
}

/**
 * Legal-page "On this page" TOC — scroll-spy. Highlights the TOC link for the section currently in
 * view (first by default). Sticky positioning is CSS; this only manages the `.is-active` class.
 * No-ops when there is no `.legal-toc` on the page.
 */
function initLegalToc() {
	const toc = document.querySelector( '.legal-toc' );

	if ( ! toc ) return;

	const links = Array.from( toc.querySelectorAll( 'a[href^="#"]' ) );
	const targets = links
		.map( ( link ) => ( {
			link,
			section: document.getElementById( decodeURIComponent( link.getAttribute( 'href' ).slice( 1 ) ) ),
		} ) )
		.filter( ( target ) => target.section );

	if ( ! targets.length ) return;

	const setActive = ( link ) => {
		links.forEach( ( other ) => other.classList.toggle( 'is-active', other === link ) );
	};
	setActive( targets[ 0 ].link );

	let raf;
	const update = () => {
		window.cancelAnimationFrame( raf );
		raf = window.requestAnimationFrame( () => {
			const line = window.scrollY + 140; // just past the sticky header
			let current = targets[ 0 ];
			targets.forEach( ( target ) => {
				if ( target.section.offsetTop <= line ) {
					current = target;
				}
			} );
			setActive( current.link );
		} );
	};
	update();
	window.addEventListener( 'scroll', update, { passive: true } );
}

/**
 * Background parallax — the photographic section images (`<img>` inside the `.__bg` wrappers, plus the
 * split-media photo) drift vertically against the scroll while their gradient scrim (`::after`) stays
 * put, so the imagery feels like it moves behind the page. Perego addition, not in the handoff.
 *
 * The image is scaled up a touch and only translated WITHIN that scale buffer, so the crop never
 * reveals an empty edge (no oversized markup or overflow gymnastics needed). Reuses the theme's own
 * idioms: `IntersectionObserver` (like initReveals) so only on-screen images are touched, and an
 * rAF-throttled passive scroll listener (like initLegalToc). Gated to desktop + motion-allowed:
 * disabled under `prefers-reduced-motion` (matching the reveal guard in perego-reference.scss) and
 * below the tablet breakpoint, where fixed/parallax backgrounds are janky and pointless. The hero
 * (`.hero__prism`) is intentionally excluded — it already runs its own `heroDrift` loop.
 */
function initParallax() {
	if ( ! ( 'IntersectionObserver' in window ) || ! ( 'requestAnimationFrame' in window ) ) return;

	const imgs = Array.from(
		document.querySelectorAll(
			'.home-about__bg img, .svc-hero__bg img, .contact-hero__bg img, .split-media > img:first-child'
		)
	);
	if ( ! imgs.length ) return;

	const SCALE = 1.28; // zoom that provides the translate buffer; ~14% of height is drift-able each way
	const reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	const wide = window.matchMedia( '(min-width: 900px)' );
	const enabled = () => wide.matches && ! reduce.matches;

	const items = imgs.map( ( img ) => ( {
		img,
		box: img.closest( '.home-about__bg, .svc-hero__bg, .contact-hero__bg, .split-media' ) || img.parentElement,
		visible: false,
	} ) );

	const io = new IntersectionObserver(
		( entries ) => {
			entries.forEach( ( entry ) => {
				const item = items.find( ( it ) => it.box === entry.target );
				if ( item ) item.visible = entry.isIntersecting;
			} );
		},
		{ rootMargin: '12% 0px 12% 0px' }
	);
	items.forEach( ( item ) => io.observe( item.box ) );

	const render = () => {
		const on = enabled();
		const vh = window.innerHeight;
		items.forEach( ( item ) => {
			if ( ! on ) {
				item.img.style.transform = '';
				return;
			}
			if ( ! item.visible ) return;
			const rect = item.box.getBoundingClientRect();
			// 0 when the section's centre sits at the viewport centre; ±1 as it exits either edge.
			const progress = Math.max( -1, Math.min( 1, ( rect.top + rect.height / 2 - vh / 2 ) / ( vh + rect.height ) * 2 ) );
			// Never translate past the scale buffer, so the cover crop always fills the frame.
			const drift = ( SCALE - 1 ) / 2 * rect.height * 0.85;
			item.img.style.transform = `translate3d(0, ${ ( progress * drift ).toFixed( 1 ) }px, 0) scale(${ SCALE })`;
		} );
	};

	let raf;
	const onScroll = () => {
		window.cancelAnimationFrame( raf );
		raf = window.requestAnimationFrame( render );
	};

	render();
	window.addEventListener( 'scroll', onScroll, { passive: true } );
	window.addEventListener( 'resize', onScroll, { passive: true } );
	reduce.addEventListener( 'change', render );
	wide.addEventListener( 'change', render );
}

/**
 * A toast when client-side validation stops a submission.
 *
 * The runtime emits `corex:form:error` when the *server* rejects, but its client-side branch
 * returns early without emitting anything. Since round 7 moved the visual channel from the status
 * line (now clipped, screen-reader only) to the toast, that left a failed submit with no banner at
 * all — only the inline field errors, which are below the fold on a phone.
 *
 * Capture phase plus a next-tick read makes this independent of listener order: whenever the
 * runtime's handler runs, it has already written its errors by the time this looks. A submission
 * that passes leaves no `aria-invalid`, and the server path emits the real event later, so neither
 * case double-toasts.
 */
function initClientValidationToasts() {
	document.addEventListener(
		'submit',
		( event ) => {
			const form = event.target;
			if ( ! form?.classList?.contains( 'corex-form' ) ) return;

			setTimeout( () => {
				if ( ! form.querySelector( '[aria-invalid="true"]' ) ) return;
				form.dispatchEvent( new CustomEvent( 'corex:form:error', { bubbles: true } ) );
			}, 0 );
		},
		true
	);
}

document.addEventListener( 'DOMContentLoaded', () => {
	initReveals();
	initLegalToc();
	initParallax();

	// CoreX's server-rendered <form class="corex-form"> has no novalidate attribute, so a
	// required/typed field fails the browser's own constraint validation before Corex.forms'
	// submit handler (window.Corex, spec 043) ever runs, showing the native "Please fill out
	// this field" bubble instead of the handoff's custom inline .corex-form__error UI. Setting
	// noValidate here — before any user interaction is possible — suppresses the native bubble
	// without touching the CoreX framework renderer or its validation/submission logic.
	document.querySelectorAll( '.corex-form' ).forEach( ( form ) => {
		form.noValidate = true;
	} );

	// Styled listbox over each native <select> — see select.js for why the native control stays.
	initCustomSelects();

	// Form outcomes as a toast. The framework's status line sits under the form, which on a phone
	// is off-screen after a long form — a successful send looked like nothing had happened.
	initFormToasts();
	initClientValidationToasts();
} );
