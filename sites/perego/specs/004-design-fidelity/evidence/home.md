# Home — visual-acceptance evidence (US1 / T009)

**Reference**: `_design_handoff/Perego-Creative-Studio-Final-Handoff/site/index.html`
**Live**: EN `http://perego.local/` · AR `http://perego.local/ar/الرئيسية/`
**Captures** (gitignored, regenerate with `node scripts/capture-baselines.mjs`):
`output/baselines/home-{mobile,desktop}.png`, `output/live/home-{en,ar}-{mobile,desktop}.png`.
**Breakpoints**: mobile 375, desktop 1280. **Status**: All 13 rows closed — 12 fully resolved, 1 resolved by
an explicit, owner-approved scope decision (see row 5).

## Material differences (baseline vs live)

Severity: **P1** = breaks the approved visual system; **P2** = notable but localized; **C** = content/asset
(owner or seed material). Each row is closed by a T010/T011 change or an explicit tracked blocker.

| # | Section | Handoff (reference) | Disposition |
| --- | --- | --- | --- |
| 1 | Hero bg | Dramatic geometric purple artwork with light rays (`hero-bg.png`) | **Resolved** — image wired via `HeroSliderRenderer` + drift/pulse animation, reduced-motion gated |
| 2 | About image | Hooded-figure image + "بيريجو" watermark (`about-hooded.png`) | **Resolved** — new `perego-theme/home-about` block renders the image + gradient overlay |
| 3 | Service cards | Each staggered card contains a real app-screenshot (`card-*.png`) | **Resolved** — real `<img>` per card via `HomeContent::services()['image']` |
| 4 | Clients bg | Section on the dark site gradient | **Resolved** — `.clients` now uses the existing `--wp--preset--color--bg-deep` token |
| 5 | Clients layout + AR content | Corporate = repeated icon-tile buttons opening a drag-scroll gallery/lightbox; Individual = wide video-review cards with a real YouTube embed | **Resolved by scope decision** — visual shape re-skinned onto the existing accessible carousel (see below); the drag-scroll+lightbox+video mechanic itself was explicitly descoped by the owner |
| 6 | Footer contact | "Contact us" lists emails + phone numbers | **Resolved** — real contact channels + blurb wired via `SiteFooterRenderer`/`GlobalContent::footer()` |
| 7 | Header logo | Logo mark image + "Perego بيريجو" wordmark (`logo-full.png`) | **Resolved** — real logo image (46px), `aria-label` preserves the accessible name |
| 8 | Header CTA | Filled pill, uppercase "START A PROJECT" | **Resolved** — CTA now reuses the shared `.perego-btn.perego-btn--accent` primitive |
| 9 | Lang toggle | "AR EN" with the active language in a filled pill | **Resolved** — fixed dead CSS (selector targeted `button`, markup uses `span`/`a`) |
| 10 | Hero controls | Dot tablist only (desktop) | **Kept** — WCAG 2.2.2 pausable-carousel requirement (build note), not a defect |
| 11 | Typography | Section headings large + heavy, in Open Sans/Cairo | **Resolved** — root cause was a missing font load (theme.json declared Open Sans/Cairo but no `@font-face`/webfont was ever enqueued, so every route silently fell back to `system-ui`); the size *tokens* already matched the handoff exactly |
| 12 | About copy (AR) | Arabic body renders on the AR page | **Resolved** — `HomeContent::about()` locale-aware, mirrors the hero/services pattern |
| 13 | Footer social | Row of social icons (IG/FB/LinkedIn/YouTube/WhatsApp) | **Resolved** — CSS mask-image icons keyed by network, handoff's own placeholder destinations |

## Resolution notes (2026-07-12)

**All 13 rows closed** this session, re-verified via fresh baseline/live capture (EN + AR, desktop +
mobile) after every change, plus the full 72-check `verify-visual.mjs` route-health run and the full
Pest (188) + Jest (67) suites. Work included:

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

## Row 5 — clients layout, scope decision and resolution

Reading the handoff's own `js/main.js` shows the "Corporate Clients" section is not real distinct client
logos at all — it's 20 repeated generic icon-buttons (`corp-icon.png`) that each open a drag-scroll
gallery/lightbox mixing placeholder images and a YouTube embed. "Individual Clients" cards embed real
YouTube videos with a custom play-button lightbox. Matching this exactly would mean building a new
interactive gallery/lightbox feature (drag-scroll track, keyboard nav, mixed image/video modal) for content
everyone agrees is temporary placeholder.

**Owner decision**: re-skin the existing, already-accessible Swiper carousel to match the handoff's visual
shape, rather than build the full custom drag-scroll+lightbox+video mechanic. Implemented:

