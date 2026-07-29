/**
 * Form feedback, as a toast.
 *
 * The CoreX runtime writes its outcome into a `<p class="corex-form__status">` under the form — a
 * line of text with a CSS pseudo-element for an icon and no entrance of any kind. Below a long form
 * it lands off-screen, so on a phone a visitor could submit successfully and see nothing happen
 * (client report 2026-07-27).
 *
 * Nothing here touches the framework: the runtime already emits `corex:form:success` and
 * `corex:form:error` on the form element, so this is a listener, not a patch. The status paragraph
 * stays in the DOM as the accessible live region — screen readers keep announcing it exactly as
 * before, and the toast is `aria-hidden` so the same message is not read twice.
 */

const DISMISS_MS = 6000;
const CONTAINER_ID = 'perego-toasts';

/** Matches the stylesheet's phone breakpoint, where the stack becomes a single top banner. */
const PHONE = '(max-width: 620px)';

/** How long the same message stays a repeat rather than a second, separate outcome. */
const REPEAT_MS = 1000;

/**
 * Keep the stack with the *visible* area, not the layout viewport.
 *
 * A rejected submit focuses the first invalid field, so on iOS the toast arrives with the keyboard
 * opening. `position: fixed` is resolved against the layout viewport, which the keyboard does not
 * resize — the toast was left floating over the middle of the page (client report 2026-07-29). The
 * visual viewport's own offset is the correction, and only the phone stylesheet consumes it, so
 * desktop is untouched. No API, no property, and the CSS fallback keeps the old position.
 */
function trackVisualViewport( stack ) {
	const viewport = window.visualViewport;
	if ( ! viewport ) {
		return;
	}

	const follow = () =>
		stack.style.setProperty(
			'--perego-toast-vv-offset',
			`${ Math.max( 0, viewport.offsetTop ) }px`
		);

	follow();
	viewport.addEventListener( 'resize', follow );
	viewport.addEventListener( 'scroll', follow );
}

/** One shared, lazily created stack, so several forms on a page cannot each build their own. */
function container() {
	let stack = document.getElementById( CONTAINER_ID );
	if ( ! stack ) {
		stack = document.createElement( 'div' );
		stack.id = CONTAINER_ID;
		stack.className = 'perego-toasts';
		// The live region is the form's own status paragraph; announcing this too would repeat it.
		stack.setAttribute( 'aria-hidden', 'true' );
		document.body.appendChild( stack );
		trackVisualViewport( stack );
	}
	return stack;
}

function dismiss( toast ) {
	if ( toast.dataset.leaving === '1' ) return;
	toast.dataset.leaving = '1';
	toast.classList.remove( 'is-visible' );

	// Wait for the transition, but never trust it to fire: a toast dismissed while its tab is in
	// the background gets no transitionend, and would stay in the DOM forever.
	const remove = () => toast.remove();
	toast.addEventListener( 'transitionend', remove, { once: true } );
	setTimeout( remove, 400 );
}

/** Single-path icons, drawn on a 20-unit grid so both sit identically inside the medallion. */
const ICON_PATHS = {
	success: 'M5 10.5l3.5 3.5L15 7',
	error: 'M10 5.5v5.5M10 14.2v.6',
};

function svgIcon( kind ) {
	const svg = document.createElementNS( 'http://www.w3.org/2000/svg', 'svg' );
	svg.setAttribute( 'viewBox', '0 0 20 20' );
	svg.setAttribute( 'aria-hidden', 'true' );
	svg.setAttribute( 'focusable', 'false' );

	const path = document.createElementNS( 'http://www.w3.org/2000/svg', 'path' );
	path.setAttribute( 'd', ICON_PATHS[ kind ] || ICON_PATHS.error );
	path.setAttribute( 'fill', 'none' );
	path.setAttribute( 'stroke', 'currentColor' );
	path.setAttribute( 'stroke-width', '2.2' );
	path.setAttribute( 'stroke-linecap', 'round' );
	path.setAttribute( 'stroke-linejoin', 'round' );
	svg.appendChild( path );

	return svg;
}

function closeIcon() {
	const svg = document.createElementNS( 'http://www.w3.org/2000/svg', 'svg' );
	svg.setAttribute( 'viewBox', '0 0 16 16' );
	svg.setAttribute( 'aria-hidden', 'true' );
	svg.setAttribute( 'focusable', 'false' );

	const path = document.createElementNS( 'http://www.w3.org/2000/svg', 'path' );
	// A drawn X, not the `×` character — a text glyph inherits the font's own metrics and never
	// optically centres against an icon set.
	path.setAttribute( 'd', 'M4 4l8 8M12 4l-8 8' );
	path.setAttribute( 'fill', 'none' );
	path.setAttribute( 'stroke', 'currentColor' );
	path.setAttribute( 'stroke-width', '1.8' );
	path.setAttribute( 'stroke-linecap', 'round' );
	svg.appendChild( path );

	return svg;
}

