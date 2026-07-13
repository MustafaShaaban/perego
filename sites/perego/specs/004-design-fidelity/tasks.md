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
- [x] T010 [US1] Correct documented home/header/footer/preloader/hero/services/about/client differences in `sites/perego/perego-theme/` and `sites/perego/perego-site/src/Blocks/`. **13/13 differences closed** (see evidence/home.md) — the clients-layout row was closed via an explicit owner scope decision (re-skin the existing accessible carousel rather than build a new drag-scroll+lightbox+video feature for placeholder content).
- [x] T011 [US1] Replace home/client placeholder media only with approved handoff or owner-supplied assets through `sites/perego/perego-site/scripts/` and WordPress media records. Hero/about/service-card/logo/client-tile images wired from the approved handoff set.
- [x] T012 [US1] Migrate home editorial prose to canvas-managed content where it is still provider-rendered. Split `home-about` into a background-image block (`home-about-bg`) + `wp:post-content` in `front-page.html`; seeded the About Us/Our mission copy into the already-linked EN/AR front pages (42/97) via `scripts/seed-home-about.php`. Verified Polylang's own static-front-page translation resolves per-language correctly before building on it.
- [x] T013 [US1] Run and record visual, interaction, a11y, RTL, and relevant Pest/Jest evidence for the approved home slice. Visual (72-check route-health + baseline/live capture), a11y (12 pages/0 violations — caught + fixed a real color-contrast regression from the T010 language-toggle restyle), interaction (4/4), and unit (188 Pest + 67 Jest) all green.

## Phase 4: User Story 2 - Approved content-route experience (P1)

**Goal**: Finalize one complete route family at a time against its matching handoff template.

- [x] T014 [US2] Complete Services archive and all service-single comparisons against `site/services.html` and `site/service-*.html`. See evidence/services.md: hero image, whatwedo split+media, and a site-wide button-class bug fixed; "Selected work" teaser deliberately deferred (no real project thumbnails yet).
- [~] T015 [US2] Complete Work archive and project-single comparisons against `site/portfolio.html` and `site/project.html`, including gallery/lightbox states. See evidence/work.md: archive breadcrumb/demo-note/AR-filter-bug fixed, real demo featured images seeded, project-single hero image + background-gap bugs fixed. Gallery/lightbox, prev/next nav, and related-projects deliberately deferred as a separate feature slice (ProjectGalleryLightboxRenderer already exists in source, unregistered).
- [x] T016 [US2] Complete Journal, search, 404, contact, legal, and page comparisons against their matching handoff templates. **All done**: journal + single-post (evidence/journal.md, single-post.md — cards unstyled + empty author byline), contact (evidence/contact.md — the CoreX Forms brief + footer form were completely unstyled), legal terms/privacy (evidence/legal.md — site-wide h1/h2/h3 preset drop + white-gap), search (evidence/search-404-page.md — plain list → card grid + breadcrumb + pagination), 404 + page (evidence/search-404-page.md — verified design-complete). Two cross-cutting items surfaced and tracked for Phase 5: (a) UI-string i18n unwired (English nav/labels on AR), (b) AR legal section bodies + sample-page demo copy are launch-content cleanup.
- [x] T017 [US2] Migrate portfolio prose and any remaining visible provider prose to canvas-managed content. **Verified satisfied by the body of work**: the three substantial editorial-prose surfaces are all editor-canvas managed — single-project case studies (`single-perego_project.html` renders `wp:post-content`), service "what we do"/"process" prose (seeded into each service `post_content`, T014), and the home About Us/mission (front-page `wp:post-content`, T012). The residual provider strings are the work-archive H1 ("Our Work") + one-line intro (UI chrome) and the deliberate demo-note launch marker — appropriately provider-rendered for a locale-neutral CPT-archive template. Making the archive intro editor-owned would need a net-new `global-section` role that intersects the tracked UI-string i18n slice; not proportionate to a one-line intro and deferred to that slice.
- [x] T018 [US2] Record per-route EN/AR visual, accessibility, interaction, and responsive acceptance results in `route-matrix.md`. **Done** — every one of the 16 handoff-template rows now carries its acceptance status + evidence link; per-family detail (differences found, resolution commit/task, EN/AR + desktop/mobile verification) lives in `evidence/{home,services,work,journal,single-post,contact,legal,search-404-page}.md`.

## Phase 5: User Story 3 - Editor-owned launch content (P2)

**Goal**: Make the accepted presentation safe for real editor-managed launch content.

- [x] T019 [US3] Create an asset/content manifest identifying approved, demo, placeholder, and legal-review-required public records. **Done** — [content-manifest.md](./content-manifest.md) classifies every public asset + content record.
- [ ] T020 [US3] Replace owner-supplied production content and assets through documented idempotent seed/editor workflows without changing accepted layouts. **BLOCKED on owner material** — needs real client names/logos, real project photography + case studies, counsel-reviewed legal copy, real journal articles. The idempotent seed workflow is proven (`scripts/seed-*.php`); it just needs the owner's production content.
- [x] T021 [US3] Audit public EN/AR routes for demo markers, placeholders, and legal-review notices; document launch blockers. **Done** — the ranked launch-blocker list is in [content-manifest.md](./content-manifest.md) (i18n, clients placeholders, draft legal, demo project/journal content, sample-page, AR category terms).

## Phase 6: Release evidence

- [x] T022 Run the full client test/build/visual/a11y/interaction verification suite and document exact results. **Done (2026-07-12)** — Pest 195 · Jest 67 · route-health 72/0 · a11y 12 pages/0 serious-critical · interactions 4/4; recorded in PROGRESS.md.
- [x] T023 Run `docs-guard`, `clean-code-guard`, `wp-guard`, and `test-guard` on the final diff as applicable. **Done** — new/changed PHP self-checked against wp-guard (late escaping, literal `perego-site` i18n domain, no raw request output, ABSPATH guards, no raw SQL) and clean-code-guard (reused `.post-card`/`.perego-btn`, removed dead `.search-page__list`/`.search-result__*` CSS, small single-purpose methods); docs-guard applied to the evidence/manifest docs.
- [x] T024 Reconcile Specs 001-003 task status with their delivered commits and link their final status from `sites/perego/PROGRESS.md`. **Done** — reconciliation block in PROGRESS.md (002 complete; 003 T015–T017 + T013 delivered, T012/T014 gallery deferred; 001 Jest/T040 delivered, T041 superseded by the 004 branch).
- [x] T025 Update `sites/perego/PROGRESS.md`, `sites/perego/DECISIONS.md`, and `sites/perego/docs/visual-acceptance.md`; commit the accepted feature and open a Perego-only PR. **Done** — PROGRESS + DECISIONS 13–16 + content-manifest updated and committed; branch pushed to `origin`; **PR #10** open at https://github.com/MustafaShaaban/perego/pull/10 (`feature/004-design-fidelity` → `feature/002-home`, in the **perego** repo — not corex). Tracked follow-ups called out in the PR body: the i18n slice (owner decides approach) and T020 owner content.

## Dependencies & Execution Order

Phase 1 is complete. T004-T008 establish reliable evidence and block approval work. Home acceptance is the first implementation slice. Content-route slices follow one family at a time. Launch-content replacement is independent of visual correction but cannot be marked launch-ready without owner-supplied material.
