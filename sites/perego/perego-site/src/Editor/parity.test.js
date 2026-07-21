/**
 * Markup-parity comparator (spec 021, T003; DECISIONS 2026-07-21).
 *
 * The live-canvas editor renders a block's real markup in edit(); PHP renders the same structure on
 * the front end. `normalizeMarkup` reduces an HTML string to a canonical tag+class+nesting skeleton so
 * a block's editor output can be asserted structurally equal to a fixture of its PHP render_callback
 * output, ignoring editable text, whitespace, and volatile attribute values (href/src/style/data-*).
 */
import { normalizeMarkup } from './parity';

describe( 'normalizeMarkup', () => {
	test.each( [
		[ 'text content and whitespace', '<div class="hero"><h1>Hello</h1></div>', '<div class="hero">\n\t<h1>  Goodbye  </h1>\n</div>' ],
		[ 'volatile attribute values such as href/src', '<a class="cta" href="/en/contact">Start</a>', '<a class="cta" href="/ar/contact">ابدأ</a>' ],
		[ 'class order', '<div class="a b"></div>', '<div class="b a"></div>' ],
	] )( 'treats markup differing only in %s as structurally equal', ( _label, a, b ) => {
		expect( normalizeMarkup( a ) ).toEqual( normalizeMarkup( b ) );
	} );

	test.each( [
		[ 'a different class list', '<div class="hero"><h1>x</h1></div>', '<div class="hero hero--wide"><h1>x</h1></div>' ],
		[ 'an extra element', '<ul><li>a</li></ul>', '<ul><li>a</li><li>b</li></ul>' ],
		[ 'a different tag name', '<section class="wrap"></section>', '<div class="wrap"></div>' ],
	] )( 'treats markup differing by %s as structurally different', ( _label, a, b ) => {
		expect( normalizeMarkup( a ) ).not.toEqual( normalizeMarkup( b ) );
	} );
} );
