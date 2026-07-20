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

// The project gallery is server-rendered from editor-owned attachment metadata; its thumbs open the
// ONE site-wide media lightbox (spec 020 round 6 — the block's own embedded dialog was removed per
// the handoff's single-GlobalMediaLightbox contract).
{
	const context = await browser.newContext({ viewport: { width: 1280, height: 800 } });
	const page = await context.newPage();
	await page.goto(`${BASE}/work/brand-film-launch-campaign/`, { waitUntil: 'networkidle' });
	const thumbs = page.locator('.project-gallery .work-card');
	const count = await thumbs.count();
	record('project gallery: representative project renders seeded thumbnails', count >= 3, `count=${count}`);

	const dialogCount = await page.locator('.lightbox').count();
	record('project page: exactly one lightbox dialog instance exists', dialogCount === 1, `dialogs=${dialogCount}`);

	if (count > 0) {
		await thumbs.first().click();
		await page.waitForTimeout(100);
		const opened = await page.locator('#perego-media-lightbox').evaluate((dialog) => {
			const style = getComputedStyle(dialog);
			return {
				hidden: dialog.hidden,
				modal: dialog.getAttribute('aria-modal'),
				bodyLocked: document.body.style.overflow === 'hidden',
				gallery: dialog.classList.contains('is-gallery'),
				// DOM-level "open" (hidden removed) is not the same as visually open — assert the
				// computed style so a dialog "open" in the DOM but invisible on screen fails.
				display: style.display,
				visibility: style.visibility,
			};
		});
		record(
			'project lightbox: opens the site-wide dialog as a visible gallery modal and locks scroll',
			opened.hidden === false && opened.modal === 'true' && opened.bodyLocked && opened.gallery && opened.display !== 'none' && opened.visibility === 'visible',
			JSON.stringify(opened)
		);

		await page.keyboard.press('Escape');
		await page.waitForTimeout(100);
		const closed = await page.locator('#perego-media-lightbox').evaluate((dialog) => ({
			hidden: dialog.hidden,
			bodyLocked: document.body.style.overflow === 'hidden',
			focusOnThumb: document.activeElement === document.querySelector('.project-gallery .work-card'),
		}));
		record('project lightbox: Escape closes and restores focus', closed.hidden === true && !closed.bodyLocked && closed.focusOnThumb, JSON.stringify(closed));
	}
	await context.close();
}

// The site-wide media lightbox (perego-theme/media-lightbox) — Services archive's real "Selected
// work" project cards open it via a plain delegated click listener, not the Interactivity API.
{
	const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
	const page = await context.newPage();
	await page.goto(`${BASE}/services/`, { waitUntil: 'networkidle' });
	const cards = page.locator('.work-card[data-gallery], .work-card[data-image]');
	const count = await cards.count();
	record('media lightbox: Services archive renders real selected-work cards', count > 0, `count=${count}`);

	if (count > 0) {
		await cards.first().click();
		await page.waitForTimeout(150);
		const opened = await page.evaluate(() => {
			const dialog = document.getElementById('perego-media-lightbox');
			const style = getComputedStyle(dialog);
			return {
				hidden: dialog.hidden,
				modal: dialog.getAttribute('aria-modal'),
				opacity: style.opacity,
				visibility: style.visibility,
				bodyLocked: document.body.style.overflow === 'hidden',
				mainInert: document.querySelector('.wp-site-blocks')?.hasAttribute('inert'),
			};
		});
		record(
			'media lightbox: opens as a visible modal, locks scroll, inerts the background',
			opened.hidden === false && opened.modal === 'true' && opened.opacity === '1' && opened.visibility === 'visible' && opened.bodyLocked && opened.mainInert === true,
			JSON.stringify(opened)
		);

		await page.keyboard.press('Escape');
		await page.waitForTimeout(150);
		const closed = await page.evaluate(() => ({
			hidden: document.getElementById('perego-media-lightbox').hidden,
			bodyLocked: document.body.style.overflow === 'hidden',
			mainInert: document.querySelector('.wp-site-blocks')?.hasAttribute('inert'),
		}));
		record('media lightbox: Escape closes, unlocks scroll, and un-inerts the background', closed.hidden === true && !closed.bodyLocked && closed.mainInert === false, JSON.stringify(closed));
	}
	await context.close();
}

await browser.close();
mkdirSync('sites/perego/output', { recursive: true });
writeFileSync(OUTPUT, JSON.stringify({ results, failures }, null, 2));
console.log(`\n${results.length} interaction checks, ${failures} failure(s). Evidence: ${OUTPUT}`);
process.exit(failures > 0 ? 1 : 0);
