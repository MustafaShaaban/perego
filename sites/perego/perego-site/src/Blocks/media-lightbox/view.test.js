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
		</div>
		<div class="lightbox" id="perego-media-lightbox" role="dialog" aria-modal="true" aria-label="Close" hidden>
			<div class="lightbox__backdrop" data-lightbox-close></div>
			<div class="lightbox__inner">
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
