/**
 * Live interaction evidence for the locked handoff header contract. It intentionally uses the
 * handoff selectors, records JSON under the Perego output tree, and exits non-zero on failure.
 */
import { chromium } from 'playwright';
import { mkdirSync, writeFileSync } from 'node:fs';

const BASE = 'http://perego.local';
const OUTPUT = 'sites/perego/output/verify-interactions.json';
const results = [];
let failures = 0;

function record(name, ok, detail = '') {
	results.push({ name, ok, detail });
	if (!ok) failures++;
	console.log(`[${ok ? 'PASS' : 'FAIL'}] ${name}${ok || !detail ? '' : ` — ${detail}`}`);
}

const browser = await chromium.launch({
	args: ['--host-resolver-rules=MAP perego.local 127.0.0.1'],
});

// Sticky/scrolled desktop header and desktop Services hover menu.
{
	const context = await browser.newContext({ viewport: { width: 1280, height: 800 } });
	const page = await context.newPage();
	await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
	const before = await page.locator('.site-header').evaluate((header) => header.classList.contains('is-scrolled'));
	await page.evaluate(() => window.scrollTo(0, 400));
	await page.waitForTimeout(250);
	const after = await page.locator('.site-header').evaluate((header) => header.classList.contains('is-scrolled'));
	record('sticky header: handoff is-scrolled state toggles', before === false && after === true, `before=${before} after=${after}`);

	const services = page.locator('.has-dropdown').first();
	await services.hover();
	await page.waitForTimeout(250);
	const dropdown = await services.locator('.dropdown').evaluate((element) => {
		const style = getComputedStyle(element);
		return { opacity: style.opacity, visibility: style.visibility };
	});
	record('desktop Services: hover opens handoff dropdown', dropdown.opacity === '1' && dropdown.visibility === 'visible', JSON.stringify(dropdown));
	await context.close();
}

// Mobile panel, Services accordion, Escape close, and focus return.
{
	const context = await browser.newContext({ viewport: { width: 375, height: 812 } });
	const page = await context.newPage();
	await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
	const toggle = page.locator('#navToggle');
	await toggle.click();
	await page.waitForTimeout(200);
	const opened = await page.evaluate(() => ({
		expanded: document.querySelector('#navToggle')?.getAttribute('aria-expanded'),
		navOpen: document.querySelector('#mainNav')?.classList.contains('is-open'),
		headerOpen: document.querySelector('.site-header')?.classList.contains('is-menu-open'),
		bodyLocked: document.body.style.overflow === 'hidden',
	}));
	record('mobile nav: hamburger opens panel and locks scroll', opened.expanded === 'true' && opened.navOpen === true && opened.headerOpen === true && opened.bodyLocked, JSON.stringify(opened));

	const servicesLink = page.locator('.has-dropdown > .main-nav__link').first();
	await servicesLink.click();
	await page.waitForTimeout(100);
	const accordion = await page.evaluate(() => ({
		open: document.querySelector('.has-dropdown')?.classList.contains('is-open'),
		expanded: document.querySelector('.has-dropdown > .main-nav__link')?.getAttribute('aria-expanded'),
	}));
	record('mobile Services: tap opens the accordion', accordion.open === true && accordion.expanded === 'true', JSON.stringify(accordion));

	await page.keyboard.press('Escape');
	await page.waitForTimeout(150);
	const closed = await page.evaluate(() => ({
		expanded: document.querySelector('#navToggle')?.getAttribute('aria-expanded'),
		navOpen: document.querySelector('#mainNav')?.classList.contains('is-open'),
		headerOpen: document.querySelector('.site-header')?.classList.contains('is-menu-open'),
		bodyLocked: document.body.style.overflow === 'hidden',
		focusOnToggle: document.activeElement === document.querySelector('#navToggle'),
	}));
	record('mobile nav: Escape closes and restores focus', closed.expanded === 'false' && closed.navOpen === false && closed.headerOpen === false && !closed.bodyLocked && closed.focusOnToggle, JSON.stringify(closed));
	await context.close();
}

// Language switching is URL-managed, not a local-storage-only visual toggle.
{
	const context = await browser.newContext({ viewport: { width: 1280, height: 800 } });
	const page = await context.newPage();
	await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
	const href = await page.locator('.lang-toggle a[data-locale="ar"]').getAttribute('href');
	record('language switch: AR control is a real locale URL', /\/ar\//.test(href ?? ''), `href=${href ?? ''}`);
	await context.close();
}

// The project gallery is server-rendered from editor-owned attachment metadata and opens an accessible lightbox.
{
	const context = await browser.newContext({ viewport: { width: 1280, height: 800 } });
	const page = await context.newPage();
	await page.goto(`${BASE}/work/brand-film-launch-campaign/`, { waitUntil: 'networkidle' });
	const thumbs = page.locator('.project-gallery .work-card');
	const count = await thumbs.count();
	record('project gallery: representative project renders seeded thumbnails', count >= 3, `count=${count}`);

	if (count > 0) {
		await thumbs.first().click();
		await page.waitForTimeout(100);
		const opened = await page.locator('.lightbox').evaluate((dialog) => ({
			hidden: dialog.hidden,
			modal: dialog.getAttribute('aria-modal'),
			bodyLocked: document.body.style.overflow === 'hidden',
		}));
		record('project lightbox: opens as a modal and locks scroll', opened.hidden === false && opened.modal === 'true' && opened.bodyLocked, JSON.stringify(opened));

		await page.keyboard.press('Escape');
		await page.waitForTimeout(100);
		const closed = await page.locator('.lightbox').evaluate((dialog) => ({
			hidden: dialog.hidden,
			bodyLocked: document.body.style.overflow === 'hidden',
			focusOnThumb: document.activeElement === document.querySelector('.project-gallery .work-card'),
		}));
		record('project lightbox: Escape closes and restores focus', closed.hidden === true && !closed.bodyLocked && closed.focusOnThumb, JSON.stringify(closed));
	}
	await context.close();
}

await browser.close();
mkdirSync('sites/perego/output', { recursive: true });
writeFileSync(OUTPUT, JSON.stringify({ results, failures }, null, 2));
console.log(`\n${results.length} interaction checks, ${failures} failure(s). Evidence: ${OUTPUT}`);
process.exit(failures > 0 ? 1 : 0);
