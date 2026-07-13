/**
 * Deterministic visual-recovery evidence for the locked static handoff and the live Perego site.
 *
 * Each state is a viewport capture after scrolling its named landmark into view. The runner freezes
 * presentation-only motion in both documents, waits for local fonts, writes baseline/current/diff
 * PNGs, and records metadata. A diff is evidence, not an acceptance decision.
 *
 * Run from the repository root after serving the handoff at http://127.0.0.1:8777:
 *   node sites/perego/perego-site/scripts/capture-visual-recovery.mjs
 */
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { chromium } from 'playwright';

const STATIC_BASE = process.env.PEREGO_HANDOFF_BASE ?? 'http://127.0.0.1:8777';
const LIVE_BASE = process.env.PEREGO_LIVE_BASE ?? 'http://perego.local';
const OUTPUT_ROOT = join('sites', 'perego', 'output', 'visual-recovery');
const VIEWPORTS = [
	{ id: '320', width: 320, height: 900 },
	{ id: '375', width: 375, height: 900 },
	{ id: '430', width: 430, height: 900 },
	{ id: '768', width: 768, height: 900 },
	{ id: '1024', width: 1024, height: 900 },
	{ id: '1280', width: 1280, height: 900 },
	{ id: '1440', width: 1440, height: 900 },
	{ id: 'wide', width: 1920, height: 1080 },
];
const requestedViewports = ( process.env.PEREGO_VIEWPORT_IDS ?? VIEWPORTS.map( ( viewport ) => viewport.id ).join( ',' ) )
	.split( ',' )
	.map( ( id ) => VIEWPORTS.find( ( viewport ) => viewport.id === id.trim() ) )
	.filter( Boolean );
const requestedLocales = ( process.env.PEREGO_LOCALES ?? 'en,ar' ).split( ',' ).map( ( locale ) => locale.trim() );

const CAPTURES = [
	{ route: 'home', state: 'hero-default', staticPath: 'index.html', livePath: '/', landmark: '#hero' },
	{ route: 'home', state: 'about', staticPath: 'index.html', livePath: '/', landmark: '#about' },
	{ route: 'home', state: 'services', staticPath: 'index.html', livePath: '/', landmark: '#services' },
	{ route: 'home', state: 'clients', staticPath: 'index.html', livePath: '/', landmark: '#clients' },
	{ route: 'home', state: 'footer', staticPath: 'index.html', livePath: '/', landmark: '.site-footer' },
	{ route: 'services', state: 'default', staticPath: 'services.html', livePath: '/services/', landmark: 'main' },
	{ route: 'service-video-editing', state: 'default', staticPath: 'service-video-editing.html', livePath: '/services/video-editing/', landmark: 'main' },
	{ route: 'service-motion-graphics', state: 'default', staticPath: 'service-motion-graphics.html', livePath: '/services/motion-graphics/', landmark: 'main' },
	{ route: 'service-graphic-design', state: 'default', staticPath: 'service-graphic-design.html', livePath: '/services/graphic-design/', landmark: 'main' },
	{ route: 'service-website-making', state: 'default', staticPath: 'service-website-making.html', livePath: '/services/website-making/', landmark: 'main' },
	{ route: 'work', state: 'default', staticPath: 'portfolio.html', livePath: '/work/', landmark: 'main' },
	{ route: 'project', state: 'default', staticPath: 'project.html', livePath: '/work/brand-film-launch-campaign/', landmark: 'main' },
	{ route: 'journal', state: 'default', staticPath: 'archive.html', livePath: '/journal/', landmark: 'main' },
	{ route: 'journal-post', state: 'default', staticPath: 'single-post.html', livePath: '/behind-the-scenes-of-a-brand-film-example/', landmark: 'main' },
	{ route: 'contact', state: 'default', staticPath: 'contact.html', livePath: '/contact/', landmark: 'main' },
	{ route: 'terms', state: 'default', staticPath: 'terms.html', livePath: '/terms/', landmark: 'main' },
	{ route: 'privacy', state: 'default', staticPath: 'privacy.html', livePath: '/privacy/', landmark: 'main' },
	{ route: 'search', state: 'populated', staticPath: 'search.html', livePath: '/?s=design', landmark: 'main' },
	{ route: 'not-found', state: 'default', staticPath: '404.html', livePath: '/visual-recovery-missing-route/', landmark: 'main' },
	{ route: 'page', state: 'default', staticPath: 'page.html', livePath: '/sample-page/', landmark: 'main' },
];

const freezeMotion = `
	*, *::before, *::after {
		animation: none !important;
		transition: none !important;
		scroll-behavior: auto !important;
	}
	.reveal, .js .reveal { opacity: 1 !important; transform: none !important; }
`;

async function capture(page, url, landmark, outputPath, locale) {
	await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
	await page.addStyleTag({ content: freezeMotion });
	if ( locale === 'ar' ) {
		await page.evaluate(() => {
			document.documentElement.lang = 'ar';
			document.documentElement.dir = 'rtl';
		});
	}
	await page.evaluate(async (selector) => {
		await document.fonts.ready;
		document.querySelector(selector)?.scrollIntoView({ block: 'start' });
	}, landmark);
	await page.waitForTimeout(150);
	await page.screenshot({ path: outputPath });
}

