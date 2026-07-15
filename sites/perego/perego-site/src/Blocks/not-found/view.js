/**
 * Locked 404 handoff interaction. Movement is decorative and is deliberately
 * disabled for people who request reduced motion.
 */
const mediaQuery = window.matchMedia( '(prefers-reduced-motion: reduce)' );

if ( ! mediaQuery.matches ) {
	const page = document.querySelector( '.error-page' );
	const inner = page?.querySelector( '.error-page__inner' );
	const orbs = [ ...page?.querySelectorAll( '.orb' ) || [] ];

	window.addEventListener( 'pointermove', ( event ) => {
		const x = event.clientX / window.innerWidth - 0.5;
		const y = event.clientY / window.innerHeight - 0.5;

		if ( inner ) {
			inner.style.transform = `translate3d(${ x * 16 }px, ${ y * 16 }px, 0)`;
		}

		orbs.forEach( ( orb, index ) => {
			const distance = ( index + 1 ) * 10;
			orb.style.marginLeft = `${ x * distance }px`;
			orb.style.marginTop = `${ y * distance }px`;
		} );
	} );
}
