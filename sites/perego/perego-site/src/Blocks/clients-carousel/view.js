/**
 * Enhances the handoff's native scroll-snap client tracks without altering their DOM: arrow paging,
 * the prototype's edge states (`.at-start`/`.at-end` drive the reference edge-fades; arrows disable
 * at their extreme), and the corporate track's drag-to-scroll with click suppression (a drag must
 * never open a card's lightbox) — ported from the handoff main.js corporateSlider()/indivSlider().
 */
function bindTrack( slider ) {
	const track = slider.querySelector( '.corp-track, .indiv-track' );
	const previous = slider.querySelector( '.corp-arrow--prev' );
	const next = slider.querySelector( '.corp-arrow--next' );

	if ( ! track || ! previous || ! next ) {
		return;
	}

	const updateEdges = () => {
		const max = track.scrollWidth - track.clientWidth - 1;
		// RTL-safe: browsers report negative scrollLeft in RTL; distance from the start is |x|.
		const position = Math.abs( track.scrollLeft );
		const atStart = position <= 1;
		const atEnd = position >= max || max <= 0;

		slider.classList.toggle( 'at-start', atStart );
		slider.classList.toggle( 'at-end', atEnd );
		previous.disabled = atStart;
		next.disabled = atEnd;
	};

	const scroll = ( direction ) => {
		const distance = Math.max( track.clientWidth * 0.85, 1 );
		track.scrollBy( { left: distance * direction, behavior: 'smooth' } );
	};

	previous.addEventListener( 'click', () => scroll( -1 ) );
	next.addEventListener( 'click', () => scroll( 1 ) );
	track.addEventListener( 'scroll', updateEdges, { passive: true } );
	window.addEventListener( 'resize', updateEdges );
	updateEdges();

	bindDragScroll( track );
}

/**
 * Prototype drag-to-scroll (handoff main.js:223-236): pointer-drag pans the track; a capture-phase
 * click handler swallows the click when the pointer moved more than 4px, so ending a drag on a
 * card never triggers its lightbox.
 */
function bindDragScroll( track ) {
	let dragging = false;
	let startX = 0;
	let startScroll = 0;
	let moved = 0;

	track.addEventListener( 'pointerdown', ( event ) => {
		dragging = true;
		moved = 0;
		startX = event.clientX;
		startScroll = track.scrollLeft;
	} );

	track.addEventListener( 'pointermove', ( event ) => {
		if ( ! dragging ) return;
		const delta = event.clientX - startX;
		moved = Math.max( moved, Math.abs( delta ) );
		if ( moved > 4 ) track.scrollLeft = startScroll - delta;
	} );

	const stop = () => {
		dragging = false;
	};
	track.addEventListener( 'pointerup', stop );
	track.addEventListener( 'pointerleave', stop );

	track.addEventListener(
		'click',
		( event ) => {
			if ( moved > 4 ) {
				event.preventDefault();
				event.stopPropagation();
				moved = 0;
			}
		},
		true
	);
}

function init() {
	document.querySelectorAll( '.corp-slider, .indiv-slider' ).forEach( bindTrack );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