function element( tag, className, text ) {
	const node = document.createElement( tag );
	node.className = className;
	if ( text !== undefined ) {
		// textContent, never innerHTML: the message can originate from a server response.
		node.textContent = text;
	}
	return node;
}

/**
 * Is this the same outcome the visitor is already looking at?
 *
 * One submit can be announced twice: the framework emits `corex:form:error` from both its client
 * and server branches, and a block that runs its own submit (join-form/view.js) emits its own. Two
 * identical cards read as two failures. A repeat inside a second is the same event; a genuinely
 * different message, or the same one after the visitor tried again, still gets its own toast.
 */
function isRepeat( stack, signature ) {
	const latest = stack.lastElementChild;

	return (
		!! latest &&
		latest.dataset.signature === signature &&
		Date.now() - Number( latest.dataset.shownAt ) < REPEAT_MS
	);
}

function show( message, kind, copy ) {
	const text = String( message || '' ).trim();
	if ( text === '' ) return;

	const stack = container();
	const signature = `${ kind }:${ text }`;
	if ( isRepeat( stack, signature ) ) return;

	// One at a time on a phone: the banner spans the screen, so a second card under it covers the
	// fields the visitor has been sent back to fix. The newest outcome is the true one, and the old
	// card leaves without its exit transition — two banners crossing reads as a glitch, not a queue.
	if ( window.matchMedia( PHONE ).matches ) {
		Array.from( stack.children ).forEach( ( node ) => node.remove() );
	}

	const toast = element( 'div', `perego-toast perego-toast--${ kind }` );
	toast.dataset.signature = signature;
	toast.dataset.shownAt = String( Date.now() );

	const medallion = element( 'span', 'perego-toast__medallion' );
	medallion.setAttribute( 'aria-hidden', 'true' );
	medallion.appendChild( svgIcon( kind ) );

	const body = element( 'div', 'perego-toast__body' );
	const title = copy[ kind === 'success' ? 'successTitle' : 'errorTitle' ];
	if ( title ) {
		body.appendChild( element( 'p', 'perego-toast__title', title ) );
	}
	body.appendChild( element( 'p', 'perego-toast__message', text ) );

	const close = element( 'button', 'perego-toast__close' );
	close.type = 'button';
	close.setAttribute( 'aria-label', copy.dismiss );
	close.appendChild( closeIcon() );
	close.addEventListener( 'click', () => dismiss( toast ) );

	// The bar drains over the dismiss window, so the visitor can see how long they have rather than
	// having a message vanish mid-sentence. CSS animates it; the duration comes from the one constant.
	const timerBar = element( 'span', 'perego-toast__timer' );
	timerBar.setAttribute( 'aria-hidden', 'true' );
	timerBar.style.animationDuration = `${ DISMISS_MS }ms`;

	toast.append( medallion, body, close, timerBar );
	stack.appendChild( toast );

	// Next frame, so the element is in the DOM with its start state before the class flips and the
	// transition has something to animate from.
	requestAnimationFrame( () => toast.classList.add( 'is-visible' ) );

	let timer = setTimeout( () => dismiss( toast ), DISMISS_MS );

	// Reading a long error should not be a race against the timer. Pausing the CSS animation keeps
	// the bar honest about the time actually left, rather than draining while the clock is stopped.
	const hold = () => {
		clearTimeout( timer );
		timerBar.style.animationPlayState = 'paused';
	};
	const resume = () => {
		timerBar.style.animationPlayState = 'running';
		timer = setTimeout( () => dismiss( toast ), DISMISS_MS );
	};

	toast.addEventListener( 'mouseenter', hold );
	toast.addEventListener( 'focusin', hold );
	toast.addEventListener( 'mouseleave', resume );
	toast.addEventListener( 'focusout', resume );
}

export function initFormToasts() {
	const strings = window.peregoTheme || {};
	const copy = {
		dismiss: strings.toastDismiss || 'Dismiss',
		successTitle: strings.toastSuccessTitle || '',
		errorTitle: strings.toastErrorTitle || '',
	};

	// The runtime dispatches both events with `bubbles: true`, so one pair of document listeners
	// covers every form on the page, including any rendered after this ran.
	const relay = ( kind, fallbackAttribute ) => ( event ) => {
		const form = event.target;
		const status = form.querySelector?.( '.corex-form__status' )?.textContent;
		show( status || form.dataset?.[ fallbackAttribute ], kind, copy );
	};

	// The status paragraph is written immediately before the event is dispatched, so it already
	// holds the message the framework chose — including a server-supplied one.
	document.addEventListener( 'corex:form:success', relay( 'success', 'corexSuccess' ) );
	document.addEventListener( 'corex:form:error', relay( 'error', 'corexError' ) );
}
