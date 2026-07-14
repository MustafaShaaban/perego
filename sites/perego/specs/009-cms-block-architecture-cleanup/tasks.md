# Spec 009 — Tasks

One feature = one branch = one PR. Mark boxes truthfully; runtime-gated tasks stay open until actually run.

## Phase 0 — Foundation (no runtime needed)

- [x] T001 Static inventory of Global Sections code, roles, seed, consumers, translatable wiring.
  - Recorded in `docs/final-completion-roadmap.md` §"Global Sections inventory". Key finding: only
    `footer-careers` is frontend-consumed; the other 6 roles are seeded-but-unconsumed; real global copy
    renders from PHP (`SiteHeaderRenderer`/`SiteFooterRenderer`/`GlobalContent`).
- [x] T002 Static inventory of FSE templates (13) and parts (3). Recorded in the roadmap.
- [x] T003 Author durable roadmap + spec/plan/tasks for spec 009.
- [x] T004 Implement `scripts/migrate-global-sections.php` dry-run (report-only, zero writes): per-record
    `{id, role, locale, status, title, consumed?}`, role classification, `footer-careers` presence check,
    anomaly detection, timestamped JSON report to `scripts/output/`. PHP-lint clean. **Run against the
    local WAMP DB: 14 records, 0 anomalies, footer-careers EN/AR present** — see
    `evidence/global-sections-inventory.md` (Run 1). `apply` intentionally refuses (deferred to T010).
- [ ] T005 Design + build the `footer-careers` editable FSE surface (Option A) so the footer "Join us"
    editorial is editable in the Site Editor, EN/AR-aware, with a meaningful preview. Add a Pest render
    test for the new surface. **Do not yet remove the `global-section` dependency.**

## Phase 1 — Runtime migration (BLOCKED on live WP + DB backup)

- [ ] T006 Freeze deployment identity: fingerprint the commit/assets the ngrok site serves; record in
    `PROGRESS.md` §RESUME HERE. (Runtime.)
- [ ] T007 Run the dry-run against the **live** ngrok DB (Run 2 in `evidence/global-sections-inventory.md`);
    reconcile with the local Run 1; resolve any anomaly before proceeding. (Runtime — live access needed.)
- [ ] T008 DB backup/export gate: create + verify a fresh export; `--backup-check` passes. (Runtime.)
- [ ] T009 Switch `SiteFooterRenderer::careersEditorial()` to render from the new editable surface; verify
    live EN/AR footer editorial unchanged; drop the `global-section` block dependency. (Runtime verify.)
- [ ] T010 Run `--apply`; confirm removal of `perego_section` posts/meta/translation relations; emit final
    orphan report; re-run `--apply` to prove idempotent no-op. (Runtime.)

## Phase 2 — Code removal + cleanup (partly runtime-verified)

- [ ] T011 Remove Global Sections code: `GlobalSectionPostType`, `GlobalSectionResolver`,
    `GlobalSectionRenderer`, `Blocks/global-section/*`, `scripts/seed-global-sections.php`, the 3 tests,
    provider `registerGlobalSections()` + the `perego_section` translatable entry.
- [ ] T012 Evidence-based dead-asset sweep (duplicate CSS, orphaned block styles, stale selectors) —
    inventory first, remove only proven-dead; note candidates already flagged in
    `specs/008-.../tasks.md` (e.g. orphaned `site-footer/style.scss`).
- [ ] T013 Write `docs/cleanup-report.md` (removed file/symbol/DB entity, why, replacement, verification,
    rollback).
- [ ] T014 Prove FSE editability of the migrated global areas (Site-Editor screenshots, no credentials).

## Phase 3 — Verification + delivery

- [ ] T015 Full suite green: Pest, Jest, route-health, a11y, interactions; `git diff --check`; builds.
- [ ] T016 Live verify: no Global Sections menu, no `perego_section` type, no `global-section` block, no
    PHP notices, footer editorial intact EN/AR.
- [ ] T017 Update `PROGRESS.md`, `DECISIONS.md`, roadmap; open PR with the required description sections.

## Acceptance (mirror of spec.md — close only when all true and proven)

- [ ] No Global Sections menu · no `perego_section` type · no `global-section` block.
- [ ] No EN/AR frontend content loss; footer-careers now editable.
- [ ] Header/footer/CTA/404 editable from FSE (foundation).
- [ ] Migration idempotent; dry-run + applied reports exist.
- [ ] No PHP/JS errors; suites green.
- [ ] Rollback documented.
