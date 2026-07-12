# Home — visual-acceptance evidence (US1 / T009)

**Reference**: `_design_handoff/Perego-Creative-Studio-Final-Handoff/site/index.html`
**Live**: EN `http://perego.local/` · AR `http://perego.local/ar/الرئيسية/`
**Captures** (gitignored, regenerate with `node scripts/capture-baselines.mjs`):
`output/baselines/home-{mobile,desktop}.png`, `output/live/home-{en,ar}-{mobile,desktop}.png`.
**Breakpoints**: mobile 375, desktop 1280. **Status**: In review — 10 of 13 rows resolved; 3 rows remain
(2 are content gaps, not visual-system bugs; 1 is a larger layout rebuild).

## Material differences (baseline vs live)

Severity: **P1** = breaks the approved visual system; **P2** = notable but localized; **C** = content/asset
(owner or seed material). Each row is closed by a T010/T011 change or an explicit tracked blocker.

| # | Section | Handoff (reference) | Disposition |
| --- | --- | --- | --- |
| 1 | Hero bg | Dramatic geometric purple artwork with light rays (`hero-bg.png`) | **Resolved** — image wired via `HeroSliderRenderer` + drift/pulse animation, reduced-motion gated |
| 2 | About image | Hooded-figure image + "بيريجو" watermark (`about-hooded.png`) | **Resolved** — new `perego-theme/home-about` block renders the image + gradient overlay |
| 3 | Service cards | Each staggered card contains a real app-screenshot (`card-*.png`) | **Resolved** — real `<img>` per card via `HomeContent::services()['image']` |
| 4 | Clients bg | Section on the dark site gradient | **Resolved** — `.clients` now uses the existing `--wp--preset--color--bg-deep` token |
| 5 | Clients layout + AR content | Corporate = dense logo-tile grid; Individual = wide video-review cards | **Open** — layout rebuild + AR client posts are a genuinely bigger, separately-scoped slice (see below) |
| 6 | Footer contact | "Contact us" lists emails + phone numbers | **Resolved** — real contact channels + blurb wired via `SiteFooterRenderer`/`GlobalContent::footer()` |
| 7 | Header logo | Logo mark image + "Perego بيريجو" wordmark (`logo-full.png`) | **Resolved** — real logo image (46px), `aria-label` preserves the accessible name |
| 8 | Header CTA | Filled pill, uppercase "START A PROJECT" | **Resolved** — CTA now reuses the shared `.perego-btn.perego-btn--accent` primitive |
| 9 | Lang toggle | "AR EN" with the active language in a filled pill | **Resolved** — fixed dead CSS (selector targeted `button`, markup uses `span`/`a`) |
| 10 | Hero controls | Dot tablist only (desktop) | **Kept** — WCAG 2.2.2 pausable-carousel requirement (build note), not a defect |
| 11 | Typography | Section headings large + heavy | **Open** — a token/scale audit spanning every block, not Home-specific; separate slice |
| 12 | About copy (AR) | Arabic body renders on the AR page | **Resolved** — `HomeContent::about()` locale-aware, mirrors the hero/services pattern |
| 13 | Footer social | Row of social icons (IG/FB/LinkedIn/YouTube/WhatsApp) | **Resolved** — CSS mask-image icons keyed by network, handoff's own placeholder destinations |

## Resolution notes (2026-07-12)

**10 of 13 rows resolved** this session, re-verified via fresh baseline/live capture (EN + AR, desktop +
mobile) after every change, plus the full 72-check `verify-visual.mjs` route-health run and the full Pest
suite (186 tests). Work included:

- Copied the approved handoff image set into `perego-theme/assets/images/` (41 files).
- New `perego-theme/home-about` block (`HomeAboutRenderer` + `HomeContent::about()`) replacing the static,
  language-neutral markup in `front-page.html` that caused row 12 — FSE templates are theme files, not
  per-locale, so hardcoded prose there can never be Arabic on `/ar/`. Mirrors the existing
  locale-aware-provider pattern `hero-slider`/`services-teaser` already use.
- Real contact channels, blurb, and social icons wired into the footer (`GlobalContent::footer()`); phone
  numbers wrapped in `<bdi>` to stop RTL bidi reordering of digit groups — caught by reviewing the AR
  capture, not by an automated check.
- Header CTA now reuses the shared `.perego-btn`/`.perego-btn--accent` primitive instead of duplicating it;
  language-toggle CSS fixed (previously targeted `button` elements that don't exist in the real `span`/`a`
  markup — dead code since the Decision-10 real-navigation rework).
- Removed now-obsolete gradient/hue-rotate placeholder rules these rows replaced.
- **Regression caught and fixed**: adding the shared `.perego-btn` class to the header CTA anchor created an
  equal-specificity tie with the block's own `@media (max-width:1024px) { .perego-header__cta { display:none } }`
  mobile-hide rule; because `main.css` (theme-level, containing `.perego-btn`) loads *after* the block's own
  inlined style, the tie broke in `.perego-btn`'s favor, showing the desktop CTA on mobile and pushing every
  page 149-150px wider than its viewport (35 route-health failures across all 16 templates). Fixed by
  qualifying the override as `.perego-header .perego-header__cta` to make it win on specificity regardless of
  load order. Full 72-check suite is back to 0 failures.
- New Pest coverage: `HomeAboutRenderTest.php` (4 tests); `SiteFooterRenderTest.php` extended for the new
  constructor signature and contact-channel assertions; `HeroSliderRenderTest`/`ServicesTeaserRenderTest`
  updated for the added `get_stylesheet_directory_uri()` dependency.

## Remaining open rows

- **Row 5 (clients layout + AR content)**: the current implementation is a 4-card Swiper of demo
  placeholders on both Corporate/Individual rails; the handoff uses a dense logo-tile grid for Corporate and
  wide video-review cards for Individual — a real layout rebuild, not a token/CSS tweak. Separately, the
  `perego_client` CPT is already Polylang-translatable (`registerTranslatablePostTypes`) but has no AR posts
  seeded, so the AR clients section renders blank — a tracked content gap (PROGRESS.md already flags "Clients
  AR: follow-up"), not a code defect. Both need their own scoped slice.
- **Row 11 (typography scale)**: headings read smaller/lighter across every section versus the handoff. This
  looks like a `--wp--preset--font-size--*`/weight token audit that affects every block on every route, not
  something to fix inside the Home slice alone — tracked as its own cross-cutting pass.

## RTL / accessibility notes

- AR desktop and mobile mirror correctly: nav order, hero alignment, cards, forms, and the new footer
  contact/social columns all flip (`dir="rtl"`), verified across both breakpoints after every change.
- Route-health for all 16 mapped templates × EN/AR × mobile/desktop: **72 checks, 0 failures** (includes the
  regression found and fixed this session).

## Next

Rows 5 and 11 are the remaining Home work; both are more accurately framed as their own slices (content
seeding and a cross-block typography pass) than a continuation of this visual-difference row list. Re-run
this file's comparison procedure once those land, then move to Phase 4 (US2, other route families) per
`tasks.md`.
