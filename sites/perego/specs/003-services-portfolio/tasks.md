# Tasks: Services + Portfolio (M3)

**Input**: [spec.md](./spec.md). Tests: Pest + Jest per the constitution.

## Phase 1: Project CPT + portfolio grid (US1) — ✅ DONE (2026-07-11)

- [x] T001 `src/PostTypes/ProjectPostType.php` — `perego_project` CPT + `perego_project_category`
      taxonomy (4 terms); pure `postTypeArgs()`/`taxonomyArgs()`. Pest: 4 tests.
- [x] T002 `src/Content/PortfolioContent.php` — locale-aware filter labels + strings (EN/AR). Pest: 5.
- [x] T003 `src/Blocks/PortfolioGridRenderer.php` — filter chips + card grid + no-results + optional
      heading; pure `render(projects, filterLabels, strings)`. Pest: 8.
- [x] T004 `src/Repositories/ProjectRepository.php` — WP_Query (bounded, no_found_rows) → grid array
      (`toGridCard()`); meta `_perego_client`/`_perego_year`, featured image, first term.
- [x] T005 `src/Blocks/portfolio-grid/{block.json,index.js,view.js,style.scss}` — Interactivity API
      filter (setFilter, filterPressed, cardHidden, noResultsHidden). Jest: 7.
- [x] T006 Register CPT + block in `PeregoSiteServiceProvider::registerPortfolio()`.
- [x] T007 `perego-theme/templates/archive-perego_project.html`.
- [x] T008 `scripts/seed-projects.php` — 9 placeholder projects + 4 terms (idempotent).
- [x] T009 Env: pretty permalinks + `wp/.htaccess` (see DECISIONS.md); build blocks.
- [x] T010 Live verify: `/work/` 200, 9 cards + 5 filters render, assets enqueue, overflow fixed
      (global `box-sizing` reset). Screenshot EN + AR/RTL + mobile.
- [x] T011 Guard Gate (wp-guard/clean-code/test-guard); full suite 70 Pest + 44 Jest green.

## Phase 2: Project single + gallery lightbox (US2) — TODO

- [ ] T012 `perego/project-gallery-lightbox` block (dialog, focus trap, counter, prev/next) + Jest.
- [ ] T013 `single-perego_project.html` template (overview/challenge/approach/solution/result + gallery).
- [ ] T014 Project meta fields (role, deliverables, gallery) + admin.

## Phase 3: Service CPT + singles + archive (US3) — TODO

- [ ] T015 `service` CPT (4 fixed) + `ServicePostType` + seed.
- [ ] T016 Service single template (hero, intro, process, selected work, closing CTA).
- [ ] T017 Services archive ("One studio, four services") template/block.

## Notes
Placeholder projects use "Sample Client" — replace with real work (CONTENT_MODEL.md). Commit per slice.