async function alternateLanguageUrl(page, englishUrl, locale) {
	await page.goto(englishUrl, { waitUntil: 'networkidle', timeout: 30000});
	const alternate = page.locator(`link[rel="alternate"][hreflang="${ locale }"]`);

	if (await alternate.count() === 0) {
		return null;
	}

	return alternate.first().getAttribute('href');
}

function pixelDifference(baselinePath, actualPath, diffPath) {
	const baseline = readFileSync(baselinePath).toString('base64');
	const actual = readFileSync(actualPath).toString('base64');

	return { baseline, actual, diffPath };
}

async function createDiff(page, images) {
	const diff = await page.evaluate(async ({ baseline, actual }) => {
		const load = (source) => new Promise((resolve, reject) => {
			const image = new Image();
			image.onload = () => resolve(image);
			image.onerror = reject;
			image.src = `data:image/png;base64,${source}`;
		});

		const [left, right] = await Promise.all([load(baseline), load(actual)]);
		if (left.width !== right.width || left.height !== right.height) {
			return { error: `dimension mismatch: ${left.width}x${left.height} vs ${right.width}x${right.height}` };
		}

		const source = document.createElement('canvas');
		source.width = left.width;
		source.height = left.height;
		const sourceContext = source.getContext('2d', { willReadFrequently: true });
		sourceContext.drawImage(left, 0, 0);
		const leftPixels = sourceContext.getImageData(0, 0, source.width, source.height);
		sourceContext.clearRect(0, 0, source.width, source.height);
		sourceContext.drawImage(right, 0, 0);
		const rightPixels = sourceContext.getImageData(0, 0, source.width, source.height);
		const output = sourceContext.createImageData(source.width, source.height);
		let changedPixels = 0;

		for (let offset = 0; offset < output.data.length; offset += 4) {
			const delta = Math.abs(leftPixels.data[offset] - rightPixels.data[offset])
				+ Math.abs(leftPixels.data[offset + 1] - rightPixels.data[offset + 1])
				+ Math.abs(leftPixels.data[offset + 2] - rightPixels.data[offset + 2]);
			if (delta > 0) {
				changedPixels++;
			}
			output.data[offset] = Math.min(255, delta * 2);
			output.data[offset + 1] = 0;
			output.data[offset + 2] = 0;
			output.data[offset + 3] = 255;
		}

		sourceContext.putImageData(output, 0, 0);
		return {
			changedPixels,
			totalPixels: source.width * source.height,
			png: source.toDataURL('image/png').split(',')[1],
		};
	}, images);

	if (diff.error) {
		return diff;
	}

	writeFileSync(images.diffPath, Buffer.from(diff.png, 'base64'));
	return { changedPixels: diff.changedPixels, totalPixels: diff.totalPixels };
}

mkdirSync(join(OUTPUT_ROOT, 'baseline'), { recursive: true });
mkdirSync(join(OUTPUT_ROOT, 'actual'), { recursive: true });
mkdirSync(join(OUTPUT_ROOT, 'diff'), { recursive: true });

const browser = await chromium.launch({
	args: ['--host-resolver-rules=MAP perego.local 127.0.0.1'],
});
const baselinePage = await browser.newPage();
const actualPage = await browser.newPage();
const diffPage = await browser.newPage();
const records = [];

try {
	for (const viewport of requestedViewports) {
		await baselinePage.setViewportSize(viewport);
		await actualPage.setViewportSize(viewport);

		for (const captureState of CAPTURES) {
			const staticUrl = `${STATIC_BASE}/${captureState.staticPath}`;
			const englishUrl = `${LIVE_BASE}${captureState.livePath}`;

			for (const locale of requestedLocales) {
				const liveUrl = locale === 'en'
					? englishUrl
					: await alternateLanguageUrl(actualPage, englishUrl, locale);

				if ( ! liveUrl ) {
					records.push({
						route: captureState.route,
						language: locale,
						viewport,
						state: captureState.state,
						status: 'unavailable',
						reason: `No hreflang ${locale} alternate is published by the live route.`,
					});
					continue;
				}

				const filename = `${captureState.route}-${locale}-${viewport.id}-${captureState.state}.png`;
				const baselinePath = join(OUTPUT_ROOT, 'baseline', filename);
				const actualPath = join(OUTPUT_ROOT, 'actual', filename);
				const diffPath = join(OUTPUT_ROOT, 'diff', filename);

				await capture(baselinePage, staticUrl, captureState.landmark, baselinePath, locale);
				await capture(actualPage, liveUrl, captureState.landmark, actualPath, locale);
				const difference = await createDiff(diffPage, pixelDifference(baselinePath, actualPath, diffPath));
				records.push({
					route: captureState.route,
					language: locale,
					viewport,
					state: captureState.state,
					baseline: baselinePath.replaceAll('\\', '/'),
					actual: actualPath.replaceAll('\\', '/'),
					diff: difference.error ? null : diffPath.replaceAll('\\', '/'),
					baselineKind: locale === 'en' ? 'locked-handoff' : 'rtl-layout-surrogate',
					status: 'unreviewed',
					...difference,
				});
			}
		}
	}
} finally {
	await browser.close();
}

writeFileSync(join(OUTPUT_ROOT, 'manifest.json'), JSON.stringify({ records }, null, 2));
console.log(JSON.stringify({ records }, null, 2));
