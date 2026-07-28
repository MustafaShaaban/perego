/**
 * Fetch each portfolio site's own logo, once, into a staging folder for review.
 *
 * The client asked for the website portfolio to show logos rather than screenshots, and pointed out
 * that every organisation publishes its logo on its own site. This collects the best candidate each
 * site offers, in preference order, and writes a manifest so the result can be checked before
 * anything is imported into the media library.
 *
 * It deliberately does NOT write to WordPress. Fetching 29 third-party sites is best-effort —
 * government portals in particular sit behind WAFs that answer a script with a challenge page — so
 * the failures have to be visible and reviewable, not silently swallowed into a half-populated grid.
 *
 * Usage: node scripts/fetch-portfolio-logos.mjs [--out <dir>]
 */

import { mkdir, writeFile } from 'node:fs/promises';
import { basename, extname, join } from 'node:path';
import process from 'node:process';

/** The client's list, in the client's order. `iso` is only used to name the saved file. */
const SITES = [
	{ order: 1, slug: 'bullion-trading-center', name: 'Bullion Trading Center', url: 'https://bulliontradingcenter.com/' },
	{ order: 2, slug: 'etcc', name: 'Emirati Talent Competitiveness Council', url: 'https://www.etcc.gov.ae/' },
	{ order: 3, slug: 'nafis-award', name: 'Nafis Award', url: 'https://nafisaward.etcc.gov.ae/' },
	{ order: 4, slug: 'enrra', name: 'Egyptian Nuclear and Radiological Regulatory Authority', url: 'https://enrra.org/' },
	{ order: 5, slug: 'moe-lessons', name: 'Ministry of Education Lessons Platform', url: 'https://lessons.moe.gov.eg/' },
	{ order: 6, slug: 'oshco', name: 'Olayan Saudi Holding Company — OSHCO', url: 'https://www.oshco.com/' },
	{ order: 7, slug: 'blackstone-eit', name: 'BlackStone eIT', url: 'https://blackstoneeit.com/' },
	{ order: 8, slug: 'quanta-egypt', name: 'Quanta Egypt', url: 'https://quanta-egypt.com/' },
	{ order: 9, slug: 'hdc-global', name: 'HDC Global', url: 'https://hdc-global.com/' },
	{ order: 10, slug: 'port-said-university', name: 'Port Said University', url: 'https://psu.edu.eg/' },
	{ order: 11, slug: 'rocc', name: 'ROCC', url: 'https://rocc.com/' },
	{ order: 12, slug: 'redcon-properties', name: 'Redcon Properties', url: 'https://www.redconproperties.com/' },
	{ order: 13, slug: 'wis-international', name: 'WIS International', url: 'https://wisintl.com/' },
	{ order: 14, slug: 'eand', name: 'e& / Etisalat UAE', url: 'https://www.eand.ae/' },
	// Two overrides, because heuristics cannot win here and should not keep being tuned until they
	// appear to. NAMA's masthead holds a near-white decorative flourish before its wordmark, and Our
	// Forum's page opens with a sponsor row — so "the first logo-shaped image" is Air Arabia's logo
	// on one and an invisible watermark on the other. Both sites publish their real mark at a stable
	// path; naming it is honest and auditable, and it is two lines rather than a cleverer guess.
	{ order: 15, slug: 'nama-women', name: 'NAMA Women Advancement', url: 'https://namawomen.ae/', logo: 'https://namawomen.ae/wp-content/uploads/sites/9/2024/07/nama-logo-white.png' },
	{ order: 16, slug: 'our-forum', name: 'Our Forum', url: 'https://ourforum.ae/', logo: 'https://ourforum.ae/wp-content/uploads/sites/19/2025/04/Dark-LOGO.png' },
	{ order: 17, slug: 'cultural-office', name: 'Cultural Office', url: 'https://culturaloffice.ae/' },
	{ order: 18, slug: 'rubu-qarn', name: 'Rubu’ Qarn', url: 'https://rqsharjah.ae/' },
	{ order: 19, slug: 'sharjah-youth', name: 'Sharjah Youth', url: 'https://shjyouth.ae/' },
	{ order: 20, slug: 'reyada-center', name: 'Reyada Center', url: 'https://reyadacenter.ae/' },
	{ order: 21, slug: 'hpd', name: 'Health Promotion Department', url: 'https://hpd.ae/' },
	{ order: 22, slug: 'sheikh-sultan-award', name: 'Sheikh Sultan Award for Celebrating the Spirit of Youth', url: 'https://sheikhsultanaward.ae/' },
	{ order: 23, slug: 'children-of-sharjah', name: 'Children of Sharjah', url: 'https://shjch.ae/' },
	{ order: 24, slug: 'sharjah-womens-sports', name: 'Sharjah Women’s Sports', url: 'https://www.sws.gov.ae/' },
	{ order: 25, slug: 'fann-media', name: 'FANN Media Discovery Platform', url: 'https://fannmedia.ae/' },
	{ order: 26, slug: 'child-safety', name: 'Child Safety Department', url: 'https://childsafety.gov.ae/' },
	{ order: 27, slug: 'sharjah-cd', name: 'Sharjah Capability Development', url: 'https://sharjahcd.ae/' },
	{ order: 28, slug: 'sajaya', name: 'Sajaya Young Ladies of Sharjah', url: 'https://sajaya.ae/' },
	{ order: 29, slug: 'sharjah-olympic-center', name: 'Sharjah Olympic Center for Women’s Sports', url: 'https://oc.sws.gov.ae/' },
];

