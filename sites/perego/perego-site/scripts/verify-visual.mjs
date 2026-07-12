/**
 * Headless verification pass (spec Phases 11 + 13). Drives Chromium against the live site and, for
 * each route × language × breakpoint, asserts the handoff's hard rules:
 *   - no horizontal page scrolling (documentElement.scrollWidth ≤ viewport width, small tolerance)
 *   - no JS console errors; no broken sub-resources on a 200 page
 *   - exactly one <h1>
 *   - correct <html lang>/dir for the language
 *
 * The Arabic URL for each route is taken from that page's own `hreflang="ar"` alternate (Polylang's
 * truth), NOT by prefixing `/ar/` — under Polylang Free the AR slug is de-duplicated (e.g. contact →
 * /ar/contact-2/), so a naïve prefix would test the wrong URL. A route with no AR alternate is
 * reported as an (informational) missing-translation gap, not a hard failure.
 *
 * Reaches the WAMP vhost via Chromium host-resolver-rules (perego.local → 127.0.0.1). Writes JSON
 * evidence to output/verify-visual.json and exits non-zero on any hard failure so it can gate.
 * Run: node sites/perego/perego-site/scripts/verify-visual.mjs
 */
import { chromium } from 'playwright';
import { writeFileSync, mkdirSync } from 'node:fs';

const BASE = 'http://perego.local';
const OVERFLOW_TOLERANCE = 2; // px — sub-pixel rounding only.

const ROUTES = [
	{ path: '/', label: 'home' },
	{ path: '/services/', label: 'services-archive' },
	{ path: '/work/', label: 'work-archive' },
	{ path: '/journal/', label: 'journal' },
	{ path: '/contact/', label: 'contact' },
	{ path: '/services/website-making/', label: 'single-service' },
	{ path: '/work/landing-page-microsite/', label: 'single-project' },
	{ path: '/behind-the-scenes-of-a-brand-film-example/', label: 'single-post' },
	{ path: '/terms/', label: 'legal' },
	{ path: '/?s=video', label: 'search' },
	{ path: '/this-route-does-not-exist/', label: '404', is404: true },
];

const BREAKPOINTS = [
	{ name: 'mobile', width: 375, height: 812 },
	{ name: 'desktop', width: 1280, height: 900 },
];

const results = [];
let failures = 0;

const browser = await chromium.launch( {
	args: [ '--host-resolver-rules=MAP perego.local 127.0.0.1' ],
} );

/** Load one URL at one breakpoint and evaluate the hard rules for the expected language. */
async function checkUrl( url, expectLang, expectDir, bp, { is404 = false } = {} ) {
	const context = await browser.newContext( { viewport: { width: bp.width, height: bp.height } } );
	const page = await context.newPage();
	const jsErrors = [];
	const resourceErrors = [];
	page.on( 'console', ( msg ) => {
		if ( msg.type() !== 'error' ) {
			return;
		}
		( /Failed to load resource/i.test( msg.text() ) ? resourceErrors : jsErrors ).push( msg.text() );
	} );
	page.on( 'pageerror', ( err ) => jsErrors.push( String( err ) ) );

	const check = { url, expectLang, breakpoint: bp.name };
	try {
		const response = await page.goto( url, { waitUntil: 'networkidle', timeout: 20000 } );
		check.status = response ? response.status() : 0;
		const m = await page.evaluate( () => ( {
			scrollWidth: document.documentElement.scrollWidth,
			clientWidth: document.documentElement.clientWidth,
			h1: document.querySelectorAll( 'h1' ).length,
			lang: document.documentElement.lang,
			dir: document.documentElement.dir || 'ltr',
			arAlternate:
				document.querySelector( 'link[rel="alternate"][hreflang="ar"]' )?.getAttribute( 'href' ) || '',
		} ) );

		check.overflowPx = m.scrollWidth - m.clientWidth;
		check.h1 = m.h1;
		check.htmlLang = m.lang;
		check.htmlDir = m.dir;
		check.arAlternate = m.arAlternate;
		check.jsErrors = jsErrors;
		check.resourceErrors = check.status === 200 ? resourceErrors : [];

		const problems = [];
		const expectStatus = is404 ? check.status !== 404 : check.status !== 200;
		if ( expectStatus ) {
			problems.push( `expected ${ is404 ? 404 : 200 }, got ${ check.status }` );
		}
		if ( check.overflowPx > OVERFLOW_TOLERANCE ) {
			problems.push( `horizontal overflow ${ check.overflowPx }px` );
		}
		if ( jsErrors.length ) {
			problems.push( `${ jsErrors.length } JS error(s)` );
		}
		if ( check.resourceErrors.length ) {
			problems.push( `${ check.resourceErrors.length } broken resource(s)` );
		}
		if ( m.h1 !== 1 ) {
			problems.push( `${ m.h1 } <h1> (expected 1)` );
		}
		if ( ! m.lang.startsWith( expectLang ) ) {
			problems.push( `html lang "${ m.lang }" (expected ${ expectLang })` );
		}
		if ( m.dir !== expectDir ) {
			problems.push( `html dir "${ m.dir }" (expected ${ expectDir })` );
		}
		check.problems = problems;
		check.ok = problems.length === 0;
	} catch ( e ) {
		check.ok = false;
		check.problems = [ `navigation error: ${ e.message }` ];
	}
	await context.close();
	return check;
}

for ( const route of ROUTES ) {
	for ( const bp of BREAKPOINTS ) {
		// English.
		const en = await checkUrl( `${ BASE }${ route.path }`, 'en', 'ltr', bp, { is404: route.is404 } );
		record( en, `en ${ bp.name } ${ route.label }` );

		// Arabic: a 404 has no translation link — use the /ar/ prefix; otherwise follow the page's
		// own hreflang="ar" alternate (the real, possibly slug-de-duplicated, Polylang URL).
		if ( route.is404 ) {
			const ar = await checkUrl( `${ BASE }/ar${ route.path }`, 'ar', 'rtl', bp, { is404: true } );
			record( ar, `ar ${ bp.name } ${ route.label }` );
		} else if ( en.arAlternate ) {
			const ar = await checkUrl( en.arAlternate, 'ar', 'rtl', bp );
			record( ar, `ar ${ bp.name } ${ route.label }` );
		} else {
			const gap = { url: `${ BASE }${ route.path }`, breakpoint: bp.name, ok: true, arTranslation: 'missing' };
			results.push( gap );
			console.log( `[GAP ] ar ${ bp.name } ${ route.label } — no AR translation (content, not a defect)` );
		}
	}
}

function record( check, tag ) {
	if ( ! check.ok ) {
		failures++;
	}
	results.push( check );
	console.log(
		`[${ check.ok ? 'PASS' : 'FAIL' }] ${ tag } ${ check.url }` +
			( check.ok ? '' : ` — ${ check.problems.join( '; ' ) }` )
	);
}

await browser.close();

mkdirSync( 'output', { recursive: true } );
writeFileSync( 'output/verify-visual.json', JSON.stringify( { results, failures }, null, 2 ) );
console.log( `\n${ results.length } checks, ${ failures } failure(s). Evidence: output/verify-visual.json` );
process.exit( failures > 0 ? 1 : 0 );
