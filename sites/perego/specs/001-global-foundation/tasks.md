# Tasks: Global Foundation (tokens, header/footer shell, bilingual, preloader)

**Input**: Design documents from `sites/perego/specs/001-global-foundation/`

**Prerequisites**: [plan.md](./plan.md) (done), [spec.md](./spec.md) (done)

**Tests**: required per the constitution's Definition of Done — Pest (PHP) + Jest (Interactivity API
JS) for every implementation task below.

## Phase 1: Setup (Shared Infrastructure)

- [x] T001 Design tokens → `perego-theme/theme.json` (done, prior session)
- [x] T002 Global base SCSS (`perego-theme/assets/src/scss/main.scss`) consuming those tokens, skip
      link, reduced-motion gate (done, prior session)
- [x] T003 Confirm `wp corex doctor` green and `perego-theme`/`perego-site` active before any new code
      (Environment Gate re-check)

**Checkpoint**: environment ready for feature work.

---

## Phase 2: Foundational — the language driver abstraction (blocks both US1's language toggle wiring and US2)

**Purpose**: US2 (bilingual) needs a language mechanism; US1's header block consumes it (for the
toggle control) even though US1 itself is about the shell, not translation — so this is foundational,
not story-scoped.

- [x] T004 [P] `perego-site/src/Language/LanguageDriver.php` — interface: `currentLocale(): string`,
      `isRtl(): bool`, `availableLocales(): array`, `urlFor(string $locale): string`
- [x] T005 [P] `perego-site/src/Language/FallbackLanguageDriver.php` — cookie-based
      (`perego_lang` cookie, 1-year expiry), defaults to `en`; `urlFor()` returns the current URL with a
      `?lang=` query var the front end intercepts client-side (no server routing needed for the
      fallback — the Interactivity API view-script does the actual swap)
- [x] T006 [P] `perego-site/src/Language/PolylangLanguageDriver.php` — wraps `pll_current_language()`,
      `pll_the_languages()`, `pll_home_url()` if `function_exists('pll_current_language')`
- [x] T007 `perego-site/src/Services/LanguageService.php` — resolves `PolylangLanguageDriver` if
      Polylang's functions exist, else `FallbackLanguageDriver`; exposes `driver(): LanguageDriver`
      (constructor-injected `?bool $polylangActive` rather than a container binding — see plan.md;
      `function_exists` cannot be Brain-Monkey-stubbed, this made it testable without weakening
      production auto-detection)
- [x] T008 Composed in `perego-site/src/PeregoSiteServiceProvider.php::register()`, exposed via
      `languageService()` for block render callbacks (site-level composition root, matching the
      --starter example's own established pattern rather than the framework's internal container)
- [x] T009 [P] Pest: `perego-site/tests/LanguageServiceTest.php` — 4 tests green
- [x] T010 [P] Pest: `perego-site/tests/FallbackLanguageDriverTest.php` — 7 tests green

**Checkpoint**: language mechanism resolvable and unit-tested independent of any block.

---

## Phase 3: User Story 1 — Consistent branded shell (Priority: P1) 🎯 MVP

**Goal**: header + footer render identically (structure) on every template, entirely token-driven.

**Independent Test**: activate the theme; every template shows the same header/footer; no literal
color/spacing/shadow value found in rendered CSS.

### Tests for User Story 1 (write first, confirm failing)

- [x] T011 [P] [US1] Pest: `perego-site/tests/Blocks/SiteHeaderRenderTest.php` — 10 tests green
- [x] T012 [P] [US1] Pest: `perego-site/tests/Blocks/SiteFooterRenderTest.php` — 3 tests green
- [ ] T013 [P] [US1] Jest: `perego-site/src/Blocks/site-header/view.test.js` — **not done**. No Jest
      config exists yet for `perego-site` (only the root framework's `jest.config.js`, scoped to its
      own `plugins/`/`addons/`). `view.js` itself is written and manually verified live (see PROGRESS.md);
      the automated Jest coverage for the sticky/mobile-menu/focus-trap/dropdown state machine is the
      known gap — pick this up before this spec is considered fully done, or explicitly accept the gap
      in a follow-up spec.

