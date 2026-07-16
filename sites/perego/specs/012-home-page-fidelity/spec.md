# Spec 012 — Homepage design + functional completion

**Branch:** `feature/012-home-page-fidelity`
**Mode:** Client Site Mode
**Status:** Done (merged in PR #20; acceptance recorded 2026-07-16 during spec 020's audit)
**Depends on:** 009 (block-first architecture), 010 (Service/Client CPT + meta), 011 (global shell).

## Goal

The homepage already renders to the locked handoff visually (hero, About, services teaser, clients,
footer — verified in spec 011's full-page capture). Spec 012 closes the **editability** gap: the
handoff-derived copy that is currently baked into a PHP `const` must become **genuinely editable through
WordPress** without changing a pixel of the rendered output, EN + AR.

## Authoritative source

`_design_handoff/Perego-Creative-Studio-Final-Handoff/site/index.html` + `css/styles.css`; reference
`perego-reference.scss` → `assets/css/main.css`. Content baseline: the handoff `content/{en,ar}.json`.

## Audit — what is editable today vs hardcoded

| Homepage section | Renderer / source | Editable? |
|------------------|-------------------|-----------|
| Hero slider (slides, CTA) | `HeroSliderRenderer` → `HomeContent::heroSlides()`/`heroCta()` → **`HomeContent::COPY` const** | ❌ hardcoded |
| About / mission | `front-page.html` → `post-content` (front page's block content) | ✅ block editor |
| Services teaser (title, "See all", 4 cards) | `ServicesTeaserRenderer` → `HomeContent::services*()` → **`HomeContent::COPY` const** | ❌ hardcoded (and not sourced from the Service CPT that spec 010 gave real meta) |
| Clients (corporate + individual) | `ClientsCarouselRenderer` → **`WP_Query` on the Client CPT** | ✅ Client CPT |

So hero + services-teaser are the gap. About + Clients already edit through WordPress.

## Functional requirements

- **FR-1 Hero editable:** the hero slides (heading, body, CTA label/target) and paragraph indicators must be
  editable through WordPress (block attributes or an options/site-content seam) per locale, with the current
  handoff copy as the seeded default. Rendered markup/behaviour (carousel, autoplay/pause, dots, reduced
  motion) unchanged and byte-identical to today.
- **FR-2 Services teaser editable + CPT-sourced:** the four service cards should reflect the **Service CPT**
  (title, slug, image) so editing a Service updates the teaser; the teaser title and "See all" label editable.
  Order and the exact card markup preserved. Fallback to the seeded copy when no Services exist keeps it
  non-fatal.
- **FR-3 EN/AR parity:** every newly-editable field works in both locales (Polylang), matching how the rest
  of the site localizes; no English leaking onto `/ar/`.
- **FR-4 No visual regression:** EN + AR homepage stays pixel-identical to the spec 011 baseline capture.
- **FR-5 Editor workflow proven:** editing the hero/teaser in the real WordPress editor changes the front end.

## Acceptance

- [x] Hero + services-teaser content editable via a real WordPress workflow (block attrs and/or options/CPT).
- [x] Services teaser reflects Service CPT edits; degrades cleanly with no Services.
- [x] EN + AR both editable and correct; no hardcoded copy left as the *only* source.
- [x] Homepage visually identical to the spec 011 baseline (EN + AR), verified by capture
  (`output/playwright/012-en-homepage-noregression` evidence, tasks T001–T008).
- [x] Pest + Jest green; clean-code-guard + wp-guard pass.

*(Boxes ticked 2026-07-16: the work itself completed and merged in PR #20 — see `tasks.md` T001–T008 —
but the acceptance checklist was never updated. Recorded during spec 020's fresh home audit.)*

## Notes

`HomeContent::COPY` stays as the **seed/default**, not the live source of truth — mirror the spec 009 pattern
(block-first, seeded from the handoff, then editable). Decide per-field whether the editable seam is a block
attribute (hero, per-page) or an options/CPT record (services teaser ← Service CPT). Do not introduce the
removed `perego_section` CPT or any private CoreX duplicate; if a CoreX capability is missing, document the
gap in `docs/corex-framework-gaps.md` rather than fork the framework.
