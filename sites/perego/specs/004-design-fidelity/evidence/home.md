# Home — visual-acceptance evidence (US1 / T009)

**Reference**: `_design_handoff/Perego-Creative-Studio-Final-Handoff/site/index.html`
**Live**: EN `http://perego.local/` · AR `http://perego.local/ar/الرئيسية/`
**Captures** (gitignored, regenerate with `node scripts/capture-baselines.mjs`):
`output/baselines/home-{mobile,desktop}.png`, `output/live/home-{en,ar}-{mobile,desktop}.png`.
**Breakpoints**: mobile 375, desktop 1280. **Status**: In review — 12 of 13 rows resolved; 1 row partially
open (clients layout rebuild against the handoff's exact gallery/lightbox mechanic — the AR-content half of
this row is now also fixed).

## Material differences (baseline vs live)

Severity: **P1** = breaks the approved visual system; **P2** = notable but localized; **C** = content/asset
(owner or seed material). Each row is closed by a T010/T011 change or an explicit tracked blocker.

| # | Section | Handoff (reference) | Disposition |
| --- | --- | --- | --- |
| 1 | Hero bg | Dramatic geometric purple artwork with light rays (`hero-bg.png`) | **Resolved** — image wired via `HeroSliderRenderer` + drift/pulse animation, reduced-motion gated |
| 2 | About image | Hooded-figure image + "بيريجو" watermark (`about-hooded.png`) | **Resolved** — new `perego-theme/home-about` block renders the image + gradient overlay |
| 3 | Service cards | Each staggered card contains a real app-screenshot (`card-*.png`) | **Resolved** — real `<img>` per card via `HomeContent::services()['image']` |
| 4 | Clients bg | Section on the dark site gradient | **Resolved** — `.clients` now uses the existing `--wp--preset--color--bg-deep` token |
| 5 | Clients layout + AR content | Corporate = repeated icon-tile buttons opening a drag-scroll gallery/lightbox; Individual = wide video-review cards with a real YouTube embed | **Partially resolved** — AR content half fixed (see below); the gallery/lightbox layout rebuild itself is a separate, larger slice |
| 6 | Footer contact | "Contact us" lists emails + phone numbers | **Resolved** — real contact channels + blurb wired via `SiteFooterRenderer`/`GlobalContent::footer()` |
| 7 | Header logo | Logo mark image + "Perego بيريجو" wordmark (`logo-full.png`) | **Resolved** — real logo image (46px), `aria-label` preserves the accessible name |
| 8 | Header CTA | Filled pill, uppercase "START A PROJECT" | **Resolved** — CTA now reuses the shared `.perego-btn.perego-btn--accent` primitive |
| 9 | Lang toggle | "AR EN" with the active language in a filled pill | **Resolved** — fixed dead CSS (selector targeted `button`, markup uses `span`/`a`) |
| 10 | Hero controls | Dot tablist only (desktop) | **Kept** — WCAG 2.2.2 pausable-carousel requirement (build note), not a defect |
| 11 | Typography | Section headings large + heavy, in Open Sans/Cairo | **Resolved** — root cause was a missing font load (theme.json declared Open Sans/Cairo but no `@font-face`/webfont was ever enqueued, so every route silently fell back to `system-ui`); the size *tokens* already matched the handoff exactly |
| 12 | About copy (AR) | Arabic body renders on the AR page | **Resolved** — `HomeContent::about()` locale-aware, mirrors the hero/services pattern |
| 13 | Footer social | Row of social icons (IG/FB/LinkedIn/YouTube/WhatsApp) | **Resolved** — CSS mask-image icons keyed by network, handoff's own placeholder destinations |

## Resolution notes (2026-07-12)

**12 of 13 rows resolved** this session, re-verified via fresh baseline/live capture (EN + AR, desktop +
mobile) after every change, plus the full 72-check `verify-visual.mjs` route-health run and the full Pest
suite (187 tests). Work included:

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
- **Row 11 root-caused, not patched**: the font-size *tokens* in `theme.json` already matched the handoff's
  `--fs-*` clamp expressions byte-for-byte — the "smaller/lighter" impression was never a size mismatch.
  The real cause: `theme.json` declares Open Sans (Latin) / Cairo (Arabic) as the brand typefaces, but no
  `functions.php` code ever loaded the font files, so every route silently rendered the browser's
  `system-ui` fallback at the same declared px size — a different, thinner typeface reads smaller even at
  an identical font-size. Fixed by enqueuing the same Google Fonts request the handoff itself uses
  (`wp_enqueue_style` + `wp_resource_hints` preconnect filter — core APIs, not hand-echoed `<link>` tags),
  confirmed visually on both EN (Open Sans Bold) and AR (Cairo) captures. This affects every route, not just
  Home, since it's a single site-wide `functions.php` enqueue — likely closes part of row 11's equivalent on
  every other route family too, though each should still be visually re-checked in its own slice.
- **Row 5 AR-content half fixed — was a real query bug, not a missing-seed gap**: PROGRESS.md's older
  "Clients AR: follow-up" note assumed the AR client posts hadn't been seeded yet. They actually already
  existed (`seed-ar-content.php` had already run) and were correctly tagged with their own Polylang-linked
  AR taxonomy terms (`corporate-ar`/`individual-ar`) — but `ClientsCarouselRenderer::query()` always
  queried the *English* term slug regardless of the current language, so the AR carousels matched zero
  posts. Fixed by resolving the taxonomy term for the current locale via `pll_get_term()` (falling back to
  the English term when Polylang is inactive, per constitution IX). Confirmed live: `/ar/الرئيسية/` now
  renders all 8 AR client cards. New regression test in `ClientsCarouselRenderTest.php`.

## Remaining open row

- **Row 5, gallery/lightbox layout**: reading the handoff's own `js/main.js` shows the "Corporate Clients"
  section is not real distinct client logos at all — it's 20 repeated generic icon-buttons
  (`corp-icon.png`) that each open a drag-scroll gallery/lightbox mixing placeholder images and a YouTube
  embed. "Individual Clients" cards embed real YouTube videos with a custom play-button lightbox. Matching
  this exactly means building a new interactive gallery/lightbox feature (drag-scroll track, keyboard nav,
  mixed image/video modal), not a CSS or content change — a properly separate, larger slice. The project
  already has a `project-gallery-lightbox` block (spec 003 US2) that may be adaptable as a starting point.

## RTL / accessibility notes

- AR desktop and mobile mirror correctly: nav order, hero alignment, cards, forms, and the new footer
  contact/social columns all flip (`dir="rtl"`), verified across both breakpoints after every change.
- Route-health for all 16 mapped templates × EN/AR × mobile/desktop: **72 checks, 0 failures** (includes the
  regression found and fixed this session).

## Next

The clients gallery/lightbox layout (row 5) is the only remaining Home work — a properly separate feature
slice (new interactive component), not a continuation of this visual-difference row list. Re-run this
file's comparison procedure once it lands, then move to Phase 4 (US2, other route families) per `tasks.md`.
