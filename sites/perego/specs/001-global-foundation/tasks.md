# Tasks: Global Foundation (tokens, header/footer shell, bilingual, preloader)

**Input**: Design documents from `sites/perego/specs/001-global-foundation/`

**Prerequisites**: [plan.md](./plan.md) (done), [spec.md](./spec.md) (done)

**Tests**: required per the constitution's Definition of Done — Pest (PHP) + Jest (Interactivity API
JS) for every implementation task below.

## Phase 1: Setup (Shared Infrastructure)

- [x] T001 Design tokens → `perego-theme/theme.json` (done, prior session)
- [x] T002 Global base SCSS (`perego-theme/assets/src/scss/main.scss`) consuming those tokens, skip
      link, reduced-motion gate (done, prior session)
- [ ] T003 Confirm `wp corex doctor` green and `perego-theme`/`perego-site` active before any new code
      (Environment Gate re-check)

**Checkpoint**: environment ready for feature work.

---

## Phase 2: Foundational — the language driver abstraction (blocks both US1's language toggle wiring and US2)

**Purpose**: US2 (bilingual) needs a language mechanism; US1's header block consumes it (for the
toggle control) even though US1 itself is about the shell, not translation — so this is foundational,
not story-scoped.

- [ ] T004 [P] `perego-site/src/Language/LanguageDriver.php` — interface: `currentLocale(): string`,
      `isRtl(): bool`, `availableLocales(): array`, `urlFor(string $locale): string`
- [ ] T005 [P] `perego-site/src/Language/FallbackLanguageDriver.php` — cookie-based
      (`perego_lang` cookie, 1-year expiry), defaults to `en`; `urlFor()` returns the current URL with a
      `?lang=` query var the front end intercepts client-side (no server routing needed for the
      fallback — the Interactivity API view-script does the actual swap)
- [ ] T006 [P] `perego-site/src/Language/PolylangLanguageDriver.php` — wraps `pll_current_language()`,
      `pll_the_languages()`, `pll_home_url()` if `function_exists('pll_current_language')`
- [ ] T007 `perego-site/src/Services/LanguageService.php` — resolves `PolylangLanguageDriver` if
      Polylang's functions exist, else `FallbackLanguageDriver`; exposes `driver(): LanguageDriver`
- [ ] T008 Bind `LanguageService` as a container singleton in
      `perego-site/src/PeregoSiteServiceProvider.php`
- [ ] T009 [P] Pest: `perego-site/tests/LanguageServiceTest.php` — resolves fallback driver when
      Polylang absent (this environment has no Polylang installed yet — verified in T003)
- [ ] T010 [P] Pest: `perego-site/tests/FallbackLanguageDriverTest.php` — default locale `en`,
      `isRtl()` false for `en` / true for `ar`, `urlFor()` shape

**Checkpoint**: language mechanism resolvable and unit-tested independent of any block.

---

## Phase 3: User Story 1 — Consistent branded shell (Priority: P1) 🎯 MVP

**Goal**: header + footer render identically (structure) on every template, entirely token-driven.

**Independent Test**: activate the theme; every template shows the same header/footer; no literal
color/spacing/shadow value found in rendered CSS.

### Tests for User Story 1 (write first, confirm failing)

- [ ] T011 [P] [US1] Pest: `perego-site/tests/Blocks/SiteHeaderRenderTest.php` — renders nav items in
      the documented order (Home, About Us, Services+dropdown, Work, Journal, Clients, Contact Us),
      CTA present, `aria-current="page"` on the active item
- [ ] T012 [P] [US1] Pest: `perego-site/tests/Blocks/SiteFooterRenderTest.php` — 3 columns present
      (contact / quick-message entry point / careers entry point) + bottom bar with current year
- [ ] T013 [P] [US1] Jest: `perego-site/src/Blocks/site-header/view.test.js` — sticky class toggles
      past 20px scroll; mobile menu opens/traps focus/closes on Esc+backdrop+link-click and restores
      focus to the hamburger; desktop dropdown opens on hover/focus-within, mobile on tap-accordion

### Implementation for User Story 1

- [ ] T014 [US1] `perego-site/src/Blocks/site-header/block.json` + `render.php` — server-rendered
      header markup (logo, nav, CTA, hamburger placeholder, language-toggle placeholder consuming
      `LanguageService`), depends on T007
- [ ] T015 [US1] `perego-site/src/Blocks/site-header/view.js` — Interactivity API store: sticky-scroll
      state, mobile-menu open/close + focus trap + scroll-lock, desktop dropdown hover/focus-within,
      mobile dropdown tap-accordion (depends on T014)
- [ ] T016 [US1] `perego-site/src/Blocks/site-header/style.scss` — tokens only, logical properties,
      `--perego-*`/`--wp--preset--*` (depends on T014)
- [ ] T017 [US1] `perego-site/src/Blocks/site-footer/block.json` + `render.php` + `style.scss` —
      3-column layout + bottom bar (form wiring itself is M4 — this renders the structural entry
      points only, per spec Assumptions)
