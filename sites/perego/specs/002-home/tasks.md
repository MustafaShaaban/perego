# Tasks: Home (M2) — hero slider + services teaser

**Input**: [spec.md](./spec.md), [plan.md](./plan.md). **Tests**: Pest (PHP) + Jest (hero view.js),
required per the constitution's Definition of Done.

## Phase 1: Content foundation (blocks both stories)

- [x] T001 `perego-site/src/Content/HomeContent.php` — locale-aware value object (EN/AR inline,
      fallback en): `heroSlides()`, `heroCta()`, `servicesTeaserTitle()`, `servicesTeaserSeeAll()`,
      `services()`.
- [x] T002 [P] Pest `tests/Content/HomeContentTest.php` — locale selection, fallback, slide/service counts.

## Phase 2: US1 — hero slider

- [x] T003 [P] [US1] Pest `tests/Blocks/HeroSliderRenderTest.php` (write first, red).
- [x] T004 [US1] `src/Blocks/hero-slider/block.json` + `HeroSliderRenderer.php`.
- [x] T005 [US1] `src/Blocks/hero-slider/view.js` — Interactivity API state machine (advance/wrap,
      goTo, prev/next, play/pause, hover + visibility pause, reduced-motion gate, live region).
- [x] T006 [US1] `src/Blocks/hero-slider/style.scss` (tokenized port) + `index.js`.
- [x] T007 [P] [US1] Jest `src/Blocks/hero-slider/view.test.js`.
- [x] T008 [US1] Register in `PeregoSiteServiceProvider::boot()`; Pest + Jest green.

## Phase 3: US2 — services teaser

- [x] T009 [P] [US2] Pest `tests/Blocks/ServicesTeaserRenderTest.php` (write first, red).
- [x] T010 [US2] `src/Blocks/services-teaser/block.json` + `ServicesTeaserRenderer.php` + `style.scss` + `index.js`.
- [x] T011 [US2] Register in the service provider; Pest green.

## Phase 4: US3 — homepage composition

- [x] T012 [US3] `perego-theme/templates/front-page.html` — compose header → preloader → hero-slider →
      about group → services-teaser → footer.
- [x] T013 [US3] Live verify: `wp eval` render both blocks + `do_blocks(front-page.html)`; HTTP smoke 200.

## Phase 5: Guards + docs

- [x] T014 Guard Gate: wp-guard + clean-code-guard (PHP), test-guard (Pest + Jest).
- [x] T015 Update PROGRESS.md + DECISIONS.md (services-tabs→services-teaser reconciliation); commit + push.

## Visual fidelity check (2026-07-11)

Real browser render (Playwright + Chromium, `--host-resolver-rules=MAP perego.local 127.0.0.1`) at
1440×900 EN + AR(RTL) + 390×844 mobile, compared against the handoff `screenshots/01-home-en-desktop.png`
and `02-home-rtl-layout.png`. Faithful: header, hero (title/body/CTA/dots/prev-pause-next), staggered
service cards, About glass panels, footer — all render token-correct in both directions; AR flips to RTL
with Arabic hero + service copy and mirrored controls/arrow/dots. Three fidelity bugs found + fixed:
1. Services dropdown rendered open — `.perego-header__nav ul` (descendant) out-specified
   `.perego-header__dropdown{display:none}`; scoped to `> ul` (direct child).
2. About section rendered as a white gap — `main.css` was stale (built before the `.home-about`/
   `.glass-panel` primitives were added); rebuilt.
3. RTL desktop header nav overlapped — the mobile slide-in `translateX(-100%)` RTL rule leaked outside
   the mobile media query; moved inside the `≤1024px` breakpoint.
Also: put the brand gradient on `body` (not just `html` fixed) so inter-section gaps read violet.

Known minor item deferred to M7 visual QA: faint inter-section seams under `background-attachment:fixed`
(a Playwright fullPage stitching artifact; not observed as a real-browser defect).

## Notes
[P] = different files, parallelizable. Commit per task/small group.
