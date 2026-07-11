# Tasks: Home (M2) — hero slider + services teaser

**Input**: [spec.md](./spec.md), [plan.md](./plan.md). **Tests**: Pest (PHP) + Jest (hero view.js),
required per the constitution's Definition of Done.

## Phase 1: Content foundation (blocks both stories)

- [ ] T001 `perego-site/src/Content/HomeContent.php` — locale-aware value object (EN/AR inline,
      fallback en): `heroSlides()`, `heroCta()`, `servicesTeaserTitle()`, `servicesTeaserSeeAll()`,
      `services()`.
- [ ] T002 [P] Pest `tests/Content/HomeContentTest.php` — locale selection, fallback, slide/service counts.

## Phase 2: US1 — hero slider

- [ ] T003 [P] [US1] Pest `tests/Blocks/HeroSliderRenderTest.php` (write first, red).
- [ ] T004 [US1] `src/Blocks/hero-slider/block.json` + `HeroSliderRenderer.php`.
- [ ] T005 [US1] `src/Blocks/hero-slider/view.js` — Interactivity API state machine (advance/wrap,
      goTo, prev/next, play/pause, hover + visibility pause, reduced-motion gate, live region).
- [ ] T006 [US1] `src/Blocks/hero-slider/style.scss` (tokenized port) + `index.js`.
- [ ] T007 [P] [US1] Jest `src/Blocks/hero-slider/view.test.js`.
- [ ] T008 [US1] Register in `PeregoSiteServiceProvider::boot()`; Pest + Jest green.

## Phase 3: US2 — services teaser

- [ ] T009 [P] [US2] Pest `tests/Blocks/ServicesTeaserRenderTest.php` (write first, red).
- [ ] T010 [US2] `src/Blocks/services-teaser/block.json` + `ServicesTeaserRenderer.php` + `style.scss` + `index.js`.
- [ ] T011 [US2] Register in the service provider; Pest green.

## Phase 4: US3 — homepage composition

- [ ] T012 [US3] `perego-theme/templates/front-page.html` — compose header → preloader → hero-slider →
      about group → services-teaser → footer.
- [ ] T013 [US3] Live verify: `wp eval` render both blocks + `do_blocks(front-page.html)`; HTTP smoke 200.

## Phase 5: Guards + docs

- [ ] T014 Guard Gate: wp-guard + clean-code-guard (PHP), test-guard (Pest + Jest).
- [ ] T015 Update PROGRESS.md + DECISIONS.md (services-tabs→services-teaser reconciliation); commit + push.

## Notes
[P] = different files, parallelizable. Commit per task/small group.
