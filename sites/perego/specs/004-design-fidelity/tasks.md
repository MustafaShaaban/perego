# Tasks: Design Fidelity Recovery

**Input**: [spec.md](./spec.md), [plan.md](./plan.md), and `_design_handoff/Perego-Creative-Studio-Final-Handoff/site/`.

**Tests**: Every implementation slice requires the applicable Pest, Jest, Playwright visual/interaction/a11y, RTL, and guard checks before completion.

## Phase 1: Control-plane recovery

- [x] T001 Create the design-fidelity spec, plan, and task register in `sites/perego/specs/004-design-fidelity/`.
- [x] T002 Record the current authoritative status and supersede stale Next blocks in `sites/perego/PROGRESS.md`.
- [x] T003 Record the locked-handoff decision in `sites/perego/DECISIONS.md`.
- [x] T004 Create `sites/perego/specs/004-design-fidelity/route-matrix.md` mapping all 16 handoff templates to Perego routes, languages, states, and owners.
- [x] T005 Create deterministic handoff baselines and an evidence location policy in `sites/perego/specs/004-design-fidelity/quickstart.md`.

## Phase 2: Foundational acceptance gates

- [x] T006 Verify the live WordPress environment and build outputs before visual work; record results in `sites/perego/specs/004-design-fidelity/quickstart.md`.
- [x] T007 Extend `sites/perego/perego-site/scripts/verify-visual.mjs` only as required to enumerate every mapped route/state and preserve current checks.
- [x] T008 Add a visual-difference review procedure to `sites/perego/docs/visual-acceptance.md`.

## Phase 3: User Story 1 - Approved home experience (P1)

**Goal**: Finalize the home page against `site/index.html` and its supplied screenshots.

- [x] T009 [US1] Capture matched EN/AR home baselines and live renders at required breakpoints in the feature evidence directory.
- [~] T010 [US1] Correct documented home/header/footer/preloader/hero/services/about/client differences in `sites/perego/perego-theme/` and `sites/perego/perego-site/src/Blocks/`. **10/13 differences resolved** (see evidence/home.md); open: clients layout + AR content, typography scale.
- [~] T011 [US1] Replace home/client placeholder media only with approved handoff or owner-supplied assets through `sites/perego/perego-site/scripts/` and WordPress media records. Hero/about/service-card/logo images wired from the approved handoff set; client-carousel media still open (depends on row 5).
- [ ] T012 [US1] Migrate home editorial prose to canvas-managed content where it is still provider-rendered.
- [~] T013 [US1] Run and record visual, interaction, a11y, RTL, and relevant Pest/Jest evidence for the approved home slice. Visual (72-check route-health + baseline/live capture) and unit (186 Pest) done; a11y/interaction re-run against the corrected markup still open.

## Phase 4: User Story 2 - Approved content-route experience (P1)

**Goal**: Finalize one complete route family at a time against its matching handoff template.

- [ ] T014 [US2] Complete Services archive and all service-single comparisons against `site/services.html` and `site/service-*.html`.
- [ ] T015 [US2] Complete Work archive and project-single comparisons against `site/portfolio.html` and `site/project.html`, including gallery/lightbox states.
- [ ] T016 [US2] Complete Journal, search, 404, contact, legal, and page comparisons against their matching handoff templates.
- [ ] T017 [US2] Migrate portfolio prose and any remaining visible provider prose to canvas-managed content.
- [ ] T018 [US2] Record per-route EN/AR visual, accessibility, interaction, and responsive acceptance results in `route-matrix.md`.

## Phase 5: User Story 3 - Editor-owned launch content (P2)

**Goal**: Make the accepted presentation safe for real editor-managed launch content.

- [ ] T019 [US3] Create an asset/content manifest identifying approved, demo, placeholder, and legal-review-required public records.
- [ ] T020 [US3] Replace owner-supplied production content and assets through documented idempotent seed/editor workflows without changing accepted layouts.
- [ ] T021 [US3] Audit public EN/AR routes for demo markers, placeholders, and legal-review notices; document launch blockers.

## Phase 6: Release evidence

- [ ] T022 Run the full client test/build/visual/a11y/interaction verification suite and document exact results.
- [ ] T023 Run `docs-guard`, `clean-code-guard`, `wp-guard`, and `test-guard` on the final diff as applicable.
- [ ] T024 Reconcile Specs 001-003 task status with their delivered commits and link their final status from `sites/perego/PROGRESS.md`.
- [ ] T025 Update `sites/perego/PROGRESS.md`, `sites/perego/DECISIONS.md`, and `sites/perego/docs/visual-acceptance.md`; commit the accepted feature and open a Perego-only PR.

## Dependencies & Execution Order

Phase 1 is complete. T004-T008 establish reliable evidence and block approval work. Home acceptance is the first implementation slice. Content-route slices follow one family at a time. Launch-content replacement is independent of visual correction but cannot be marked launch-ready without owner-supplied material.
