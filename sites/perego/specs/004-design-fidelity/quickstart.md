# Design Fidelity Validation Guide

## Purpose

Prove that each Perego route is identical to the locked handoff before calling it visually complete. The reference is `_design_handoff/Perego-Creative-Studio-Final-Handoff/site/`; it is not inspiration or a starting point for redesign.

## Prerequisites

1. Work from `feature/004-design-fidelity` with the final handoff present at the recorded path.
2. Verify the client source and compiled block/theme assets are current.
3. Verify the WordPress runtime boots and the Perego theme, client plugin, and Polylang Free are active.
4. Use real EN and AR URLs discovered from the page's `hreflang` links; do not construct Arabic URLs by hand.

## Comparison procedure

1. Choose one row and state from [route-matrix.md](./route-matrix.md).
2. Render the matching static handoff page at the same viewport. Use the supplied screenshots where available; otherwise create a deterministic baseline from the static handoff.
3. Render the matching WordPress route with matching seeded content, fonts loaded, and animation frozen only when needed for repeatable comparison.
4. Compare hierarchy, dimensions, spacing, typography, colors, borders, radii, shadows, imagery, RTL order, responsive behavior, and interaction state. A difference is a defect unless unavoidable browser font rasterization is the only cause.
5. Record the evidence and every material difference in `route-matrix.md` or a linked per-route evidence file before editing.
6. Correct only the documented difference. Do not introduce new styling or interactions.
7. Re-run the focused visual, interaction, accessibility, RTL, and relevant unit checks. Mark the route accepted only after all material differences are resolved.

## Existing verification commands

Run from the repository root after confirming their prerequisites in the client package scripts:

```powershell
node sites/perego/perego-site/scripts/verify-visual.mjs
node sites/perego/perego-site/scripts/verify-a11y.mjs
node sites/perego/perego-site/scripts/verify-interactions.mjs
```

These scripts provide route-health, accessibility, and selected interaction evidence. They do **not** replace screenshot comparison against the handoff.

## Environment & build verification (T006 — 2026-07-12)

Recorded before any visual-acceptance slice, to prove the comparison target is real and current.

**Live runtime** (`wp` CLI against the install at `wp/`):

- `siteurl` = `http://perego.local`; `GET /` returns **HTTP 200** (Chromium via `host-resolver-rules`, no hosts-file edit).
- Active theme: **`perego-theme`**.
- Active plugins: `corex-core`, `corex-blocks`, `corex-config`, `corex-forms`, `corex-ui`, `corex-kit-company`,
  `corex-media`, `corex-captcha`, `corex-careers`, `corex-email`, `advanced-custom-fields`, **`polylang`**, **`perego-site`**.

**Build outputs** (regenerated clean this session; output is gitignored):

- `perego-site`: `npm run build` → webpack **compiled successfully** (all block `view.js`/`style-index.css`, incl.
  clients-carousel, hero-slider, portfolio-grid, preloader, project-gallery-lightbox, site-header).
- `perego-theme`: `npm run styles` (SCSS → `assets/css/main.css`, compressed) and `npm run scripts`
  (`assets/js/main.js`) both **succeed**. The chained `npm run images` step fails only because `sharp` is not
  installed in this theme — an **optional** image-optimization pass that does not affect CSS/JS/block output. Tracked
  as an environment gap, not a defect.

**Route enumeration evidence** (T007): `node scripts/verify-visual.mjs` from `perego-site/` → **72 checks, 0 hard
failures** across all 16 mapped templates × EN/AR × mobile+desktop. One informational GAP: the representative
standard page (`/sample-page/`) has no Arabic Polylang translation — a content gap for the owner, not a template
defect. Evidence: `perego-site/output/verify-visual.json`.

## Evidence policy

- Keep generated baseline/live captures and diff artifacts outside committed production assets unless explicitly designated as durable evidence.
- Commit only concise matrices, findings, and reproducible scripts needed to regenerate evidence.
- A route blocked by missing approved assets, real business content, or legal approval remains `Blocked on owner material`; it must not be recorded as accepted.
