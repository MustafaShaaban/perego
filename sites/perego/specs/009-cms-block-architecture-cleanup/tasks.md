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

- [x] T011 Removed Global Sections code: `GlobalSectionPostType`, `GlobalSectionResolver`,
    `GlobalSectionRenderer`, `Blocks/global-section/*`, `scripts/seed-global-sections.php`, the 3 tests,
    provider `registerGlobalSections()` + imports + the `perego_section` translatable entry. Inlined the
    CPT constants into the retained migration script. Regenerated `.pot` (0 global-section refs).
    Rebuilt. Verified: home 200, `perego_section` gone, `global-section` block gone, `footer-careers`
    registered, footer editorial intact. Pest 233/233, Jest 76/76.
- [x] T012 Dead-asset sweep: removed the orphaned `site-footer/style.scss` (styled `.perego-footer*`
    classes the renderer never emits — real footer styling is in `perego-reference.scss`; grep-confirmed
    unused) + its `style` key/import; rebuilt; footer stays styled (`class="site-footer"`). No orphaned
    global-section CSS remained. Broader duplicate-CSS/Swiper-selector sweep deferred to spec 018.
- [x] T013 Wrote `docs/cleanup-report.md` (DB entities, code, assets — why/replacement/verification/rollback).
- [x] T014 Proved the `footer-careers` block is a real editor-available editable block (registered with an
    editor script + its 4 bilingual attributes via the block registry; edit UI shows both EN/AR). **Seam:**
    wiring the footer as editable Site-Editor template-part blocks (owner edits on the canvas) is spec 011;
    spec 009 delivers the architecture removal + content preservation + an editable block, not the footer
    canvas rebuild.

## Phase 3 — Verification + delivery

- [x] T015 Pest 233/233, Jest 76/76; `git diff --check` clean; both builds clean. (Route-health/a11y
    Playwright verifiers deferred to the spec-018 full matrix; not required to close 009's DB/code change.)
- [x] T016 Live verify: `perego_section` type gone, `global-section` block gone, footer editorial intact
    EN/AR, home/work/services/contact/journal + AR home all 200, debug.log clean since marker.
- [x] T017 Updated PROGRESS/DECISIONS/roadmap; opened PR #17 (base `feature/008-visual-fidelity-recovery`).

## Acceptance (mirror of spec.md — close only when all true and proven)

- [x] No `perego_section` type · no `global-section` block. (No Global Sections admin menu — CPT unregistered.)
- [x] No EN/AR frontend content loss; footer-careers now a real editable block (byte-identical output).
- [~] Header/footer/CTA/404 editable from FSE — **foundation only**: footer-careers is an editable block;
    full Site-Editor canvas editing of the global shell is spec 011 (documented seam).
- [x] Migration idempotent; dry-run + applied reports exist (gitignored `scripts/output/`).
- [x] No PHP/JS errors; Pest 233/233, Jest 76/76.
- [x] Rollback documented (cleanup report + DB backup + git revert).
