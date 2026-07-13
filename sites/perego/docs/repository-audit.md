# Repository Audit — Perego Creative Studio

> Required by PEREGO_IMPLEMENTATION_PROMPT.md Phase 0. Evidence-based snapshot taken
> 2026-07-11 before continuing structural implementation work. Operating mode:
> **CLIENT SITE MODE** (edit only `sites/perego/**`; never CoreX framework internals).

## 1. Current architecture

- This checkout is a **CoreX framework repo** (`origin = perego`, `upstream = corex`)
  with the client site living at `sites/perego/` per the documented `sites/<client>/`
  convention. There is no separate framework checkout on disk.
- Client source:
  - `sites/perego/perego-site/` — plugin, namespace `PeregoSite\`, text domain
    `perego-site`, REST `perego/v1`.
  - `sites/perego/perego-theme/` — FSE block theme (`theme.json`, `templates/`, `parts/`).
  - `sites/perego/specs/`, `PROGRESS.md`, `DECISIONS.md`, `README.md`, `AGENTS.md`,
    `CLAUDE.md`, `docs/`.
- WordPress dev runtime at `C:\wamp64\www\perego\wp` (WP 7.0.1, gitignored, never
  committed). Reachable at `http://perego.local`. Active plugins: `corex-core`,
  `corex-blocks`, `corex-config`, `corex-forms`, `corex-ui`, `corex-kit-company`,
  `corex-media`, `perego-site`, `polylang` (Free v3.8.5).

## 2. Remotes & baseline

See `corex-baseline.md`. `origin`=Perego, `upstream`=CoreX (push disabled). Baseline
`v0.33.0` (`71639e7`), `upstream/main` `ff61bf0` already merged. Recovery checkpoint
branch `recovery/2026-07-11-pre-impl` created at `af9e4fe` and pushed to origin before
any structural work.

## 3. Existing work (committed)

| Milestone | Spec | State |
|---|---|---|
| M1 Global Foundation | `specs/001-global-foundation` | Header/footer/preloader blocks, `LanguageDriver`/`PolylangLanguageDriver`/`FallbackLanguageDriver`, EN/AR mechanism. Pest+Jest green. |
| M2 Home | `specs/002-home` | `hero-slider`, `services-teaser` blocks, `HomeContent`, `front-page.html`, **asset build pipeline**. Visual fidelity checked vs handoff. |
| M3-US1 Portfolio | `specs/003-services-portfolio` | `ProjectPostType` + `perego_project_category` taxonomy, `PortfolioContent`, `PortfolioGridRenderer`, `portfolio-grid` block, `archive-perego_project.html`, `scripts/seed-projects.php`. Committed in `af9e4fe`. |

**Note:** `sites/perego/PROGRESS.md` said US1 was "not yet committed" — this is **stale**;
`git log` confirms the US1 files landed in `af9e4fe fixing the root`. Corrected here.

## 4. Existing work (source built, not yet completed/verified)

- **M3-US2** — `ProjectGalleryLightboxRenderer` + `project-gallery-lightbox` block
  (dialog/focus-trap/counter/prev-next) with Pest+Jest written. NOT yet: provider
  wiring verified, `single-perego_project.html` template, gallery meta, live check.
- **M3-US3** — `ServicePostType` (+ test) exists. NOT yet: `ServiceContent` (EN/AR),
  service single renderer/template, services archive, provider wiring.

## 5. Framework pollution

**None.** No Perego references in `plugins/`, `addons/`, `packages/`, root `theme/`.
Client isolation is clean.

## 6. Hard-coded content / architecture-smell scan

- Header/footer are **FSE blocks** (`site-header`/`site-footer`) rendered into
  `parts/header.html` / `parts/footer.html` — not PHP partials. ✅
- Visible editorial prose currently comes from locale-aware PHP providers
  (`HomeContent`, `PortfolioContent`) rather than editor-canvas block content. ⚠️
  **Gap vs prompt Phase 4/6:** the prompt requires visible prose to be editor-canvas
  content (core blocks/RichText/InnerBlocks/post content), with providers used only as
  *seed defaults*. The current provider-render approach satisfies "dynamic/bilingual"
  but not "editor-canvas editable." Tracked as a remediation item (see PROGRESS "Next").
- Language switching currently uses a cookie-based toggle in the header block; the
  prompt requires real Polylang Free entity linking + Language Switcher block. ⚠️

## 7. Design handoff versions

- **Extracted (canonical):** `_design_handoff/Perego-Creative-Studio-Final-Handoff/` —
  root README identifies it as the **Final Design & Developer Handoff**. Contains
  `design-tokens.json`, `content/{en,ar}.json`, `docs/*.md` (12), `site/*.html` (16),
  `site/css/styles.css`, `site/js/main.js`, `emails/{en,ar}/`, `source/vectors/`,
  `screenshots/`.
- **Duplicate (packaged):** `Perego Creative Studio Final.zip` (16.9 MB) at repo root —
  same content, zipped. Candidate for removal in Phase 14 cleanup after confirming it
  matches the extracted copy (keep the extracted copy as design evidence).

## 8. Generated / runtime files (not source; keep gitignored)

- `wp/` (WordPress runtime), `**/build/`, `**/node_modules/`, `.phpunit.cache/`,
  `vendor/`. Confirmed gitignored.

## 9. Obsolete-file candidates (verify before removal in Phase 14)

- Root `Perego Creative Studio Final.zip` (duplicate of extracted handoff).
- `perego-site/src/Blocks/example/**`, `ExampleRenderer.php`, `Models/Example.php`,
  `Options/ExampleOptions.php`, `Repositories/ExampleRepository.php`,
  `Controllers/ExampleController.php` — `--starter` scaffold, removable per
  `perego-site/REMOVE-EXAMPLE.md` once no longer needed as reference.

## 10. Tests

- Pest (PHP) + Jest (JS) configured per client site
  (`sites/perego/perego-site/jest.config.js`). Prior runs: 70 Pest + 44 Jest green
  before the M3 session outage. Must re-run to confirm current state.

## 11. Recommended action (this session)

Repair in place (NOT clean restart) — the client layer already follows CoreX + FSE
correctly (FSE blocks, providers via container, tests, guards). See
`decision-repair-vs-restart.md`. Continue: complete M3 (US2/US3), then Polylang EN/AR
configuration + seeding, then remediate the editor-canvas-prose gap (§6) milestone by
milestone.
