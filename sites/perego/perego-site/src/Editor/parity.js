/**
 * Markup-parity comparator (spec 021, T003; DECISIONS 2026-07-21, refining framework #43).
 *
 * The live-canvas editor renders a block's real markup in edit(); PHP renders the same structure on the
 * front end. `normalizeMarkup` reduces an HTML string to a canonical tag + sorted-class + nesting skeleton
 * so a block's editor output can be asserted structurally equal to a fixture of its PHP render_callback
 * output. It deliberately ignores editable text, whitespace, and volatile/behavioral attribute values
 * (href, src, style, data-*) — only element tags, their class hooks, and their nesting are compared,
 * because those are what the shared CSS contract renders against. Which subtree to compare, and any
 * intentional front-end-only omissions (hamburger, language toggle), are decided per block by the caller.
 */

const ELEMENT_NODE = 1;

function elementSkeleton( node ) {
	if ( node.nodeType !== ELEMENT_NODE ) {
		return null;
	}

	const classes = ( node.getAttribute( 'class' ) || '' )
		.split( /\s+/ )
		.filter( Boolean )
		.sort();

	return {
		tag: node.tagName.toLowerCase(),
		classes,
		children: Array.from( node.childNodes ).map( elementSkeleton ).filter( Boolean ),
	};
}

/**
 * @param {string} html Raw HTML string (an editor render, or a PHP-render fixture).
 * @return {Array<{tag:string,classes:string[],children:Array}>} Canonical structural skeleton.
 */
export function normalizeMarkup( html ) {
	const template = document.createElement( 'template' );
	template.innerHTML = typeof html === 'string' ? html.trim() : '';

	return Array.from( template.content.childNodes )
		.map( elementSkeleton )
		.filter( Boolean );
}
