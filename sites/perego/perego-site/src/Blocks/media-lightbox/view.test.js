// jsdom never computes layout, so `offsetParent` (used by view.js to skip hidden/off-screen
// focusables) is always null. Stub it from the `hidden` attribute instead, matching how a real
// browser would report it for these dialog controls.
Object.defineProperty( HTMLElement.prototype, 'offsetParent', {
	get() {
		return this.hidden || this.closest( '[hidden]' ) ? null : {};
	},
} );

function loadLightbox() {
	jest.resetModules();
	document.body.innerHTML = `
		<div id="page">
			<button type="button" data-image="https://perego.local/a.jpg">Open image</button>
			<button type="button" data-video="https://www.youtube.com/embed/abc123">Open video</button>
			<button type="button" data-gallery="https://perego.local/a.jpg, https://perego.local/b.jpg, https://perego.local/c.jpg">Open gallery</button>
			<button type="button" id="thumb-b" data-gallery="https://perego.local/a.jpg, https://perego.local/b.jpg, https://perego.local/c.jpg" data-gallery-index="1">Open gallery at b</button>
			<button type="button" id="doc-only" data-gallery="https://perego.local/case-study.pdf">Open document</button>
			<button type="button" id="mixed" data-gallery="https://perego.local/a.jpg, https://perego.local/case-study.pdf">Open image + document</button>
		</div>
		<div class="lightbox" id="perego-media-lightbox" role="dialog" aria-modal="true" aria-label="Close"
			data-lightbox-document-label="Open the document" hidden>
			<div class="lightbox__backdrop" data-lightbox-close></div>
			<div class="lightbox__inner">
				<div class="lightbox__tools" data-lightbox-tools>
					<button type="button" class="lightbox__tool" data-lightbox-zoom="zoom-out" aria-label="Zoom out" hidden>&minus;</button>
					<button type="button" class="lightbox__tool" data-lightbox-zoom="zoom-reset" aria-label="Reset zoom" hidden>&#9673;</button>
					<button type="button" class="lightbox__tool" data-lightbox-zoom="zoom-in" aria-label="Zoom in" hidden>&plus;</button>
					<button type="button" class="lightbox__tool" data-lightbox-fullscreen aria-pressed="false" aria-label="Full screen">&#9974;</button>
				</div>
				<button type="button" class="lightbox__close" aria-label="Close" data-lightbox-close>&times;</button>
				<button type="button" class="lightbox__nav lightbox__nav--prev" aria-label="Previous" data-lightbox-prev hidden>&#8249;</button>
				<div class="lightbox__frame"></div>
				<button type="button" class="lightbox__nav lightbox__nav--next" aria-label="Next" data-lightbox-next hidden>&#8250;</button>
				<div class="lightbox__dots" data-lightbox-dots></div>
				<p class="screen-reader-text" data-lightbox-counter role="status" aria-live="polite"></p>
			</div>
		</div>`;
	require( './view.js' );
}

function dialog() {
	return document.getElementById( 'perego-media-lightbox' );
}

test( 'opens on an image trigger, showing an image frame with no gallery nav', () => {
	loadLightbox();
	document.querySelector( '[data-image]' ).click();

	expect( dialog().hidden ).toBe( false );
	expect( dialog().querySelector( '.lightbox__frame img' ) ).not.toBeNull();
	expect( dialog().querySelector( '[data-lightbox-prev]' ).hidden ).toBe( true );
	expect( document.body.style.overflow ).toBe( 'hidden' );
} );

test( 'opens a YouTube trigger as a MUTED autoplaying iframe embed (no autoplay with sound)', () => {
	loadLightbox();
	document.querySelector( '[data-video]' ).click();

	const iframe = dialog().querySelector( '.lightbox__frame iframe' );
	expect( iframe ).not.toBeNull();
	expect( iframe.src ).toContain( 'autoplay=1' );
	expect( iframe.src ).toContain( 'mute=1' );
} );

