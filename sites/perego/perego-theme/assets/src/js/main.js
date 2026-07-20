/**
 * Perego theme front-end entry (assets/src/js/main.js). `npm run scripts` builds it to
 * assets/js/main.js with a hashed assets/js/main.asset.php (cache-busting via the CoreX Script
 * helper). Styles compile separately from assets/src/scss/ (`npm run styles`). Keep this small
 * and dependency-free — progressive enhancement only.
 */
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

document.addEventListener( 'DOMContentLoaded', () => {
	initReveals();
	initLegalToc();

	// CoreX's server-rendered <form class="corex-form"> has no novalidate attribute, so a
	// required/typed field fails the browser's own constraint validation before Corex.forms'
	// submit handler (window.Corex, spec 043) ever runs, showing the native "Please fill out
	// this field" bubble instead of the handoff's custom inline .corex-form__error UI. Setting
	// noValidate here — before any user interaction is possible — suppresses the native bubble
	// without touching the CoreX framework renderer or its validation/submission logic.
	document.querySelectorAll( '.corex-form' ).forEach( ( form ) => {
		form.noValidate = true;
	} );
} );
