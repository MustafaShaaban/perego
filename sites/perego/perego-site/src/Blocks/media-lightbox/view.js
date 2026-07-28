/**
 * Site-wide accessible media lightbox. Delegates a click listener to every
 * [data-image]/[data-video]/[data-gallery] trigger on the page (any block can use it without
 * registering its own dialog) and opens the single dialog rendered by MediaLightboxRenderer.
 * Ported from the handoff's main.js lightbox IIFE (media-type detection, prev/next, dots), with the
 * accessible dialog contract already proven by project-gallery-lightbox: role="dialog", focus trap,
 * Escape/backdrop close, focus restoration, scroll lock — plus an inert background while open.
 */

function mediaType( src ) {
	if ( /youtube\.com|youtu\.be|vimeo\.com|\/embed\//i.test( src ) ) return 'embed';
	if ( /\.(mp4|webm|ogv|mov)(\?|#|$)/i.test( src ) ) return 'video';
	// Tested before the image fallthrough, which is the catch-all. Documents are self-hosted
	// attachments, so the browser's own viewer can frame them same-origin with no library.
	if ( /\.pdf(\?|#|$)/i.test( src ) ) return 'pdf';
	return 'image';
}

/** How far a single zoom step moves, and the range it stays inside. */
const ZOOM_STEP = 0.5;
const ZOOM_MIN = 1;
const ZOOM_MAX = 4;

const clamp = ( value, min, max ) => Math.min( max, Math.max( min, value ) );

/**
 * A pasted YouTube/Vimeo URL is almost always the normal watch/page URL (`youtube.com/watch?v=ID`,
 * `youtu.be/ID`, `youtube.com/shorts/ID`, `vimeo.com/ID`), which is not embeddable — loading it in an
 * iframe returns YouTube's own "Error 153 / Video player configuration error" page instead of the
 * video. Only `/embed/ID` (YouTube) and `player.vimeo.com/video/ID` (Vimeo) URLs actually embed.
 * Converts the common pasted forms to their embeddable equivalent; anything already embeddable, or
 * not recognized, passes through unchanged.
 */
function toEmbedUrl( src ) {
	const youtubeWatch = src.match( /youtube\.com\/watch\?(?:.*&)?v=([\w-]+)/i );
	if ( youtubeWatch ) return 'https://www.youtube.com/embed/' + youtubeWatch[ 1 ];

	const youtubeShort = src.match( /youtu\.be\/([\w-]+)/i );
	if ( youtubeShort ) return 'https://www.youtube.com/embed/' + youtubeShort[ 1 ];

	const youtubeShorts = src.match( /youtube\.com\/shorts\/([\w-]+)/i );
	if ( youtubeShorts ) return 'https://www.youtube.com/embed/' + youtubeShorts[ 1 ];

	const vimeoPage = src.match( /vimeo\.com\/(\d+)/i );
	if ( vimeoPage && ! /player\.vimeo\.com/i.test( src ) ) return 'https://player.vimeo.com/video/' + vimeoPage[ 1 ];

	return src;
}

function focusableIn( container ) {
	return Array.from(
		container.querySelectorAll(
			'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'
		)
	).filter( ( el ) => el.offsetParent !== null );
}

function init() {
	const dialog = document.getElementById( 'perego-media-lightbox' );
	const triggers = document.querySelectorAll( '[data-image], [data-video], [data-gallery]' );
	if ( ! dialog || ! triggers.length ) return;

	// Move the dialog to be a direct child of <body> so it can sit above everything regardless of
	// where it was server-rendered, and so `inert` on its siblings never inerts the dialog itself.
	document.body.appendChild( dialog );

	const frame = dialog.querySelector( '.lightbox__frame' );
	const dots = dialog.querySelector( '[data-lightbox-dots]' );
	const counter = dialog.querySelector( '[data-lightbox-counter]' );
	const prevBtn = dialog.querySelector( '[data-lightbox-prev]' );
	const nextBtn = dialog.querySelector( '[data-lightbox-next]' );
	const zoomBtns = Array.from( dialog.querySelectorAll( '[data-lightbox-zoom]' ) );
	const fullscreenBtn = dialog.querySelector( '[data-lightbox-fullscreen]' );
	const documentLabel = dialog.getAttribute( 'data-lightbox-document-label' ) || 'Open the document';

	let gallery = [];
	let index = 0;
	let lastFocused = null;
	let zoom = 1;
	let panX = 0;
	let panY = 0;

	/** The zoomable element, or null when the slide is not an image. */
	const zoomTarget = () => frame.querySelector( '.lightbox__img' );

	function applyZoom() {
		const img = zoomTarget();
		if ( ! img ) return;

		// At 1× the pan is meaningless and a stray offset would leave the image off-centre, so it is
		// dropped rather than remembered.
		if ( zoom === 1 ) {
			panX = 0;
			panY = 0;
		}

		img.style.transform = 'translate(' + panX + 'px, ' + panY + 'px) scale(' + zoom + ')';
		img.classList.toggle( 'is-zoomed', zoom > 1 );
		frame.classList.toggle( 'is-zoomed', zoom > 1 );
	}

	function setZoom( next ) {
		zoom = clamp( next, ZOOM_MIN, ZOOM_MAX );
		applyZoom();
	}

	/** Zoom is per-slide: carrying it across would land the next image already magnified and off-centre. */
	function resetZoom() {
		zoom = 1;
		panX = 0;
		panY = 0;
		applyZoom();
	}

	function syncTools() {
		const zoomable = !! zoomTarget();
		zoomBtns.forEach( ( button ) => {
			button.hidden = ! zoomable;
		} );
	}

	function renderItem( list, i ) {
		gallery = list;
		index = ( i + list.length ) % list.length;
		dialog.classList.toggle( 'is-gallery', list.length > 1 );

		const src = gallery[ index ];
		const type = mediaType( src );

		if ( type === 'embed' ) {
			// Muted autoplay only — "no autoplay with sound" (handoff GlobalMediaLightbox C-03).
			const embedSrc = toEmbedUrl( src );
			const sep = embedSrc.indexOf( '?' ) > -1 ? '&' : '?';
			frame.innerHTML =
				'<iframe src="' + embedSrc + sep + 'autoplay=1&mute=1" title="Video" allow="autoplay; encrypted-media; fullscreen" allowfullscreen></iframe>';
		} else if ( type === 'video' ) {
			frame.innerHTML = '<video class="lightbox__media" src="' + src + '" controls autoplay muted playsinline></video>';
		} else if ( type === 'pdf' ) {
			// The browser's own viewer, which brings paging, search and its own zoom for free. The
			// link below it is not decoration: iOS Safari renders only the first page of a framed PDF
			// and offers no way to scroll it, so on that platform the link IS the reading experience.
			frame.innerHTML =
				'<div class="lightbox__document">' +
					'<iframe class="lightbox__pdf" src="' + src + '#view=FitH" title="' + documentLabel + '"></iframe>' +
					'<a class="lightbox__document-open" href="' + src + '" target="_blank" rel="noopener">' + documentLabel + '</a>' +
				'</div>';
		} else {
			frame.innerHTML = '<img class="lightbox__img" src="' + src + '" alt="" />';
		}

		// After the slide is built, not before: the reset has to reach the element that is now on
		// screen, so every image carries an explicit transform rather than inheriting whatever the
		// previous slide was left at.
		resetZoom();
		syncTools();

		const multi = list.length > 1;
		prevBtn.hidden = ! multi;
		nextBtn.hidden = ! multi;
		dots.innerHTML = multi
			? list
					.map( ( _, k ) => '<span class="lightbox__dot' + ( k === index ? ' is-active' : '' ) + '"></span>' )
					.join( '' )
			: '';
		counter.textContent = multi ? index + 1 + ' / ' + list.length : '';
	}

	function setBackgroundInert( isInert ) {
		Array.from( document.body.children ).forEach( ( el ) => {
			if ( el === dialog ) return;
			if ( isInert ) el.setAttribute( 'inert', '' );
			else el.removeAttribute( 'inert' );
		} );
	}

	function open() {
		dialog.hidden = false;
		document.body.style.overflow = 'hidden';
		lastFocused = document.activeElement;
		setBackgroundInert( true );
		document.addEventListener( 'keydown', onKeydown );

		requestAnimationFrame( () => {
			const focusables = focusableIn( dialog );
			( focusables[ 0 ] || dialog ).focus?.();
		} );
	}

	function close() {
		if ( dialog.hidden ) return;
		// Leaving the browser in full screen after the dialog it belonged to is gone would strand the
		// visitor on a page with no visible chrome.
		if ( document.fullscreenElement === dialog ) document.exitFullscreen?.();
		dialog.hidden = true;
		frame.innerHTML = '';
		document.body.style.overflow = '';
		gallery = [];
		resetZoom();
		setBackgroundInert( false );
		document.removeEventListener( 'keydown', onKeydown );
		lastFocused?.focus?.();
	}

	function step( delta ) {
		if ( gallery.length > 1 ) renderItem( gallery, index + delta );
	}

	function onKeydown( event ) {
		if ( dialog.hidden ) return;

		if ( event.key === 'Escape' ) {
			// While full screen, Escape is the browser's own way out of it; closing the dialog on the
			// same press would take away two things when the visitor asked for one.
			if ( document.fullscreenElement === dialog ) return;
			close();
			return;
		}
		if ( event.key === 'ArrowLeft' ) {
			step( -1 );
			return;
		}
		if ( event.key === 'ArrowRight' ) {
			step( 1 );
			return;
		}
		// Keyboard parity for the zoom buttons: every pointer gesture below has one.
		if ( zoomTarget() && ( event.key === '+' || event.key === '=' ) ) {
			setZoom( zoom + ZOOM_STEP );
			return;
		}
		if ( zoomTarget() && ( event.key === '-' || event.key === '_' ) ) {
			setZoom( zoom - ZOOM_STEP );
			return;
		}
		if ( zoomTarget() && event.key === '0' ) {
			resetZoom();
			return;
		}
		if ( event.key !== 'Tab' ) return;

		const focusables = focusableIn( dialog );
		if ( focusables.length === 0 ) return;
		const first = focusables[ 0 ];
		const last = focusables[ focusables.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	}

	triggers.forEach( ( trigger ) => {
		trigger.addEventListener( 'click', ( event ) => {
			event.preventDefault();
			const gallerySrc = trigger.getAttribute( 'data-gallery' );
			const videoSrc = trigger.getAttribute( 'data-video' );
			const imageSrc = trigger.getAttribute( 'data-image' );
			// A gallery trigger may name which item it represents (project-gallery thumbs) so the
			// dialog opens on that image rather than always the first.
			const startIndex = parseInt( trigger.getAttribute( 'data-gallery-index' ), 10 ) || 0;

			if ( gallerySrc ) renderItem( gallerySrc.split( ',' ).map( ( s ) => s.trim() ), startIndex );
			else if ( videoSrc ) renderItem( [ videoSrc ], 0 );
			else if ( imageSrc ) renderItem( [ imageSrc ], 0 );
			else return;

			open();
		} );
	} );

	dialog.querySelectorAll( '[data-lightbox-close]' ).forEach( ( el ) => el.addEventListener( 'click', close ) );
	prevBtn.addEventListener( 'click', () => step( -1 ) );
	nextBtn.addEventListener( 'click', () => step( 1 ) );

	zoomBtns.forEach( ( button ) =>
		button.addEventListener( 'click', () => {
			const action = button.getAttribute( 'data-lightbox-zoom' );
			if ( action === 'zoom-in' ) setZoom( zoom + ZOOM_STEP );
			else if ( action === 'zoom-out' ) setZoom( zoom - ZOOM_STEP );
			else resetZoom();
		} )
	);

	fullscreenBtn?.addEventListener( 'click', () => {
		if ( document.fullscreenElement === dialog ) document.exitFullscreen?.();
		else dialog.requestFullscreen?.();
	} );

	// Driven by the event rather than by the click, so leaving full screen through Escape or the
	// browser's own chrome keeps the button honest.
	document.addEventListener( 'fullscreenchange', () => {
		fullscreenBtn?.setAttribute( 'aria-pressed', document.fullscreenElement === dialog ? 'true' : 'false' );
		dialog.classList.toggle( 'is-fullscreen', document.fullscreenElement === dialog );
	} );

	// Wheel-to-zoom, but only over a zoomable slide and only once the dialog is open, so the page
	// behind it still scrolls normally. passive:false because the default scroll must be prevented.
	frame.addEventListener( 'wheel', ( event ) => {
		if ( dialog.hidden || ! zoomTarget() ) return;
		event.preventDefault();
		setZoom( zoom + ( event.deltaY < 0 ? ZOOM_STEP : -ZOOM_STEP ) );
	}, { passive: false } );

	// Double-click toggles between fit and a useful magnification rather than stepping, which is the
	// gesture people expect from a photo viewer.
	frame.addEventListener( 'dblclick', () => {
		if ( ! zoomTarget() ) return;
		setZoom( zoom > 1 ? 1 : 2 );
	} );

	// Drag to pan, once zoomed. Pointer events cover mouse, touch and pen in one path, and the capture
	// keeps the gesture alive if the pointer leaves the image mid-drag.
	let dragging = false;
	let originX = 0;
	let originY = 0;

	frame.addEventListener( 'pointerdown', ( event ) => {
		if ( zoom === 1 || ! zoomTarget() ) return;
		dragging = true;
		originX = event.clientX - panX;
		originY = event.clientY - panY;
		frame.setPointerCapture?.( event.pointerId );
		event.preventDefault();
	} );

	frame.addEventListener( 'pointermove', ( event ) => {
		if ( ! dragging ) return;
		panX = event.clientX - originX;
		panY = event.clientY - originY;
		applyZoom();
	} );

	[ 'pointerup', 'pointercancel' ].forEach( ( name ) =>
		frame.addEventListener( name, ( event ) => {
			if ( ! dragging ) return;
			dragging = false;
			frame.releasePointerCapture?.( event.pointerId );
		} )
	);
	dots.addEventListener( 'click', ( event ) => {
		const dot = event.target.closest( '.lightbox__dot' );
		if ( ! dot ) return;
		const dotIndex = Array.from( dots.children ).indexOf( dot );
		if ( dotIndex > -1 ) renderItem( gallery, dotIndex );
	} );
}

if ( document.readyState === 'loading' ) document.addEventListener( 'DOMContentLoaded', init );
else init();