test( 'a direct video file autoplays muted', () => {
	loadLightbox();
	const trigger = document.querySelector( '[data-video]' );
	trigger.setAttribute( 'data-video', 'https://perego.local/reel.mp4' );
	trigger.click();

	const video = dialog().querySelector( '.lightbox__frame video' );
	expect( video ).not.toBeNull();
	expect( video.hasAttribute( 'muted' ) ).toBe( true );
	expect( video.hasAttribute( 'autoplay' ) ).toBe( true );
} );

test( 'a gallery trigger with data-gallery-index opens on that image (project-gallery thumbs)', () => {
	loadLightbox();
	document.getElementById( 'thumb-b' ).click();

	expect( dialog().querySelector( '.lightbox__img' ).src ).toContain( 'b.jpg' );
	const dots = dialog().querySelectorAll( '.lightbox__dot' );
	expect( dots[ 1 ].classList.contains( 'is-active' ) ).toBe( true );
} );

describe( 'normalizes pasted watch/page URLs to their embeddable form (prevents YouTube Error 153)', () => {
	// A normal "watch?v=" URL is what an editor naturally copies from the address bar — it is NOT
	// embeddable; loading it in an iframe is exactly what produces YouTube's own error page instead
	// of the video.
	test.each( [
		[ 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ' ],
		[ 'https://www.youtube.com/watch?list=PL123&v=dQw4w9WgXcQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ' ],
		[ 'https://youtu.be/dQw4w9WgXcQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ' ],
		[ 'https://www.youtube.com/shorts/dQw4w9WgXcQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ' ],
		[ 'https://vimeo.com/76979871', 'https://player.vimeo.com/video/76979871' ],
	] )( '%s -> %s', ( pasted, expectedEmbed ) => {
		loadLightbox();
		document.querySelector( '[data-video]' ).setAttribute( 'data-video', pasted );
		document.querySelector( '[data-video]' ).click();

		const iframe = dialog().querySelector( '.lightbox__frame iframe' );
		expect( iframe.src ).toContain( expectedEmbed );
	} );

	test( 'leaves an already-embeddable YouTube URL unchanged', () => {
		loadLightbox();
		document.querySelector( '[data-video]' ).click(); // fixture already uses .../embed/abc123

		const iframe = dialog().querySelector( '.lightbox__frame iframe' );
		expect( iframe.src ).toContain( 'youtube.com/embed/abc123' );
	} );

	test( 'leaves an already-embeddable Vimeo player URL unchanged', () => {
		loadLightbox();
		document.querySelector( '[data-video]' ).setAttribute( 'data-video', 'https://player.vimeo.com/video/76979871' );
		document.querySelector( '[data-video]' ).click();

		const iframe = dialog().querySelector( '.lightbox__frame iframe' );
		expect( iframe.src ).toContain( 'player.vimeo.com/video/76979871' );
	} );
} );

test( 'opens a gallery trigger with working prev/next/dots and wraparound', () => {
	loadLightbox();
	document.querySelector( '[data-gallery]' ).click();

	expect( dialog().querySelector( '[data-lightbox-prev]' ).hidden ).toBe( false );
	expect( dialog().querySelectorAll( '.lightbox__dot' ) ).toHaveLength( 3 );
	expect( dialog().querySelector( '.lightbox__img' ).src ).toContain( 'a.jpg' );

	dialog().querySelector( '[data-lightbox-next]' ).click();
	expect( dialog().querySelector( '.lightbox__img' ).src ).toContain( 'b.jpg' );

	// Wrap from the last item back to the first.
	dialog().querySelector( '[data-lightbox-next]' ).click();
	dialog().querySelector( '[data-lightbox-next]' ).click();
	expect( dialog().querySelector( '.lightbox__img' ).src ).toContain( 'a.jpg' );
} );

test( 'closes on Escape, clears the frame, restores scroll, and restores focus to the trigger', () => {
	loadLightbox();
	const trigger = document.querySelector( '[data-image]' );
	trigger.focus();
	trigger.click();

	document.dispatchEvent( new KeyboardEvent( 'keydown', { key: 'Escape', bubbles: true } ) );

	expect( dialog().hidden ).toBe( true );
	expect( dialog().querySelector( '.lightbox__frame' ).innerHTML ).toBe( '' );
	expect( document.body.style.overflow ).toBe( '' );
	expect( document.activeElement ).toBe( trigger );
} );

test( 'closes on backdrop click', () => {
	loadLightbox();
	document.querySelector( '[data-image]' ).click();
	dialog().querySelector( '.lightbox__backdrop' ).click();

	expect( dialog().hidden ).toBe( true );
} );

test( 'marks the rest of the page inert while open and restores it on close', () => {
	loadLightbox();
	document.querySelector( '[data-image]' ).click();

	expect( document.getElementById( 'page' ).hasAttribute( 'inert' ) ).toBe( true );

	document.dispatchEvent( new KeyboardEvent( 'keydown', { key: 'Escape', bubbles: true } ) );

	expect( document.getElementById( 'page' ).hasAttribute( 'inert' ) ).toBe( false );
} );

test( 'traps Tab focus inside the dialog', () => {
	loadLightbox();
	document.querySelector( '[data-gallery]' ).click(); // gallery gives prev/next/close = multiple focusables

	const focusables = Array.from(
		dialog().querySelectorAll( 'button:not([hidden])' )
	);
	const last = focusables[ focusables.length - 1 ];
	last.focus();

	const event = new KeyboardEvent( 'keydown', { key: 'Tab', bubbles: true, cancelable: true } );
	document.dispatchEvent( event );

	expect( document.activeElement ).toBe( focusables[ 0 ] );
} );

/* ------------------------------------------------------------------
   Documents, zoom and full screen (owner, 2026-07-28)
   ------------------------------------------------------------------ */

const zoomButton = ( action ) => dialog().querySelector( `[data-lightbox-zoom="${ action }"]` );
const image = () => dialog().querySelector( '.lightbox__frame img' );

test( 'a .pdf slide opens in the browser viewer, with a link out for iOS Safari', () => {
	loadLightbox();
	document.getElementById( 'doc-only' ).click();

	const iframe = dialog().querySelector( '.lightbox__pdf' );
	const link = dialog().querySelector( '.lightbox__document-open' );

	expect( iframe ).not.toBeNull();
	// #view=FitH opens fitted to the width, which is how a document wants to be read.
	expect( iframe.getAttribute( 'src' ) ).toBe( 'https://perego.local/case-study.pdf#view=FitH' );
	expect( link.getAttribute( 'href' ) ).toBe( 'https://perego.local/case-study.pdf' );
	expect( link.textContent ).toBe( 'Open the document' );
	// It must not be mistaken for an image: object-fit and zoom would both be wrong.
	expect( image() ).toBeNull();
} );

test( 'a document rides the same gallery list as the artwork', () => {
	loadLightbox();
	document.getElementById( 'mixed' ).click();

	expect( image() ).not.toBeNull();
	expect( dialog().querySelector( '[data-lightbox-next]' ).hidden ).toBe( false );

	dialog().querySelector( '[data-lightbox-next]' ).click();
	expect( dialog().querySelector( '.lightbox__pdf' ) ).not.toBeNull();
} );

test( 'the zoom controls appear for an image and sit out every other slide type', () => {
	loadLightbox();

	document.querySelector( '[data-image]' ).click();
	expect( zoomButton( 'zoom-in' ).hidden ).toBe( false );

	document.querySelector( '[data-lightbox-close]' ).click();
	document.getElementById( 'doc-only' ).click();
	expect( zoomButton( 'zoom-in' ).hidden ).toBe( true );

	document.querySelector( '[data-lightbox-close]' ).click();
	document.querySelector( '[data-video]' ).click();
	expect( zoomButton( 'zoom-in' ).hidden ).toBe( true );
} );

test( 'zooming in scales the image and zooming out again returns it to fit', () => {
	loadLightbox();
	document.querySelector( '[data-image]' ).click();

	zoomButton( 'zoom-in' ).click();
	expect( image().style.transform ).toContain( 'scale(1.5)' );
	expect( image().classList.contains( 'is-zoomed' ) ).toBe( true );

	zoomButton( 'zoom-out' ).click();
	expect( image().style.transform ).toContain( 'scale(1)' );
	expect( image().classList.contains( 'is-zoomed' ) ).toBe( false );
} );

test( 'zoom stops at 1x rather than inverting the image', () => {
	loadLightbox();
	document.querySelector( '[data-image]' ).click();

	for ( let i = 0; i < 6; i++ ) zoomButton( 'zoom-out' ).click();

	expect( image().style.transform ).toContain( 'scale(1)' );
} );

/*
 * Regression guard: zoom used to be a property of the dialog rather than of the slide, so stepping to
 * the next image inherited the previous one's magnification and pan — landing the visitor on a photo
 * already cropped into its own corner.
 */
test( 'zoom resets when the slide changes', () => {
	loadLightbox();
	document.querySelector( '[data-gallery]' ).click();

	zoomButton( 'zoom-in' ).click();
	expect( image().style.transform ).toContain( 'scale(1.5)' );

	dialog().querySelector( '[data-lightbox-next]' ).click();
	expect( image().style.transform ).toContain( 'scale(1)' );
} );

test( 'the keyboard can zoom without reaching for the buttons', () => {
	loadLightbox();
	document.querySelector( '[data-image]' ).click();

	document.dispatchEvent( new KeyboardEvent( 'keydown', { key: '+' } ) );
	expect( image().style.transform ).toContain( 'scale(1.5)' );

	document.dispatchEvent( new KeyboardEvent( 'keydown', { key: '0' } ) );
	expect( image().style.transform ).toContain( 'scale(1)' );
} );

test( 'the full-screen toggle asks the dialog itself to go full screen', () => {
	loadLightbox();
	const request = jest.fn();
	dialog().requestFullscreen = request;

	document.querySelector( '[data-image]' ).click();
	dialog().querySelector( '[data-lightbox-fullscreen]' ).click();

	expect( request ).toHaveBeenCalled();
} );

/*
 * Driven by `fullscreenchange` rather than by the click, so leaving full screen through Escape or the
 * browser's own chrome — neither of which goes through our handler — still leaves the button honest.
 */
test( 'the toggle follows the browser out of full screen, however it was left', () => {
	loadLightbox();
	const button = dialog().querySelector( '[data-lightbox-fullscreen]' );

	Object.defineProperty( document, 'fullscreenElement', { value: dialog(), configurable: true } );
	document.dispatchEvent( new Event( 'fullscreenchange' ) );
	expect( button.getAttribute( 'aria-pressed' ) ).toBe( 'true' );

	Object.defineProperty( document, 'fullscreenElement', { value: null, configurable: true } );
	document.dispatchEvent( new Event( 'fullscreenchange' ) );
	expect( button.getAttribute( 'aria-pressed' ) ).toBe( 'false' );
} );

test( 'Escape leaves full screen first and keeps the dialog open', () => {
	loadLightbox();
	document.querySelector( '[data-image]' ).click();

	Object.defineProperty( document, 'fullscreenElement', { value: dialog(), configurable: true } );
	document.dispatchEvent( new KeyboardEvent( 'keydown', { key: 'Escape' } ) );
	expect( dialog().hidden ).toBe( false );

	Object.defineProperty( document, 'fullscreenElement', { value: null, configurable: true } );
	document.dispatchEvent( new KeyboardEvent( 'keydown', { key: 'Escape' } ) );
	expect( dialog().hidden ).toBe( true );
} );
