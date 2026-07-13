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
const VIEWPORT = { width: 1440, height: 900 };

const STATES = [
	{ name: 'hero-default', landmark: '#hero' },
	{ name: 'about', landmark: '#about' },
	{ name: 'services', landmark: '#services' },
	{ name: 'clients', landmark: '#clients' },
	{ name: 'footer', landmark: '.site-footer' },
];

const freezeMotion = `
	*, *::before, *::after {
		animation: none !important;
		transition: none !important;
		scroll-behavior: auto !important;
	}
	.reveal, .js .reveal { opacity: 1 !important; transform: none !important; }
`;

async function capture(page, url, landmark, outputPath) {
	await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
	await page.addStyleTag({ content: freezeMotion });
	await page.evaluate(async (selector) => {
		await document.fonts.ready;
		document.querySelector(selector)?.scrollIntoView({ block: 'start' });
	}, landmark);
	await page.waitForTimeout(150);
	await page.screenshot({ path: outputPath });
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
const baselinePage = await browser.newPage({ viewport: VIEWPORT });
const actualPage = await browser.newPage({ viewport: VIEWPORT });
const diffPage = await browser.newPage();
const records = [];

try {
	for (const state of STATES) {
		const filename = `home-en-1440-${state.name}.png`;
		const baselinePath = join(OUTPUT_ROOT, 'baseline', filename);
		const actualPath = join(OUTPUT_ROOT, 'actual', filename);
		const diffPath = join(OUTPUT_ROOT, 'diff', filename);

		await capture(baselinePage, `${STATIC_BASE}/index.html`, state.landmark, baselinePath);
		await capture(actualPage, `${LIVE_BASE}/`, state.landmark, actualPath);
		const difference = await createDiff(diffPage, pixelDifference(baselinePath, actualPath, diffPath));
		records.push({
			route: 'home',
			language: 'en',
			viewport: VIEWPORT,
			state: state.name,
			baseline: baselinePath.replaceAll('\\', '/'),
			actual: actualPath.replaceAll('\\', '/'),
			diff: difference.error ? null : diffPath.replaceAll('\\', '/'),
			status: 'unreviewed',
			...difference,
		});
	}
} finally {
	await browser.close();
}

writeFileSync(join(OUTPUT_ROOT, 'home-en-1440-manifest.json'), JSON.stringify({ records }, null, 2));
console.log(JSON.stringify({ records }, null, 2));
