# Perego on CoreX — Design

**Date**: 2026-07-11
**Status**: Approved by owner (Mustafa Shaaban) — ready for M1 execution.

## Purpose

Rebuild the Perego Creative Studio marketing site (bilingual EN/AR, dark-violet/magenta identity, glassmorphic
panels) as a real client site on the CoreX WordPress framework, using the "Perego Creative Studio Final Handoff"
package as the visual + behavioural + content reference. The handoff is a high-fidelity static HTML/CSS/JS
prototype — **not** to be copied verbatim. It is rebuilt using CoreX's own patterns (FSE blocks, CPTs, tokens)
while preserving the exact tokens, layouts, copy, and interactions documented in the handoff. Do not redesign.

Reference material (unpacked from `Perego Creative Studio Final.zip`):
`_design_handoff/Perego-Creative-Studio-Final-Handoff/` — `design-tokens.json`, `content/{en,ar}.json`, the
`site/` HTML/CSS/JS prototype, `emails/{en,ar}/`, `screenshots/`, and the `docs/` handoff set (`ROUTES_AND_TEMPLATES.md`,
`COMPONENTS.md`, `CONTENT_MODEL.md`, `CONTENT_EN_AR_MAP.md`, `INTERACTIONS.md`, `RESPONSIVE_BEHAVIOR.md`,
`ACCESSIBILITY_HANDOFF.md`, `SEO_HANDOFF.md`, `EMAIL_HANDOFF.md`, `ASSET_MANIFEST.md`, `COREX_DESIGN_MAPPING.md`,
`QA_CHECKLIST.md`).

## Environment & repo layout

CoreX framework already exists as a live checkout at `C:\wamp64\www\corex` (git remote → `MustafaShaaban/corex`,
WordPress runtime under `corex\wp`, Spec Kit workflow already wired). It is **not** modified for this project.

Perego gets its **own, independent WordPress install and its own git repo**, wired to the framework only by
read-only junctions:

```
C:\wamp64\www\perego\              ← MustafaShaaban/perego git repo root
  perego-site/                     ← generated plugin (PeregoSite\ namespace, perego/v1 REST, --perego- CSS prefix)
  perego-theme/                    ← generated FSE block theme
  specs/  docs/  AGENTS.md  CLAUDE.md  PROGRESS.md  DECISIONS.md   ← generated governance (CoreX convention)
  wp/                              ← gitignored WordPress core + wp-content (own DB `perego`, vhost perego.local)
    wp-content/plugins/corex-core, corex-blocks, corex-config, corex-forms   → junctions → C:\wamp64\www\corex\plugins\*
    wp-content/plugins/corex-ui, corex-kit-company, corex-media              → junctions → C:\wamp64\www\corex\addons\*
    wp-content/themes/corex                                                  → junction  → C:\wamp64\www\corex\theme
    wp-content/plugins/perego-site  → junction → ..\..\perego-site  (sibling, tracked in the perego repo)
    wp-content/themes/perego        → junction → ..\..\perego-theme (sibling, tracked in the perego repo)
```

This mirrors exactly how the CoreX checkout maps itself into its own `wp/` — same pattern, one level removed.

## Content / architecture mapping

From the handoff's own `COREX_DESIGN_MAPPING.md`, cross-checked against `ROUTES_AND_TEMPLATES.md`:

- **CPTs / taxonomies**: `service` (4 fixed entries), `project` (+ category taxonomy: video/motion/design/web),
  `client` (+ client-type taxonomy: corporate/individual). Journal uses native WP posts + categories + comments.
- **Templates** (16 in the handoff, all FSE): front-page (Home), services archive, 4× service single, project
  archive, project single, contact (custom), journal archive, single post, search, default page, legal (page + TOC
  variant), 404, plus 6 transactional email templates (EN+AR) via the `corex-email` addon.
- **i18n**: bilingual EN/AR, RTL via CSS logical properties, via **Polylang** — already CoreX's own documented
  framework decision, not introduced here. Content source of truth: `content/en.json` ↔ `content/ar.json` (identical
  key structure).
