# Tasks: Lighthouse Client Performance

**Input**: Design documents from
`sites/perego/specs/026-lighthouse-client-performance/`  
**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`,
`quickstart.md`

## Phase 1: Setup

- [x] T001 Create spec 026 artifacts and requirements checklist in
  `sites/perego/specs/026-lighthouse-client-performance/`
- [x] T002 Classify supplied Lighthouse findings by ownership in
  `sites/perego/specs/026-lighthouse-client-performance/research.md`
- [x] T003 Confirm branch, client-only ownership boundary, and CoreX media API
  contract

## Phase 2: Foundational Tests and Asset Contract

- [x] T004 [P] Add image and intrinsic-dimension assertions to affected renderer
  tests in `sites/perego/perego-site/tests/Blocks/`
- [x] T005 [P] Add native-role regression assertions to
  `sites/perego/perego-site/tests/Blocks/ClientsCarouselRenderTest.php`
- [x] T006 Add deterministic image-output verification in
  `sites/perego/perego-theme/scripts/verify-theme-images.mjs`
- [x] T007 Run the focused tests and record the expected pre-fix failures

## Phase 3: User Story 1 - Efficient Homepage Media

**Goal**: Modern browsers receive materially smaller WebP media with valid
fallbacks.

**Independent Test**: Build the theme, verify dimensions and byte budgets, and
inspect selected network response formats in a clean browser.

- [x] T008 [US1] Add missing canonical image inputs under
  `sites/perego/perego-theme/assets/src/images/`
- [x] T009 [US1] Make
  `sites/perego/perego-theme/scripts/optimize-images.mjs` deterministic and
  compression-aware
- [x] T010 [US1] Rebuild tracked image artifacts under
  `sites/perego/perego-theme/assets/images/`
- [x] T011 [P] [US1] Use the CoreX media helper in affected Perego PHP renderers
  under `sites/perego/perego-site/src/Blocks/`
- [x] T012 [P] [US1] Add equivalent WebP/fallback markup to affected static
  templates under `sites/perego/perego-theme/templates/`
- [x] T013 [US1] Add modern background-image selection to the editable Perego
  adapter stylesheet without changing the immutable reference stylesheet

## Phase 4: User Story 2 - Stable and Accessible Content

**Goal**: Target media has intrinsic dimensions and carousel controls expose
valid native semantics.

**Independent Test**: Run renderer tests and inspect the clean accessibility
tree.

- [x] T014 [US2] Add accurate logo and audited-image width/height attributes in
  Perego renderers and templates
- [x] T015 [US2] Preserve custom-logo intrinsic dimensions through WordPress
  attachment metadata
- [x] T016 [US2] Replace incompatible carousel list/list-item role overrides
  with valid grouping semantics
- [x] T017 [US2] Verify eager/lazy behavior for above- and below-the-fold media

## Phase 5: User Story 3 - Caching and Trustworthy Audits

**Goal**: Perego-owned static assets have a safe repeat-visit policy and audit
results exclude extension noise.

**Independent Test**: Inspect HTTP cache headers and run Lighthouse in a clean
profile.

- [x] T018 [US3] Add a finite static-asset cache policy in
  `sites/perego/perego-theme/.htaccess`
- [x] T019 [US3] Bump the Perego theme version in
  `sites/perego/perego-theme/style.css` and `package.json`
- [x] T020 [US3] Verify local static response headers and document the
  production CDN/host equivalent if required
- [x] T021 [US3] Run an extension-free Lighthouse audit and classify any
  remaining first-party findings

## Phase 6: Polish and Cross-Cutting Verification

- [x] T022 Run the full Perego PHP, JavaScript, build, and reference-sync suites
- [x] T023 Run EN/AR Playwright checks at 375, 768, and 1440 CSS pixels
- [x] T024 Compare target image byte totals with the supplied baseline and
  verify SC-001 through SC-007
- [x] T025 Run clean-code, WordPress, test, and documentation guards on the diff
- [x] T026 Update `sites/perego/PROGRESS.md` and
  `sites/perego/DECISIONS.md` with verification and deployment notes
- [x] T027 Raise hero gradient fidelity without breaking the image budget, then
  visually verify the rebuilt full-screen asset
- [x] T028 Bust the previously cached low-quality hero URL with a Perego-owned
  versioned filename and verify a clean browser requests the new asset

## Dependencies & Execution Order

- Phase 2 must complete before production implementation.
- T008 and T009 precede T010.
- T011 through T013 depend on T010 because modern siblings must exist.
- T014 through T017 depend on T004 and T005.
- T020 depends on T018; T021 depends on all production changes and a complete
  build.
- Phase 6 begins only after all three user stories pass independently.

## Parallel Opportunities

- T004 and T005 touch separate test concerns and can be prepared together.
- T011 and T012 affect PHP renderers and static templates independently.
- T013 can proceed independently after optimized image filenames are fixed.
- The parent agent remains the sole writer; `[P]` indicates independent file
  scopes, not concurrent writers.
