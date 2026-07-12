/**
 * Accessibility verification pass (spec Phase 11 — WCAG 2.2 AA). Loads each primary route in headless
 * Chromium, injects axe-core (already present as a dev dependency), and runs an audit against the
 * WCAG 2.0/2.1/2.2 A + AA rule tags. Serious/critical violations are hard failures (non-zero exit);
 * minor/moderate are reported as warnings. Writes JSON evidence to output/verify-a11y.json.
 *
 * Reaches the WAMP vhost via Chromium host-resolver-rules (perego.local → 127.0.0.1).
 * Run: node sites/perego/perego-site/scripts/verify-a11y.mjs
 */
import { chromium } from 'playwright';
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { createRequire } from 'node:module';

const require = createRequire( import.meta.url );
const axeSource = readFileSync( require.resolve( 'axe-core' ), 'utf8' );

const BASE = 'http://perego.local';
const TAGS = [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa' ];
const HARD_IMPACTS = [ 'serious', 'critical' ];

const URLS = [
	{ url: `${ BASE }/`, label: 'home (en)' },
	{ url: `${ BASE }/services/`, label: 'services archive (en)' },
	{ url: `${ BASE }/work/`, label: 'work archive (en)' },
	{ url: `${ BASE }/journal/`, label: 'journal (en)' },
	{ url: `${ BASE }/contact/`, label: 'contact (en)' },
	{ url: `${ BASE }/ar/`, label: 'home (ar)' },
	{ url: `${ BASE }/ar/contact-2/`, label: 'contact (ar)' },
];

const browser = await chromium.launch( {
	args: [ '--host-resolver-rules=MAP perego.local 127.0.0.1' ],
} );
const results = [];
let hardFailures = 0;

for ( const { url, label } of URLS ) {
	const context = await browser.newContext( { viewport: { width: 1280, height: 900 } } );
	const page = await context.newPage();
	const entry = { url, label };
	try {
		await page.goto( url, { waitUntil: 'networkidle', timeout: 20000 } );
		await page.addScriptTag( { content: axeSource } );
		const report = await page.evaluate( async ( tags ) => {
			/* global axe, document */
			const r = await axe.run( document, { runOnly: { type: 'tag', values: tags } } );
			return r.violations.map( ( v ) => ( {
				id: v.id,
				impact: v.impact,
				help: v.help,
				nodes: v.nodes.length,
				sample: v.nodes[ 0 ]?.target?.join( ' ' ) || '',
			} ) );
		}, TAGS );

		entry.violations = report;
		const hard = report.filter( ( v ) => HARD_IMPACTS.includes( v.impact ) );
		entry.hard = hard.length;
		if ( hard.length ) {
			hardFailures += hard.length;
		}
		const tag = hard.length ? 'FAIL' : ( report.length ? 'WARN' : 'PASS' );
		console.log( `[${ tag }] ${ label } — ${ report.length } violation type(s), ${ hard.length } serious/critical` );
		for ( const v of report ) {
			console.log( `        ${ v.impact }: ${ v.id } (${ v.nodes }×) — ${ v.help } [${ v.sample }]` );
		}
	} catch ( e ) {
		entry.error = e.message;
		hardFailures++;
		console.log( `[FAIL] ${ label } — ${ e.message }` );
	}
	results.push( entry );
	await context.close();
}

await browser.close();

mkdirSync( 'output', { recursive: true } );
writeFileSync( 'output/verify-a11y.json', JSON.stringify( { results, hardFailures }, null, 2 ) );
console.log( `\n${ results.length } pages audited, ${ hardFailures } serious/critical violation(s). Evidence: output/verify-a11y.json` );
process.exit( hardFailures > 0 ? 1 : 0 );
