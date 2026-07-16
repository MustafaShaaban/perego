# Spec 020 — Home page visual audit: live site vs design handoff

**Date:** 2026-07-16 · **Reference:** `_design_handoff/Perego-Creative-Studio-Final-Handoff/site/index.html`
served statically (reveal-on-scroll fired before every capture) · **Live:** `http://perego.local/` (+ AR home).
**Captures:** `sites/perego/output/020-home-audit/` — full-page: `handoff-en-1440`, `handoff-ar-1440`,
`handoff-en-375`, `live-en-1440`, `live-ar-1440`, `live-en-375`, `live-ar-375` (`-full.png`); per-section
element shots (`*-hero.png`, `*-services.png`, `*-footer.png`); post-fix evidence (`live-*-after.png`).
The handoff ships no AR-mobile reference beyond its client-side RTL flip, so the AR-375 comparison is
live-vs-RTL-rules rather than live-vs-capture.

## Method

Full-page and element screenshots at matched viewports (1440×900, 375×812), EN + AR, with each section
scrolled into view so the handoff's reveal animations settle (the old spec-008 baselines were captured
without this — their "empty" sections are a stale-evidence artifact, not a design difference).
Layout metrics cross-checked with computed styles (e.g. corp tile 115×77 and individual card 413×150 are
**identical** on both sides; clients-section heading 56px both sides).

## Findings

| # | Section | Difference (live vs handoff) | Classification | Disposition |
|---|---------|------------------------------|----------------|-------------|
| D1 | Footer bottom bar | Live: `© 2026 Perego` + Terms/Privacy. Handoff: `© <year> Perego Creative Studio — بيريجو. All rights reserved.` + **Journal**, Terms, Privacy (`site/index.html` footer bottom). Same gap on AR + mobile. | **Real drift** | Fix: restore full copyright line + Journal link (localized) |
| D2 | Footer quick-message form | Live textarea has no `maxlength` and no counter. Handoff caps message at **1200** chars with a live `0 / 1200` counter (`site/js/main.js` CAPS + field-counter; visible on home and contact reference pages). | **Real drift** | Fix: `maxlength="1200"` + live counter, matching handoff styling |
| D3 | Services-teaser card labels | Live: **white**, single-line (`VIDEO EDITING`). Handoff: **accent** `#d86af3` (inherited link color), explicit two-line break (`Video<br>Editing`), on desktop + mobile, EN + AR. | **Real drift** | Fix: accent token color + two-line wrap |
| D4 | Join-us form | Live adds a required **Email** field (not in handoff) and its input has no placeholder; label reads `Portfolio / website link` vs handoff `Portfolio/website link`. | **Accepted divergence** (an application needs a reply address; field is wired to storage/email) | Keep field; add placeholder for pattern consistency; align label copy |
| D5 | Hero slider controls | Live shows prev/pause/next; handoff page shows dots only. | **Not drift** — handoff `docs/INTERACTIONS.md` explicitly instructs the build to "add prev/next + pause/play + aria-live" | None |
| D6 | Clients section density | Live: 4 corporate tiles / 4 individual cards ("Sample …", "Example stat", AR stat untranslated). Handoff: 20 corporate tiles / 3 populated video cards. Tile/card dimensions and classes identical. | **Placeholder content** — owner material still unavailable | Out of scope; remains the FR-006 launch blocker (spec 004 T020) |

No other visible differences at either viewport in either language: hero typography/wrap/CTA, about &
mission glass panels, services imagery/stagger/hover ring, clients layout, footer forms' underline fields
and buttons all match.

One additional functional finding made while reading the implicated renderers:

| # | Section | Difference | Classification | Disposition |
|---|---------|-----------|----------------|-------------|
| D7 | AR home links | Hero CTA, "See All Services", and all four service cards built hrefs with `home_url()`, so the AR home linked to the **EN** pages (`/contact`, `/services/...`) — the same gap spec 019 closed for the nav. | **Real drift** (functional) | Fixed on the home renderers: `localizedUrl()`; AR now links `/ar/contact-2/`, `/ar/services/`, `/ar/services/<slug>-2/` (all verified 200). The same residual exists on non-home renderers (breadcrumbs, 404, portfolio grid, services overview, project nav) — deferred to those pages' passes. |

## Fixes applied + re-verification (2026-07-16)

- **D1** — `SiteFooterRenderer::renderBottomBar()`: full handoff copyright + localized Journal link.
  Verified live EN (`© 2026 Perego Creative Studio — بيريجو. All rights reserved.` + Journal/Terms/Privacy)
  and AR (translated المدونة link). `live-footer-after.png`, `live-ar-footer-after.png`.
- **D2** — `QuickMessageForm` message rule `max:2000`→`max:1200` + `maxlength` attr; new
  `site-footer/view.js` projects the live `n / 1200` counter via the shared `data-perego-counter`
  pattern (limit read from the textarea's maxlength); adapter SCSS renders it like the contact page's.
  Verified EN (`0 / 1200`) and AR (bidi-reordered exactly like the handoff's own RTL rendering).
- **D3** — `services-teaser/style.scss` label: accent token + `max-inline-size: calc(6.2em + 48px)`
  reproduces the handoff's two-line accent labels for all four seed labels (EN verified; AR unchanged
  apart from the accent color — the handoff carries no AR copy). `live-services-after.png`.
  Note: element screenshots can rasterize with a stale cached stylesheet after a rebuild — verify wrap
  changes with `Range.getClientRects()` line boxes plus a fresh full-viewport capture.
- **D4** — join form: EN label aligned to `Portfolio/website link`; email input gains a
  pattern-consistent placeholder (EN + AR). Field itself kept (documented divergence).
- **D7** — `HeroSliderRenderer` + `ServicesTeaserRenderer` link building moved to
  `LanguageDriver::localizedUrl()`. AR link targets verified 200.

Suites after the fixes: Pest **268/268** (816 assertions), Jest **80/80** (12 suites, including the new
site-footer counter tests). Builds: theme Sass + perego-site blocks compile clean.

## Bookkeeping

Spec 012's five acceptance boxes were still unchecked with header "In progress" although the work merged in
PR #20 — ticked as part of this spec (see spec.md there).
