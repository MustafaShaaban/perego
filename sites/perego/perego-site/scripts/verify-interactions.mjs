/**
 * Interaction-state verification (spec Phase 12). Drives the live header's Interactivity-API behaviour
 * in headless Chromium and asserts the handoff's documented states:
 *   - sticky header: scrolling past the threshold adds `is-scrolled` to `.perego-header`
 *   - mobile nav (≤1024): the hamburger opens the panel (aria-expanded=true, body scroll locked);
 *     Escape closes it and returns focus to the hamburger
 *   - desktop language switch: the AR pill is a real anchor to an `/ar/...` URL
 *
 * Reaches the WAMP vhost via Chromium host-resolver-rules (perego.local → 127.0.0.1). Writes JSON
 * evidence to output/verify-interactions.json and exits non-zero on any failure.
 * Run: node sites/perego/perego-site/scripts/verify-interactions.mjs
 */
import { chromium } from 'playwright';
import { writeFileSync, mkdirSync } from 'node:fs';

const BASE = 'http://perego.local';
const results = [];
let failures = 0;

function record( name, ok, detail = '' ) {
	results.push( { name, ok, detail } );
	if ( ! ok ) {
		failures++;
	}
	console.log( `[${ ok ? 'PASS' : 'FAIL' }] ${ name }${ ok || ! detail ? '' : ` — ${ detail }` }` );
}

const browser = await chromium.launch( {
	args: [ '--host-resolver-rules=MAP perego.local 127.0.0.1' ],
} );

// 1) Sticky header on scroll (desktop).
{
	const context = await browser.newContext( { viewport: { width: 1280, height: 800 } } );
	const page = await context.newPage();
	await page.goto( `${ BASE }/`, { waitUntil: 'networkidle' } );
	const before = await page.evaluate( () => document.querySelector( '.perego-header' )?.classList.contains( 'is-scrolled' ) );
	await page.evaluate( () => window.scrollTo( 0, 400 ) );
	await page.waitForTimeout( 250 );
	const after = await page.evaluate( () => document.querySelector( '.perego-header' )?.classList.contains( 'is-scrolled' ) );
	record( 'sticky header: is-scrolled toggles on scroll', before === false && after === true, `before=${ before } after=${ after }` );
	await context.close();
}

// 2) Mobile nav open/close + focus restore (mobile).
{
	const context = await browser.newContext( { viewport: { width: 375, height: 812 } } );
	const page = await context.newPage();
	await page.goto( `${ BASE }/`, { waitUntil: 'networkidle' } );
	const hamburger = page.locator( '.perego-header__hamburger' );
	await hamburger.click();
	await page.waitForTimeout( 200 );
	const opened = await page.evaluate( () => ( {
		expanded: document.querySelector( '.perego-header__hamburger' )?.getAttribute( 'aria-expanded' ),
		menuOpen: document.querySelector( '.perego-header' )?.classList.contains( 'is-menu-open' ),
		bodyLocked: document.body.style.overflow === 'hidden',
	} ) );
	record(
		'mobile nav: hamburger opens the panel + locks scroll',
		opened.expanded === 'true' && opened.menuOpen === true && opened.bodyLocked,
		JSON.stringify( opened )
	);

	await page.keyboard.press( 'Escape' );
	await page.waitForTimeout( 200 );
	const closed = await page.evaluate( () => ( {
		menuOpen: document.querySelector( '.perego-header' )?.classList.contains( 'is-menu-open' ),
		bodyLocked: document.body.style.overflow === 'hidden',
		focusOnHamburger: document.activeElement === document.querySelector( '.perego-header__hamburger' ),
	} ) );
	record(
		'mobile nav: Escape closes + restores focus to the hamburger',
		closed.menuOpen === false && ! closed.bodyLocked && closed.focusOnHamburger,
		JSON.stringify( closed )
	);
	await context.close();
}

// 3) Language switch is a real anchor to an /ar/ URL (desktop).
{
	const context = await browser.newContext( { viewport: { width: 1280, height: 800 } } );
	const page = await context.newPage();
	await page.goto( `${ BASE }/`, { waitUntil: 'networkidle' } );
	const href = await page.evaluate( () =>
		document.querySelector( '.perego-language-toggle a[data-locale="ar"]' )?.getAttribute( 'href' ) || ''
	);
	record( 'language switch: AR pill is a real /ar anchor', /\/ar\//.test( href ), `href=${ href }` );
	await context.close();
}

await browser.close();

mkdirSync( 'output', { recursive: true } );
writeFileSync( 'output/verify-interactions.json', JSON.stringify( { results, failures }, null, 2 ) );
console.log( `\n${ results.length } interaction checks, ${ failures } failure(s). Evidence: output/verify-interactions.json` );
process.exit( failures > 0 ? 1 : 0 );