// A browser UA, because several of these sites serve a challenge or a stripped page to anything
// that identifies as a script. This is a public page being read exactly as a visitor reads it.
const HEADERS = {
	'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36',
	'Accept': 'text/html,application/xhtml+xml,image/avif,image/webp,*/*;q=0.8',
	'Accept-Language': 'en-US,en;q=0.9,ar;q=0.8',
};

const TIMEOUT_MS = 20000;
const MIN_BYTES = 700; // below this it is a spacer or a 1px tracking pixel, not a logo

async function get( url, as = 'text' ) {
	const controller = new AbortController();
	const timer = setTimeout( () => controller.abort(), TIMEOUT_MS );
	try {
		const response = await fetch( url, { headers: HEADERS, redirect: 'follow', signal: controller.signal } );
		if ( ! response.ok ) {
			throw new Error( `HTTP ${ response.status }` );
		}
		return as === 'text' ? await response.text() : Buffer.from( await response.arrayBuffer() );
	} finally {
		clearTimeout( timer );
	}
}

const attr = ( tag, name ) => tag.match( new RegExp( `${ name }\\s*=\\s*["']([^"']+)["']`, 'i' ) )?.[ 1 ] || '';

/**
 * Candidate logo URLs, best first.
 *
 * An `<img>` in the masthead whose name says "logo" is the actual brand mark and the thing the
 * client asked for. A touch icon is a deliberate, square, high-resolution brand asset and is the
 * next best thing. `og:image` is last because it is often a photograph or a social banner rather
 * than a mark.
 */
