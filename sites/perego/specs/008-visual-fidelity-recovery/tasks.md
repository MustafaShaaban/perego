# Tasks: Visual fidelity recovery

- [ ] T001 Create deterministic static-handoff baseline capture and diff infrastructure under `sites/perego/output/visual-recovery/`.
  - [x] Capture the initial static and live EN desktop diagnostic images.
  - [x] Add a deterministic Home EN desktop runner for hero, About, Services, Clients, and footer states; it stores baseline/current/red-pixel-diff PNGs plus an explicitly unreviewed manifest.
  - [ ] Automate normalized baseline/current/diff capture for every route, state, locale, and required viewport.
- [ ] T002 Port `site/css/styles.css` and required handoff assets into the client theme with separate WordPress adapter/editor layers.
  - [x] Preserve the source stylesheet untouched in `perego-reference.scss` and separate the WordPress/editor layers.
  - [x] Map the copied stylesheet's handoff background URLs to the production theme build location without changing their visual values.
  - [ ] Verify every required asset/font is production-safe and referenced by the matching route contract.
- [ ] T003 Audit and correct root/global-style/CoreX interference; record selectors and remediation in `sites/perego/docs/visual-recovery.md`.
- [ ] T004 [US1] Rebuild header/navigation/dropdown/mobile panel and language pills to the reference DOM contract.
  - [x] Align desktop header, nav, dropdown, AR/EN pill order, and handoff class contract.
  - [x] Verify mobile panel, focus trap, dropdown state, and RTL at all required viewports (2026-07-14:
    `verify-interactions.mjs` 9/9 functional + 26 manually reviewed EN/AR captures at every required
    viewport in `docs/visual-recovery.md`; two real contrast/role a11y bugs found and fixed along the way).
    This closes the shared-shell header/nav slice; still open across the rest of T004/T005: footer
    field/error/loading/success form-state screenshots and the contact-page flat-footer layout.
- [ ] T005 [US1] Rebuild shared buttons, fields, form states, standard footer, and flat footer to the reference contract.
  - [x] Align the shared footer and form wrappers while preserving the CoreX/careers submit pipelines.
  - [ ] Verify every field/error/loading/success state and contact-page flat-footer layout visually.
- [ ] T006 [US2] Rebuild Home and Services routes, including desktop/mobile/RTL states and visual evidence.
- [ ] T007 [US2] Rebuild Work/project and Journal/single-post routes, including cards, filters, gallery, and visual evidence.
- [ ] T008 [US2] Rebuild Contact, Search, standard page, legal, and 404 routes with all required states and evidence.
- [ ] T009 [US3] Verify content remains FSE/editor-canvas managed and Polylang Free EN/AR behavior remains linked and RTL-correct.
- [ ] T010 Complete state/viewport visual regression, a11y, performance, security, quality gates, docs, PR, and merge.