- [ ] T018 [US1] `perego-theme/parts/header.html` → `<!-- wp:perego/site-header /-->`
- [ ] T019 [US1] `perego-theme/parts/footer.html` → `<!-- wp:perego/site-footer /-->`
- [ ] T020 [US1] `perego-theme/templates/index.html` — header part + `wp:post-content` + footer part
      (already close to this shape from scaffolding; verify/adjust)
- [ ] T021 [US1] Run `wp-guard` + `clean-code-guard` on the diff so far; fix findings
- [ ] T022 [US1] Run Pest (T011, T012) + Jest (T013); confirm green

**Checkpoint**: US1 fully functional and independently testable — every template shares one
token-driven header/footer.

---

## Phase 4: User Story 2 — Bilingual EN/AR (Priority: P1) 🎯 MVP

**Goal**: language toggle flips `lang`/`dir`, swaps font stack, mirrors layout, persists choice.

**Independent Test**: toggle language; `<html lang>`/`dir` flip and persist across reload; Arabic uses
Cairo/Tajawal; layout mirrors with no breakage.

### Tests for User Story 2

- [ ] T023 [P] [US2] Jest: `perego-site/src/Blocks/site-header/language-toggle.test.js` — clicking the
      toggle updates `document.documentElement.lang`/`dir`, persists to the fallback driver's
      mechanism, re-applies on script re-init (simulated reload)

### Implementation for User Story 2

- [ ] T024 [US2] Wire the language-toggle control markup into `site-header/render.php` (depends on
      T014, T007)
- [ ] T025 [US2] Extend `site-header/view.js`'s Interactivity store with the language-toggle directive
      (depends on T015, T023)
- [ ] T026 [US2] `:root:lang(ar)` font-stack swap already present in `main.scss` (T002) — verify it
      covers the header/footer blocks too (no separate override needed, since they consume the same
      `--wp--preset--font-family--latin` alias)
- [ ] T027 [US2] Attempt installing Polylang (`wp plugin install polylang --activate`); if it
      succeeds, add a thin integration check that `PolylangLanguageDriver` is the one actually resolved
      by `LanguageService` in that environment; if installation isn't possible here (no
      network/registry access), document that in `DECISIONS.md` and confirm the fallback driver alone
      satisfies every acceptance scenario in spec.md
- [ ] T028 [US2] Run `wp-guard` + `clean-code-guard`; fix findings
- [ ] T029 [US2] Run Jest (T023) + re-run T013; confirm green; manual check against
      `_design_handoff/.../screenshots/02-home-rtl-layout.png` for mirroring fidelity

**Checkpoint**: US1 + US2 both independently functional.

---

## Phase 5: User Story 3 — Branded first-visit preloader (Priority: P3)

**Goal**: homepage-only, first-visit-per-session, reduced-motion-safe branded preload.

**Independent Test**: clear session storage, load homepage — preloader shows, clears ≤2.5s; reload —
does not show again; with `prefers-reduced-motion` — never shows.

### Tests for User Story 3

- [ ] T030 [P] [US3] Pest: `perego-site/tests/Blocks/PreloaderRenderTest.php` — render only emits
      markup when the block is placed on `front-page` context (or: always renders markup but the
      *display* gating is JS-only, per spec Edge Cases — decide in T031 and reflect the test
      accordingly)
- [ ] T031 [P] [US3] Jest: `perego-site/src/Blocks/preloader/view.test.js` — shows only when
      `sessionStorage['perego-preloaded']` is unset; sets it after first show; hides ~0.9s after
      mount; hard-hides at 2.5s regardless; never shows when `matchMedia('(prefers-reduced-motion:
      reduce)').matches`

### Implementation for User Story 3

- [ ] T032 [US3] `perego-site/src/Blocks/preloader/block.json` + `render.php` + `style.scss`
- [ ] T033 [US3] `perego-site/src/Blocks/preloader/view.js` — Interactivity API store implementing the
      session-gate/timing/reduced-motion rules (depends on T032, T031)
- [ ] T034 [US3] `perego-theme/templates/front-page.html` — header part + `<!-- wp:perego/preloader
      /-->` + `wp:post-content` + footer part (only template that includes the preloader block)
- [ ] T035 [US3] Run `wp-guard` + `clean-code-guard`; fix findings
- [ ] T036 [US3] Run Pest (T030) + Jest (T031); confirm green

**Checkpoint**: all three user stories independently functional.

---

## Phase 6: Polish & Cross-Cutting

- [ ] T037 [P] `docs-guard` on `spec.md`/`plan.md`/`tasks.md` and any README touched
- [ ] T038 Update `sites/perego/PROGRESS.md` (what shipped) and `sites/perego/DECISIONS.md` (Polylang
      outcome from T027, any other non-trivial call made during implementation)
- [ ] T039 Full Pest + Jest suite run for `perego-site`/`perego-theme`; confirm all green together
      (not just per-story)
- [ ] T040 Manual visual/behavioral comparison against `_design_handoff/.../screenshots/*.png` and
      `site/index.html` for the header/footer/preloader specifically (spec SC-001, SC-004)
- [ ] T041 Use `superpowers:finishing-a-development-branch`-equivalent close-out: confirm the branch is
      ready for a PR (this session pushes nothing without explicit confirmation, per operating policy)

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
