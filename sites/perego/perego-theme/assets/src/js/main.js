/**
 * Perego theme front-end entry (assets/src/js/main.js). `npm run scripts` builds it to
 * assets/js/main.js with a hashed assets/js/main.asset.php (cache-busting via the CoreX Script
 * helper). Styles compile separately from assets/src/scss/ (`npm run styles`). Keep this small
 * and dependency-free — progressive enhancement only.
 */
document.addEventListener( 'DOMContentLoaded', () => {
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
