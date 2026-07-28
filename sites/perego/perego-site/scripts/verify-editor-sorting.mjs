/**
 * Verifies canvas drag-sorting inside the REAL block editor (spec 023; owner 2026-07-28).
 *
 * WHY A HARNESS AND NOT A UNIT TEST. Everything below the editor is unit-tested — markup parity,
 * ordering arithmetic, crop resolution, the PHP read path. What no unit test can reach is whether
 * pointer events survive the editor's own drag machinery: `useBlockProps` puts `draggable="true"` on
 * the block wrapper, the editor installs document-level `dragover` handling, and the canvas is an
 * iframe while the drag chip is not. jsdom models none of that.
 *
 * It has already earned its place. It caught a real bug the unit tests could not: the Move earlier /
 * Move later buttons live inside the card, so their `pointerdown` bubbled to the sort handler whose
 * `preventDefault()` — the thing that stops a native drag ever starting — also suppressed the button's
 * own `click`. Dragging worked; the single-pointer alternative WCAG 2.2 2.5.7 requires silently did
 * not. jsdom dispatches `click` directly and never reproduces it.
 *
 * Auth is a session generated through WP-CLI, so no password is handled and nothing is typed into a
 * login form. `--url` is load-bearing: COOKIEHASH is md5 of the *resolved* site URL, and this install
 * defines WP_SITEURL per host, so a cookie minted without it is named for a URL the browser never
 * sends.
 *
 * Usage (from the repo root, with WAMP up):
 *   node sites/perego/perego-site/scripts/verify-editor-sorting.mjs
 *
 * Cleans up after itself: the Site Editor save it performs creates a `wp_template` override that would
 * otherwise shadow the theme file, and that override is deleted before exit.
 */
import { execFileSync } from 'node:child_process';
import { mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { chromium } from 'playwright';

const SITE = process.env.PEREGO_SITE_URL || 'http://perego.local';
const HOST = new URL( SITE ).hostname;
const TEMPLATE = 'perego-theme//front-page';

let failures = 0;
const ok = ( label, pass, detail = '' ) => {
	if ( ! pass ) failures++;
	console.log( `${ pass ? '[PASS]' : '[FAIL]' } ${ label }${ detail ? ` — ${ detail }` : '' }` );
};

/**
 * Run PHP through WP-CLI.
 *
 * Via a temp file rather than `wp eval "<code>"`: multi-line PHP containing quotes cannot survive being
 * re-split by the shell, which `execFileSync(..., { shell: true })` requires on Windows where `wp` is a
 * batch script.
 */
const workDir = mkdtempSync( join( tmpdir(), 'perego-verify-' ) );
const wp = ( php ) => {
	const file = join( workDir, 'run.php' );
	writeFileSync( file, `<?php\n${ php }\n` );

	return execFileSync(
		'wp',
		[ 'eval-file', file, `--url=${ SITE }`, '--path=wp' ],
		{ encoding: 'utf8', shell: true }
	).trim();
};

/** A real session, minted server-side. No password leaves or enters this process. */
function login() {
	const raw = wp( `
		$u = get_users( [ "role" => "administrator", "number" => 1 ] );
		if ( ! $u ) { echo "[]"; return; }
		$exp = time() + 3600;
		$token = WP_Session_Tokens::get_instance( $u[0]->ID )->create( $exp );
		echo wp_json_encode( [
			[ "name" => LOGGED_IN_COOKIE, "value" => wp_generate_auth_cookie( $u[0]->ID, $exp, "logged_in", $token ), "domain" => "${ HOST }", "path" => "/" ],
			[ "name" => AUTH_COOKIE, "value" => wp_generate_auth_cookie( $u[0]->ID, $exp, "auth", $token ), "domain" => "${ HOST }", "path" => "/" ],
		] );
	` );

	return JSON.parse( raw.slice( raw.indexOf( '[' ) ) );
}

const cookies = login();
if ( cookies.length === 0 ) {
	console.error( 'No administrator found.' );
	process.exit( 1 );
}

const browser = await chromium.launch();
const context = await browser.newContext( { viewport: { width: 1600, height: 1000 } } );
await context.addCookies( cookies );
const page = await context.newPage();
const errors = [];
page.on( 'pageerror', ( error ) => errors.push( error.message ) );

await page.goto(
	`${ SITE }/wp-admin/site-editor.php?postType=wp_template&postId=${ encodeURIComponent( TEMPLATE ) }&canvas=edit`,
	{ waitUntil: 'domcontentloaded' }
);
await page.waitForTimeout( 11000 );

const canvas = page.frameLocator( 'iframe[name="editor-canvas"]' );
const cards = canvas.locator( '#portfolioGrid .perego-sortable' );

await cards.first().waitFor( { timeout: 45000 } ).catch( () => {} );
const total = await cards.count();

ok( 'the grid renders as a live canvas, not a server-rendered preview', total > 1, `${ total } cards` );

if ( total > 1 ) {
	const title = async ( i ) => ( await cards.nth( i ).locator( '.post-card__title' ).textContent() )?.trim();
	const lead = await title( 0 );

	await cards.nth( 0 ).click();
	await page.waitForTimeout( 1200 );

	const controls = cards.nth( 0 ).locator( '.perego-sortable__controls button' );
	ok( 'every card offers the non-drag Move controls (WCAG 2.2 2.5.7)', ( await controls.count() ) === 2 );

	// The regression this harness exists for: the buttons must not be swallowed by the sort handler.
	await controls.nth( 1 ).click( { force: true } );
	await page.waitForTimeout( 1000 );
	ok( 'the Move later button reorders the grid', ( await title( 1 ) ) === lead, `lead is now at position 2` );

	// And the drag itself, which the editor's own machinery could otherwise claim.
	const from = await cards.nth( 1 ).boundingBox();
	const to = await cards.nth( 3 ).boundingBox();
	await page.mouse.move( from.x + from.width / 2, from.y + from.height / 2 );
	await page.mouse.down();
	await page.mouse.move( from.x + from.width / 2 + 20, from.y + from.height / 2 + 20, { steps: 5 } );
	await page.mouse.move( to.x + to.width / 2, to.y + to.height / 2, { steps: 15 } );
	await page.waitForTimeout( 400 );
	await page.mouse.up();
	await page.waitForTimeout( 1500 );

	ok( 'a pointer drag moves the card to the drop position', ( await title( 3 ) ) === lead, 'lead is now at position 4' );
	ok( 'the editor did not hijack the gesture into moving the block', ( await canvas.locator( '.perego-portfolio-grid__editor' ).count() ) === 1 );
}

ok( 'no page errors', errors.length === 0, errors.slice( 0, 2 ).join( ' | ' ) );

await browser.close();

/*
 * Selecting a block in the Site Editor is enough for WordPress to consider the template dirty, and any
 * saved `wp_template` row shadows the theme file from then on — the same hazard the Selected-work
 * migration warns about. Remove anything this run created.
 */
const removed = wp( `
	$rows = get_posts( [ "post_type" => "wp_template", "post_status" => "any", "numberposts" => 50, "name" => "front-page" ] );
	foreach ( $rows as $row ) { wp_delete_post( $row->ID, true ); }
	echo count( $rows );
` );
console.log( `\nCleanup: removed ${ removed } front-page template override(s); the theme file is authoritative again.` );
rmSync( workDir, { recursive: true, force: true } );

process.exit( failures > 0 ? 1 : 0 );
