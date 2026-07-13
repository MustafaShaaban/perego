/**
 * Perego clients carousels — progressive Swiper enhancement (modular imports only). The cards are
 * already server-rendered and reachable without JS (CSS scroll fallback); this upgrades each
 * [data-clients-swiper] to a Swiper with Navigation, Pagination, Keyboard, and A11y. Autoplay is not
 * used, so reduced-motion needs no special-casing here.
 */
import Swiper from 'swiper';
import { Navigation, Pagination, Keyboard, A11y } from 'swiper/modules';

// Corporate cards are small icon tiles (handoff: ~10 per row on desktop); Individual cards are wide
// info+thumbnail cards (handoff: a few per view). Same Swiper wiring, different density per carousel.
const CORPORATE_SIZING = {
	slidesPerView: 3,
	spaceBetween: 12,
	breakpoints: {
		640: { slidesPerView: 5 },
		1024: { slidesPerView: 8, spaceBetween: 16 },
	},
};

const INDIVIDUAL_SIZING = {
	slidesPerView: 1.2,
	spaceBetween: 16,
	breakpoints: {
		640: { slidesPerView: 2.2 },
		1024: { slidesPerView: 4, spaceBetween: 24 },
	},
};

const init = () => {
	const els = document.querySelectorAll( '[data-clients-swiper]' );
	els.forEach( ( el ) => {
		const sizing = el.classList.contains( 'clients-swiper--corporate' )
			? CORPORATE_SIZING
			: INDIVIDUAL_SIZING;

		// eslint-disable-next-line no-new
		new Swiper( el, {
			modules: [ Navigation, Pagination, Keyboard, A11y ],
			...sizing,
			keyboard: { enabled: true },
			a11y: { enabled: true },
			watchOverflow: true,
			navigation: {
				prevEl: el.querySelector( '.swiper-button-prev' ),
				nextEl: el.querySelector( '.swiper-button-next' ),
			},
			pagination: {
				el: el.querySelector( '.swiper-pagination' ),
				clickable: true,
			},
		} );
	} );
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