- **Tokens**: `design-tokens.json` (color, gradient, typography, spacing, radius, border, shadow, glass, motion,
  breakpoints, zIndex) → `perego-theme/theme.json` settings + `--perego-*` CSS custom properties. No hardcoded
  values in block CSS.

## FSE-native, block-first, dynamic (owner requirement)

The site must be **built entirely on FSE and blocks** — editable and dynamic, not hardcoded PHP markup — and must
match the handoff design **identically** (tokens, layout, copy, motion), not "inspired by."

- **Templates & parts**: every page is an FSE `templates/*.html` composed of blocks. No classic PHP template
  markup for layout. Header/footer become `parts/header.html` / `parts/footer.html`.
- **Bespoke sections → dynamic blocks**, auto-discovered via `corex-blocks`, editable via attributes and/or Block
  Bindings to CPT fields, so an editor changes content through wp-admin (no code change) and the front end reflects
  it:
  - `perego/hero-slider` (headline rotator + dots + prev/next/pause)
  - `perego/services-tabs` (2×2 glass tiles, active state, bound to the `service` CPT)
  - `perego/portfolio-grid` (masonry + service-taxonomy filter + no-results state, queries `project`)
  - `perego/project-gallery-lightbox` (accessible dialog + focus trap + counter)
  - `perego/client-carousel` (+ corporate equalizer tiles + individual cards, bound to `client`)
  - `perego/service-chooser` (project-brief multi-toggle, `?service=` preselect)
  - `perego/legal-toc` (scrollspy)
  - `perego/preloader` (branded, home/first-visit, reduced-motion-safe)
- **Standard pieces** (post cards, breadcrumbs, pagination, comments, search form, forms) reuse `corex-ui`'s
  existing `corex/*` blocks/patterns where they already fit; only build new `perego/*` blocks where CoreX has no
  equivalent.
- **Interactivity**: hero rotation, tab state, masonry filter, lightbox, carousel, TOC scrollspy, sticky header,
  mobile menu — ported from the reference `site/js/main.js` IIFEs to the WordPress **Interactivity API**,
  block-scoped. No jQuery, no ad hoc global scripts.
- **Fidelity check**: each milestone's acceptance includes a visual/behavioural comparison against the handoff's
  reference screenshots (`screenshots/*.png`) and live HTML prototype (`site/*.html`), not just token/structure
  correctness.

## Phasing

Each milestone is one CoreX spec folder (`specs/NNN-slug/`), one branch, one PR — using **CoreX's own native**
`/specify → /clarify → /plan → /tasks → /implement` workflow (the guard skills its generated `AGENTS.md`/`CLAUDE.md`
require), **not** the generic superpowers plan/implement flow — Perego inherits CoreX's own constitution once
generated.

1. **M1 Foundation** — independent WP install + vhost/DB, junctions to the CoreX checkout, `wp corex make:site
   Perego --starter`, tokens → `theme.json`, global header/footer/nav parts, skip link, Polylang wiring, branded
   preloader block.
2. **M2 Home** — `perego/hero-slider`, `perego/services-tabs`, homepage content from `content/{en,ar}.json`.
3. **M3 Services + Portfolio** — `service` CPT + singles; `project` CPT + taxonomy + `perego/portfolio-grid` +
   project single + `perego/project-gallery-lightbox`.
4. **M4 Contact + Clients** — contact/start-a-project page + `perego/service-chooser` + corex-forms wiring;
   `client` CPT + `perego/client-carousel`.
5. **M5 Journal + Legal + Search + 404** — post archive/single + comments, legal pages + `perego/legal-toc`, search
   template, 404.
6. **M6 Emails** — the 6 transactional templates, EN+AR, via `corex-email`.
7. **M7 Polish** — SEO handoff, accessibility handoff, responsive QA, the handoff's own `QA_CHECKLIST.md` pass.

## Out of scope / deferred decisions

- Real portfolio/journal content and imagery (handoff marks these as placeholder/example) — replace with real data
  when Perego supplies it.
- Legal page bodies (Terms/Privacy) are layout drafts — must be reviewed by counsel before launch, per the handoff.
- No invented business claims (awards, client counts, ratings) without written confirmation from Perego.
- Production deployment target/hosting — decide at M7, using CoreX's `dist/` build + deploy guides.