### Implementation for User Story 1

- [x] T014 [US1] `perego-site/src/Blocks/site-header/block.json` + `SiteHeaderRenderer.php` (a
      render-callback class, not a raw `render.php` — matches the `--starter` example's own
      `ExampleRenderer` pattern)
- [x] T015 [US1] `perego-site/src/Blocks/site-header/view.js` — sticky-scroll, mobile menu (focus trap,
      scroll lock, Esc/backdrop/link-click close, focus restore), mobile tap-accordion, language toggle
- [x] T016 [US1] `perego-site/src/Blocks/site-header/style.scss`
- [x] T017 [US1] `perego-site/src/Blocks/site-footer/block.json` + `SiteFooterRenderer.php` + `style.scss`
- [x] T018 [US1] `perego-theme/parts/header.html` → `<!-- wp:perego-theme/site-header /-->` (note: the
      actual block name is `perego-theme/*`, matching the generated example block's own convention —
      `perego/*` in this file's earlier draft was corrected during implementation)
- [x] T019 [US1] `perego-theme/parts/footer.html` → `<!-- wp:perego-theme/site-footer /-->`
- [x] T020 [US1] `perego-theme/templates/index.html` — `tagName` removed from the `wp:template-part`
      calls (the blocks already render their own semantic `<header>`/`<footer>` — keeping `tagName`
      would have double-wrapped)
- [x] T021 [US1] Self-applied `wp-guard` + `clean-code-guard` checklists (skills not invokable this
      session — read directly, applied manually); found + fixed one real issue: the `data-wp-context`
      JSON wasn't `esc_attr()`-wrapped (safe today, booleans-only, but the wrong pattern to leave in place)
- [x] T022 [US1] Pest (T011, T012) green; Jest (T013) not done — see above

**Checkpoint**: US1 fully functional and independently testable — every template shares one
token-driven header/footer.

---

## Phase 4: User Story 2 — Bilingual EN/AR (Priority: P1) 🎯 MVP

**Goal**: language toggle flips `lang`/`dir`, swaps font stack, mirrors layout, persists choice.

**Independent Test**: toggle language; `<html lang>`/`dir` flip and persist across reload; Arabic uses
Cairo/Tajawal; layout mirrors with no breakage.

### Tests for User Story 2

- [ ] T023 [P] [US2] Jest: `perego-site/src/Blocks/site-header/language-toggle.test.js` — **not done**,
      same Jest-infra gap as T013.

### Implementation for User Story 2

- [x] T024 [US2] Language-toggle markup wired into `SiteHeaderRenderer` (button per available locale,
      `data-locale`, `aria-pressed`, current locale highlighted)
- [x] T025 [US2] `view.js`'s `switchLanguage` action: applies `lang`/`dir`, persists to a
      `perego_lang` cookie, reloads so the server-rendered `is-current` state stays truthful
- [x] T026 [US2] Verified: `:root:lang(ar)` font swap in `main.scss` applies to the header/footer since
      they only consume the `--wp--preset--font-family--latin` alias, no separate override needed
- [x] T027 [US2] Polylang installed + activated for real (`wp plugin install polylang --activate`,
      v3.8.5); `LanguageService` confirmed to resolve `PolylangLanguageDriver` live. Languages not yet
      configured (Polylang exposes no simple public API for that — needs its own wp-admin wizard);
      documented in `DECISIONS.md` as a follow-up, not blocking since the fallback driver already
      satisfies every acceptance scenario in spec.md on its own.
- [x] T028 [US2] Self-applied guards — see T021.
- [ ] T029 [US2] Jest not done (see T023). Manual screenshot-fidelity comparison against
      `02-home-rtl-layout.png` **not done this session** — real next step before calling US2 fully closed.

**Checkpoint**: US1 + US2 both independently functional.

---

## Phase 5: User Story 3 — Branded first-visit preloader (Priority: P3)

**Goal**: homepage-only, first-visit-per-session, reduced-motion-safe branded preload.

**Independent Test**: clear session storage, load homepage — preloader shows, clears ≤2.5s; reload —
does not show again; with `prefers-reduced-motion` — never shows.

### Tests for User Story 3

- [x] T030 [P] [US3] Pest: `perego-site/tests/Blocks/PreloaderRenderTest.php` — resolved: render
      always emits markup (2 tests green); the session/timing/reduced-motion *display* gating is
      JS-only in `view.js`, and homepage-only placement is structural (only `front-page.html` includes
      the block at all).
- [ ] T031 [P] [US3] Jest: `perego-site/src/Blocks/preloader/view.test.js` — **not done**, same
      Jest-infra gap as T013/T023.

### Implementation for User Story 3

- [x] T032 [US3] `perego-site/src/Blocks/preloader/block.json` + `PreloaderRenderer.php` + `style.scss`
- [x] T033 [US3] `perego-site/src/Blocks/preloader/view.js` — session-gate (fails safe to "already
      shown" if `sessionStorage` throws), soft-hide ~0.9s, hard-hide 2.5s (independent timer, documented
      as an intentional backstop rather than dead code), reduced-motion check
- [x] T034 [US3] `perego-theme/templates/front-page.html` — header part + preloader + post-content +
      footer part; `index.html` (used by every other template) has no preloader
- [x] T035 [US3] Self-applied guards — see T021.
- [ ] T036 [US3] Pest green; Jest not done (see T031).

**Checkpoint**: all three user stories independently functional.

---

## Phase 6: Polish & Cross-Cutting

- [~] T037 [P] `docs-guard` not invokable this session (skill loaded from disk after session start);
      spec/plan/tasks kept current by hand throughout instead.
- [x] T038 `sites/perego/PROGRESS.md` and `sites/perego/DECISIONS.md` updated throughout, not just at
      the end.
- [x] T039 Full Pest suite: 32/32 green (`perego-site`). Jest: not applicable yet — no Jest config
      exists for `perego-site`/`perego-theme` (see T013/T023/T031).
- [ ] T040 **Not done.** Real next step: compare the live rendered header/footer/preloader against
      `_design_handoff/Perego-Creative-Studio-Final-Handoff/screenshots/*.png` and
      `site/index.html`/`site/css/styles.css` pixel-by-pixel — structure and tokens are faithful by
      construction (same token source), but no side-by-side visual check has been done yet.
- [ ] T041 **Not done** — this feature branch (`feature/001-global-foundation`) is not yet finished/
      merged/PR'd. Remaining before it can be: T013/T023/T031 (Jest), T029/T040 (visual fidelity checks).

## Honest status (2026-07-11)

**Done and verified live** (not just unit-tested): the language driver abstraction, the header/footer
shell with full desktop+mobile markup and Interactivity API wiring, the bilingual mechanism (Polylang
real + fallback), and the branded preloader. 32 Pest tests green. `wp corex doctor` green throughout.
No PHP fatals at any point.

**Real gaps, not swept under the rug**: no Jest coverage yet for any of the three blocks' JS behavior
(the JS is written and manually verified via direct block rendering + code review, not automated-tested
— this is the single biggest remaining risk in this spec, since the most complex logic here, the
focus-trap and session-gating, is exactly the kind of thing that regresses silently without a test).
No pixel/screenshot fidelity comparison against the design handoff yet. The environment's hosts-file
entry + Apache restart still need to run in an elevated shell (documented, not something this session
could do) before the site is reachable over HTTP to even do that visual comparison manually in a
browser.

---

## Dependencies & Execution Order

- Phase 1 (Setup) → Phase 2 (Foundational: language driver) → Phases 3/4/5 (user stories — all depend
  on Phase 2 for the language driver, but the header/footer *shell* work in US1 doesn't block on
  language mechanics beyond having the toggle *present*, hence US1 and US2 are sequenced together as
  they touch the same block) → Phase 6 (Polish).
- US3 (preloader) has no dependency on US1/US2 beyond sharing the theme's template files — could be
  built in parallel by a second contributor.

## Notes

- [P] tasks touch different files and can be done in parallel; non-[P] tasks in the same phase touch
  shared files or have a real ordering dependency.
- Commit after each task or small logical group, per the constitution's frequent-commit expectation —
  not one giant commit at the end.
