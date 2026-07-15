# Spec 009 — Implementation Plan

## Approach

Migration-first, removal-last, dry-run-gated. Nothing is deleted until the migration is proven idempotent
against the **live** database behind a backup gate, and the one consumed role (`footer-careers`) has a new
editable home verified on the frontend.

## Target architecture for the migrated content

The footer already renders through the server-side `perego-theme/site-footer` block (`SiteFooterRenderer`),
composed from `PeregoSite\Content\GlobalContent`. Two viable homes for the `footer-careers` editorial:

- **Option A (chosen): editor-canvas block inside the footer template part.** Add the careers editorial as
  real block content (heading + paragraph) that the `footer` template part renders, EN/AR-aware. This is
  the most block-first and matches the "meaningful Site-Editor preview" requirement. Keeps prose off PHP.
- Option B: keep it in `GlobalContent` (PHP, bilingual) like the rest of the footer. Rejected — it does
  not satisfy the FSE-editability requirement and just moves the hard-coded-prose problem.

The remaining six roles are unconsumed; their canonical copy already lives in `GlobalContent` /
`SiteHeaderRenderer` / templates. Full FSE-editability of header/footer/CTA/404 (FR-6) is delivered as the
foundation here and completed visually in specs 011/017. **Decision:** for spec 009 the FR-6 bar is "the
editable surface exists and previews in FSE"; pixel fidelity is 011/017.

## Migration tool design (`scripts/migrate-global-sections.php`)

A CLI script (`wp eval-file`) with three modes, idempotent, read-live-DB:

1. `--dry-run` (default, **zero writes**): query all `perego_section` posts (any status), for each record
   emit `{id, role, locale (pll_get_post_language), status, title, consumed?}`; classify roles; confirm
   `footer-careers` EN/AR present; write a timestamped report to `scripts/output/` and a human summary to
   `evidence/global-sections-inventory.md`. Refuse to proceed to apply if any anomaly (missing
   `footer-careers` locale, unexpected role, duplicate singleton).
2. `--backup-check`: verify a fresh DB export exists and is non-empty (path passed in / documented);
   **hard-fail** if absent. Gate for `--apply`.
3. `--apply`: only after backup-check passes and the new footer-careers editable surface is live — remove
   the `perego_section` posts, their `_perego_section_role` meta, and Polylang translation relations;
   emit a final orphan report. Idempotent: a second `--apply` finds nothing and makes no change.

Removal of **code** (classes/block/tests/provider wiring/seed) is a separate, reviewable commit done only
after `--apply` succeeds and the suite is green.

## Files to change / add

- **Add:** `scripts/migrate-global-sections.php`; `evidence/global-sections-inventory.md`;
  `docs/cleanup-report.md`; the footer-careers editable surface (block content in `parts/footer.html` or a
  small `GlobalContent`→canvas move — finalise during implementation).
- **Modify:** `src/Blocks/SiteFooterRenderer.php` (render careers editorial from the new surface, drop the
  `global-section` dependency); `src/PeregoSiteServiceProvider.php` (remove `registerGlobalSections()` +
  the `perego_section` translatable entry).
- **Remove (after `--apply`):** `src/PostTypes/GlobalSectionPostType.php`,
  `src/Content/GlobalSectionResolver.php`, `src/Blocks/GlobalSectionRenderer.php`,
  `src/Blocks/global-section/*`, `scripts/seed-global-sections.php`, the three Global Sections tests.

## Verification

- Pest (drop obsolete tests; add a footer-careers render test for the new surface), Jest, route-health,
  a11y, interactions.
- Live: `/` and `/ar/` footer still show the "Join us"/"انضم إلينا" editorial; no `perego_section` in
  `wp post-type list`; no Global Sections admin menu; no PHP notices in `debug.log`.
- Idempotency: run `--dry-run` then `--apply` then `--apply` again → second apply is a no-op.

## Rollback

- Restore the pre-migration DB export (backup gate guarantees it exists).
- `git revert` the removal commits (code is recoverable from history).
- Re-run `seed-global-sections.php` (still idempotent) if the CPT is temporarily restored.

## Sequencing

Author (done) → dry-run tool → **run dry-run live behind backup gate** → build+verify footer-careers
editable surface → `--apply` → remove code → cleanup report → full suite + live verify → PR.

## Runtime dependency

Steps from "run dry-run live" onward require live WordPress + DB access (ngrok admin / local WAMP) and a DB
backup. These are **blocked until runtime access + backup are confirmed** — do not fabricate their results.
