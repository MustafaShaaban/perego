/**
 * Visual-acceptance capture (spec 004 T008/T009). For each mapped route, screenshots BOTH:
 *   - the deterministic handoff baseline — the static handoff page rendered from file:// at the
 *     target viewport (the locked visual reference), and
 *   - the matching live WordPress render (EN and the real Polylang AR URL).
 *
 * Motion is frozen (reducedMotion: 'reduce') and the preloader is dismissed so captures are
 * repeatable. Output PNGs go under the gitignored output/ tree (baselines/ + live/) — regenerable
 * evidence, never committed. Reaches the WAMP vhost via host-resolver-rules (perego.local → 127.0.0.1).
 *
 * Scope is intentionally per-slice: start with the route(s) under acceptance now and add rows as each
 * route family reaches its slice. Run from perego-site/:
 *   node scripts/capture-baselines.mjs
 */
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { dirname, resolve } from 'node:path';

const here = dirname( fileURLToPath( import.meta.url ) );
const HANDOFF_ROOT = resolve( here, '../../../../_design_handoff/Perego-Creative-Studio-Final-Handoff/site' );
const BASE = 'http://perego.local';

const BREAKPOINTS = [
	{ name: 'mobile', width: 375, height: 812 },
	{ name: 'desktop', width: 1280, height: 900 },
];

// One row per route currently under visual acceptance.
const ROUTES = [
	{
		label: 'home',
		handoffFile: 'index.html',
		liveEN: `${ BASE }/`,
		liveAR: `${ BASE }/ar/%d8%a7%d9%84%d8%b1%d8%a6%d9%8a%d8%b3%d9%8a%d8%a9/`,
	},
	{
		label: 'services-archive',
		handoffFile: 'services.html',
		liveEN: `${ BASE }/services/`,
		liveAR: `${ BASE }/ar/services/`,
	},
	{
		label: 'single-service-video-editing',
		handoffFile: 'service-video-editing.html',
		liveEN: `${ BASE }/services/video-editing/`,
		liveAR: `${ BASE }/ar/services/video-editing-2/`,
	},
	{
		label: 'work-archive',
		handoffFile: 'portfolio.html',
		liveEN: `${ BASE }/work/`,
		liveAR: `${ BASE }/ar/work/`,
	},
	{
		label: 'single-project',
		handoffFile: 'project.html',
		liveEN: `${ BASE }/work/landing-page-microsite/`,
		liveAR: `${ BASE }/ar/work/%D8%B5%D9%81%D8%AD%D8%A9-%D9%87%D8%A8%D9%88%D8%B7-%D9%88%D9%85%D9%88%D9%82%D8%B9-%D9%85%D8%B5%D8%BA%D9%91%D8%B1/`,
	},
];

const browser = await chromium.launch( {
	args: [ '--host-resolver-rules=MAP perego.local 127.0.0.1' ],
} );

/** Screenshot one URL (live or file://) full-page at one breakpoint, motion frozen. */
async function shoot( url, outPath, bp ) {
	const context = await browser.newContext( {
		viewport: { width: bp.width, height: bp.height },
		reducedMotion: 'reduce',
	} );
	const page = await context.newPage();
	// The live site session-gates the preloader on sessionStorage; pre-seed it so the capture is not
	// covered by the intro overlay. Harmless on the static handoff (which has no such key).
	await context.addInitScript( () => {
		try {
			window.sessionStorage.setItem( 'perego-preloader-seen', '1' );
		} catch ( e ) {}
	} );
	await page.goto( url, { waitUntil: 'networkidle', timeout: 30000 } );
	await page.screenshot( { path: outPath, fullPage: true } );
	await context.close();
}

mkdirSync( 'output/baselines', { recursive: true } );
mkdirSync( 'output/live', { recursive: true } );

for ( const route of ROUTES ) {
	const baseUrl = pathToFileURL( resolve( HANDOFF_ROOT, route.handoffFile ) ).href;
	for ( const bp of BREAKPOINTS ) {
		await shoot( baseUrl, `output/baselines/${ route.label }-${ bp.name }.png`, bp );
		console.log( `[baseline] ${ route.label } ${ bp.name }` );
		await shoot( route.liveEN, `output/live/${ route.label }-en-${ bp.name }.png`, bp );
		console.log( `[live en ] ${ route.label } ${ bp.name }` );
		await shoot( route.liveAR, `output/live/${ route.label }-ar-${ bp.name }.png`, bp );
		console.log( `[live ar ] ${ route.label } ${ bp.name }` );
	}
}

await browser.close();
console.log( '\nCaptures written under output/baselines/ and output/live/.' );
