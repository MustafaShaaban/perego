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
	return 'image';
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

	let gallery = [];
	let index = 0;
	let lastFocused = null;

	function renderItem( list, i ) {
		gallery = list;
		index = ( i + list.length ) % list.length;
		dialog.classList.toggle( 'is-gallery', list.length > 1 );

		const src = gallery[ index ];
		const type = mediaType( src );

		if ( type === 'embed' ) {
			const sep = src.indexOf( '?' ) > -1 ? '&' : '?';
			frame.innerHTML =
				'<iframe src="' + src + sep + 'autoplay=1" title="Video" allow="autoplay; encrypted-media; fullscreen" allowfullscreen></iframe>';
		} else if ( type === 'video' ) {
			frame.innerHTML = '<video class="lightbox__media" src="' + src + '" controls autoplay playsinline></video>';
		} else {
			frame.innerHTML = '<img class="lightbox__img" src="' + src + '" alt="" />';
		}

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
		dialog.hidden = true;
		frame.innerHTML = '';
		document.body.style.overflow = '';
		gallery = [];
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

			if ( gallerySrc ) renderItem( gallerySrc.split( ',' ).map( ( s ) => s.trim() ), 0 );
			else if ( videoSrc ) renderItem( [ videoSrc ], 0 );
			else if ( imageSrc ) renderItem( [ imageSrc ], 0 );
			else return;

			open();
		} );
	} );

	dialog.querySelectorAll( '[data-lightbox-close]' ).forEach( ( el ) => el.addEventListener( 'click', close ) );
	prevBtn.addEventListener( 'click', () => step( -1 ) );
	nextBtn.addEventListener( 'click', () => step( 1 ) );
	dots.addEventListener( 'click', ( event ) => {
		const dot = event.target.closest( '.lightbox__dot' );
		if ( ! dot ) return;
		const dotIndex = Array.from( dots.children ).indexOf( dot );
		if ( dotIndex > -1 ) renderItem( gallery, dotIndex );
	} );
}

if ( document.readyState === 'loading' ) document.addEventListener( 'DOMContentLoaded', init );
else init();
