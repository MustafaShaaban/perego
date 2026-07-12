/**
 * Perego clients carousels — progressive Swiper enhancement (modular imports only). The cards are
 * already server-rendered and reachable without JS (CSS scroll fallback); this upgrades each
 * [data-clients-swiper] to a Swiper with Navigation, Pagination, Keyboard, and A11y. Autoplay is not
 * used, so reduced-motion needs no special-casing here.
 */
import Swiper from 'swiper';
import { Navigation, Pagination, Keyboard, A11y } from 'swiper/modules';

const init = () => {
	const els = document.querySelectorAll( '[data-clients-swiper]' );
	els.forEach( ( el ) => {
		// eslint-disable-next-line no-new
		new Swiper( el, {
			modules: [ Navigation, Pagination, Keyboard, A11y ],
			slidesPerView: 1.2,
			spaceBetween: 16,
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
			breakpoints: {
				640: { slidesPerView: 2.2 },
				1024: { slidesPerView: 4, spaceBetween: 24 },
			},
		} );
	} );
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
