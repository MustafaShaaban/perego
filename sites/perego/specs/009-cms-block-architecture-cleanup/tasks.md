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
- [x] T005 Built the bilingual `perego-theme/footer-careers` block (EN/AR variants in block attributes;
    editor shows both languages via RichText — no "edit elsewhere" placeholder; frontend renders only the
    current language). `FooterCareersRenderer` reconstructs the exact core heading/paragraph markup the
    record held and `do_blocks` it → **byte-identical output**. Switched
    `SiteFooterRenderer::careersEditorial()` onto it (T009 folded in). Built (`build/Blocks/footer-careers`),
    Pest test added. **Verified live EN + AR on perego.local/ngrok — output matches baseline exactly.**
    global-section block still registered (removed in T011). Pest 246/246, Jest 76/76.

## Phase 1 — Migration (target = WAMP DB via wp-cli; runnable locally)

- [x] T006 Freeze target identity: migration target is the WAMP install (`wp/`, siteurl/home=perego.local,
    blogname "Perego Creative Studio"). ngrok is an optional mirror. Recorded in roadmap + RESUME HERE.
- [x] T007 Authoritative dry-run = local WAMP Run 1 (14 records, 0 anomalies) per the goal's DATABASE
    guidance that the WAMP DB behind perego.local is the migration target. ngrok cross-check optional.
- [x] T008 DB backup gate: `wp db export` → gitignored `scripts/output/db-backup-20260714-134449.sql`
    (1.5 MB); `backup-check` passed. Rollback artifact for the migration.
- [x] T009 Switched `SiteFooterRenderer::careersEditorial()` to the `footer-careers` block (done with
    T005). Live EN/AR footer editorial byte-identical. global-section dependency dropped from the footer.
- [x] T010 Ran `apply` (gated on backup + footer-careers block registered): removed 14 `perego_section`
    records; 0 posts / 0 role-meta / 0 orphaned translation groups remain; footer editorial intact EN/AR;
    second `apply` = no-op (idempotent). Evidence in `global-sections-inventory.md`.

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
