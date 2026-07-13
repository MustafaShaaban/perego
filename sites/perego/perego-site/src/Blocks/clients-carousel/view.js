/**
 * Enhances the handoff's native scroll-snap client tracks without altering their DOM.
 */
function bindTrack( slider ) {
	const track = slider.querySelector( '.corp-track, .indiv-track' );
	const previous = slider.querySelector( '.corp-arrow--prev' );
	const next = slider.querySelector( '.corp-arrow--next' );

	if ( ! track || ! previous || ! next ) {
		return;
	}

	const scroll = ( direction ) => {
		const distance = Math.max( track.clientWidth * 0.85, 1 );
		track.scrollBy( { left: distance * direction, behavior: 'smooth' } );
	};

	previous.addEventListener( 'click', () => scroll( -1 ) );
	next.addEventListener( 'click', () => scroll( 1 ) );
}

function init() {
	document.querySelectorAll( '.corp-slider, .indiv-slider' ).forEach( bindTrack );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