function candidates( html, base ) {
	const secure = new URL( base ).protocol === 'https:';
	const found = [];
	const push = ( raw ) => {
		if ( ! raw ) return;
		try {
			const url = new URL( raw.trim(), base );
			// Several of these sites print their asset URLs as http:// while serving the page over
			// https, and their http port then refuses the connection outright. Same host, same
			// path — only the scheme is stale, so upgrade rather than record a false miss.
			if ( secure && url.protocol === 'http:' && url.hostname === new URL( base ).hostname ) {
				url.protocol = 'https:';
			}
			found.push( url.href );
		} catch ( e ) { /* a malformed src is not worth a failure */ }
	};

	const firstFrom = ( srcset ) => ( srcset || '' ).split( ',' )[ 0 ]?.trim().split( /\s+/ )[ 0 ] || '';

	// The whole document, not a slice of it: these are 200-250 KB pages and an arbitrary window
	// silently dropped the <head> icons on the longer ones.
	for ( const tag of html.match( /<img\b[^>]*>/gi ) || [] ) {
		const haystack = `${ attr( tag, 'src' ) } ${ attr( tag, 'srcset' ) } ${ attr( tag, 'alt' ) } ${ attr( tag, 'class' ) } ${ attr( tag, 'id' ) }`.toLowerCase();
		if ( /logo|brand|identity/.test( haystack ) ) {
			push( attr( tag, 'data-src' ) || attr( tag, 'src' ) || firstFrom( attr( tag, 'srcset' ) ) );
		}
	}

	const links = html.match( /<link\b[^>]*>/gi ) || [];
	for ( const tag of links ) {
		if ( attr( tag, 'rel' ).toLowerCase().includes( 'apple-touch-icon' ) ) push( attr( tag, 'href' ) );
	}
	for ( const tag of links ) {
		const rel = attr( tag, 'rel' ).toLowerCase();
		if ( rel.includes( 'icon' ) && ! rel.includes( 'apple' ) ) push( attr( tag, 'href' ) );
	}

	// A logo set as a CSS background is common on hand-built headers.
	for ( const match of html.match( /url\((["']?)[^)"']*logo[^)"']*\1\)/gi ) || [] ) {
		push( match.replace( /^url\((["']?)/i, '' ).replace( /(["']?)\)$/, '' ) );
	}

	// `og:image` is deliberately NOT a candidate. It is as often a stock photograph or a social
	// banner as a mark — Bullion Trading Center's is a picture of a hand holding a gold bar — and a
	// wall of logos with one photograph in it looks broken. No logo is better than a wrong one; the
	// grid falls back to a typographic name plate.

	return [ ...new Set( found ) ];
}

/**
 * Rank candidates so the site's OWN mark wins.
 *
 * Taking the first logo-shaped image on the page is wrong on any site with a sponsor row or a
 * decorative watermark: it picked Air Arabia's logo for Our Forum and a faint background flourish
 * for NAMA. A file named after the site itself is almost always the masthead mark, so score for that
 * first, then any other logo-named asset, then the touch icon.
 */
function rank( urls, site ) {
	const tokens = site.slug.split( '-' ).filter( ( token ) => token.length > 2 );
	const score = ( url ) => {
		const name = decodeURIComponent( url ).toLowerCase();
		const own = tokens.some( ( token ) => name.includes( token ) );
		const isLogo = /logo|brand|identity/.test( name );
		if ( own && isLogo ) return 0;
		if ( own ) return 1;
		if ( isLogo ) return 2;
		return 3; // an icon: a real brand asset, just usually the least refined one
	};

	return [ ...urls ].sort( ( a, b ) => score( a ) - score( b ) );
}

function extensionFor( url, bytes ) {
	const fromUrl = extname( new URL( url ).pathname ).toLowerCase();
	if ( [ '.svg', '.png', '.jpg', '.jpeg', '.webp', '.gif' ].includes( fromUrl ) ) {
		return fromUrl === '.jpeg' ? '.jpg' : fromUrl;
	}
	// Sniff, for the extensionless CDN URLs several of these sites use.
	if ( bytes.slice( 0, 4 ).toString( 'hex' ) === '89504e47' ) return '.png';
	if ( bytes.slice( 0, 2 ).toString( 'hex' ) === 'ffd8' ) return '.jpg';
	if ( bytes.slice( 0, 5 ).toString( 'utf8' ).includes( '<svg' ) || bytes.slice( 0, 200 ).toString( 'utf8' ).includes( '<svg' ) ) return '.svg';
	if ( bytes.slice( 8, 12 ).toString( 'utf8' ) === 'WEBP' ) return '.webp';
	return '';
}

async function collect( site, outDir ) {
	const result = { ...site, status: 'failed', file: '', source: '', bytes: 0, note: '' };

	let html;
	try {
		html = await get( site.url );
	} catch ( error ) {
		result.note = `homepage unreachable: ${ error.message }`;
		return result;
	}

	const urls = site.logo
		? [ site.logo ]
		: rank( candidates( html, site.url ), site );
	if ( urls.length === 0 ) {
		result.note = 'page fetched, but it exposes no logo, touch icon or og:image';
		return result;
	}

	for ( const url of urls ) {
		try {
			const bytes = await get( url, 'binary' );
			const extension = extensionFor( url, bytes );
			if ( ! extension ) continue;
			if ( extension !== '.svg' && bytes.length < MIN_BYTES ) continue;

			const file = `${ String( site.order ).padStart( 2, '0' ) }-${ site.slug }${ extension }`;
			await writeFile( join( outDir, file ), bytes );
			Object.assign( result, { status: 'ok', file, source: url, bytes: bytes.length } );
			return result;
		} catch ( error ) {
			result.note = `${ basename( url ) }: ${ error.message }`;
		}
	}

	result.note = result.note || `no usable candidate among ${ urls.length } found`;
	return result;
}

const outFlag = process.argv.indexOf( '--out' );
const outDir = outFlag > -1 ? process.argv[ outFlag + 1 ] : 'logos';
await mkdir( outDir, { recursive: true } );

const results = [];
for ( const site of SITES ) {
	// Sequential on purpose: 29 parallel requests to a handful of shared government hosts reads
	// like a burst of traffic, and there is no hurry here.
	const result = await collect( site, outDir );
	results.push( result );
	process.stdout.write(
		`${ result.status === 'ok' ? 'ok  ' : 'MISS' } ${ String( site.order ).padStart( 2 ) }. ${ site.name }` +
		`${ result.status === 'ok' ? ` -> ${ result.file } (${ result.bytes }b)` : ` -- ${ result.note }` }\n`
	);
}

await writeFile( join( outDir, 'manifest.json' ), JSON.stringify( results, null, '\t' ) );

const ok = results.filter( ( r ) => r.status === 'ok' ).length;
process.stdout.write( `\n${ ok }/${ results.length } logos collected into ${ outDir }\n` );