- **Corporate** cards are now small square icon tiles (`.client-card--corporate`, handoff `.corp-card`
  proportions/gradient) showing the client's real thumbnail if set, else the approved `corp-icon.png`
  glyph. The client name moved from visible text to `aria-label` (matching the handoff, which shows no
  name on these tiles either) — still available to assistive tech.
- **Individual** cards are now wide info+thumbnail cards (`.client-card--individual`, handoff `.indiv-card`
  proportions) with the name/stat text beside a thumbnail. Deliberately **omitted** the handoff's
  decorative play-button overlay: unlike the handoff's demo, these cards have no real video to play, and a
  fake play affordance on a non-interactive card would be a misleading UI signal — a smaller, deliberate
  accessibility improvement over a literal copy.
- Swiper `slidesPerView`/breakpoints now differ per carousel (`clients-swiper--corporate` / `--individual`
  modifier classes) — dense tiles for Corporate, a few wide cards for Individual — instead of one shared
  density for both.
- The existing accessible carousel mechanism (keyboard nav, ARIA, Swiper a11y module, no-JS scroll
  fallback) is unchanged and untouched by the re-skin.
- New/updated tests: `ClientsCarouselRenderTest.php` (corporate icon-tile + individual info-card
  assertions) and `clients-carousel/view.test.js` (per-type Swiper sizing).

Confirmed live on EN + AR, desktop + mobile: both card types render correctly, mirror in RTL, and the
72-check route-health suite stays at 0 failures.

## RTL / accessibility notes

- AR desktop and mobile mirror correctly: nav order, hero alignment, cards, forms, and the new footer
  contact/social columns all flip (`dir="rtl"`), verified across both breakpoints after every change.
- Route-health for all 16 mapped templates × EN/AR × mobile/desktop: **72 checks, 0 failures** (includes the
  regression found and fixed this session).

## T013 — a11y/interaction re-run (2026-07-12)

Re-ran `verify-a11y.mjs` and `verify-interactions.mjs` against the corrected markup. The a11y run **caught
a real regression** from this session's own T010 work: the language-toggle restyle (row 9) used the
generic `--wp--preset--color--text` (white) for the active pill's text instead of the theme's dedicated
`--wp--preset--color--cta-text-on-accent` token, failing color-contrast against the bright accent
background on every route (12 pages, 1 serious violation each). Fixed by switching to the same
text-on-accent token `.perego-btn--accent` already uses correctly. Re-verified: **12 pages / 0 violations**,
**4/4 interaction checks**, and the full **72-check route-health + 188 Pest + 67 Jest** suites all still
green after the fix.

## T012 — editor-canvas migration (2026-07-12)

Migrated the About Us / Our mission prose from `HomeContent::about()` (PHP provider) to real,
editor-editable WordPress content, closing FR-005 for Home:

- Split the `home-about` block in two: `perego-theme/home-about-bg` (new — renders only the
  background image, decorative chrome) and the panels, now authored as real blocks in the static front
  page's own `post_content` via `<!-- wp:post-content -->`, restructured in `front-page.html` as
  `wp:group.home-about` > [`home-about-bg` block, `wp:group.home-about__inner` >
  `wp:group.home-about__panels` > `wp:post-content`].
- **Verified the localization mechanism before building on it**, not assumed: read Polylang's own
  `PLL_Frontend_Static_Pages`/`PLL_Static_Pages::get_translation()` source, which confirms the static
  front page (`page_on_front`) is translated automatically per-language from the existing post
  translation link — no extra Polylang configuration needed. Confirmed live: `/ar/` renders the AR
  page's own content (page 97), `/` renders the EN page's (page 42).
- New idempotent seed script `scripts/seed-home-about.php` writes the exact same copy `HomeContent::about()`
  used to hardcode into page 42 (EN, `page_on_front`) and its already-linked AR translation (page 97) —
  only when a page's content is empty, never overwriting later editor changes.
- Also set an explicit `post_excerpt` on both pages: `PeregoMeta`'s SEO description now sources a
  singular page's own excerpt, and the auto-generated one (WordPress concatenating the "About Us"
  heading directly into the body paragraph with no separator) read awkwardly. An explicit excerpt using
  the body text alone reads naturally when truncated at 160 characters.
- Removed `HomeAboutRenderer`/the old `home-about` block/`HomeContent::about()` entirely — dead code
  after the split, not deprecated in place.
- Visual output confirmed byte-for-byte identical to before the migration (fresh EN/AR desktop capture)
  — this was a structural move, not a visual change.

Re-verified after the migration: **72-check route-health, 12-page a11y (0 violations), 4/4 interaction,
186 Pest (2 new in `HomeAboutBgRenderTest.php`, replacing the removed `HomeAboutRenderTest.php`), 67
Jest** — all green.

## Next

Home's visual-fidelity slice (T009-T013) is fully done, including the editor-canvas migration (T012).
Move to Phase 4 (US2, other route families) per `tasks.md`.
