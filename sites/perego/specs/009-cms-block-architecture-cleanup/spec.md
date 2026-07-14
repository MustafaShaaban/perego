# Spec 009 — CMS Block Architecture and Cleanup

**Branch:** `feature/009-cms-block-architecture-cleanup`
**Mode:** Client Site Mode
**Status:** In progress (foundation authored 2026-07-14)
**Depends on:** none (foundation). **Blocks:** 011, 012, 017 (edit the block-first global areas).

## Problem

The Perego global content architecture is wrong. A `perego_section` ("Global Sections") custom post type
was built to hold repeated global copy (header, footers, global CTA, contact details, 404, footer
careers), but:

- Only **one** of its seven seeded roles (`footer-careers`) is actually rendered on the frontend (via
  `SiteFooterRenderer::careersEditorial()`). The other six are seeded-but-unconsumed.
- The real header/footer/CTA/404 copy renders from **PHP providers** (`SiteHeaderRenderer`,
  `SiteFooterRenderer`, `GlobalContent`), not from editable blocks — so global content is **not genuinely
  editable** from the Site Editor, and prose is hard-coded in PHP.
- The CPT is a hidden content store the constitution's block-first rule forbids for global fragments.

## Goal

Replace the Global Sections architecture with **block-first, FSE-editable global content**, remove the
`perego_section` CPT and all its code safely (no frontend content loss, idempotent migration, full
rollback), and remove clearly-dead duplicate assets. Leave the repo and database clean.

## Non-goals (owned by later specs)

- Pixel-level visual completion of the header/footer/CTA/404 shells → **spec 011 / 017**.
- CoreX Forms/Submissions/Data Models runtime → **spec 010**.
- The careers/join *form* itself (this spec only relocates the careers *editorial* heading/blurb).

## User stories

- **US1 (Owner):** I can edit the site's global areas (header, footers, CTA, 404 editorial, footer
  careers editorial) from the Site Editor / block editor and see the change on the frontend — without a
  hidden "Global Sections" admin menu or a "edit another record" placeholder.
- **US2 (Maintainer):** The repository has no obsolete Global Sections code, no orphaned `perego_section`
  records/meta/translation relations, and no dead duplicate styling — with an evidence-based cleanup
  report and a documented rollback path.
- **US3 (Bilingual visitor):** EN and AR global content is preserved exactly; only the current language
  renders in the public DOM.

## Functional requirements

- **FR-1** A safe, idempotent migration with a **dry-run** mode that inventories every EN/AR
  `perego_section` record, classifies each role consumed/unconsumed, and maps `footer-careers` content to
  its new editable home. Re-running produces no duplicate content and no destructive change.
- **FR-2** A **DB backup/export prerequisite gate** must pass before any write/removal.
- **FR-3** `footer-careers` EN/AR editorial is migrated to an FSE-editable surface; the footer renders it
  from that surface, not from the `global-section` block.
- **FR-4** After verified migration: remove `GlobalSectionPostType`, `GlobalSectionResolver`,
  `GlobalSectionRenderer`, the `global-section` block, its provider wiring, the seed script, the obsolete
  tests, the `perego_section` entry in `registerTranslatablePostTypes()`, and the seeded posts/meta/
  translation relations.
- **FR-5** No Global Sections admin menu; no `perego_section` post type; no `global-section` placeholder
  block anywhere.
- **FR-6** Header/footer/CTA/404 editorial content is editable from FSE with a meaningful editor preview
  (foundation delivered here; full visual fidelity in 011/017).
- **FR-7** A cleanup report (`docs/cleanup-report.md`) listing every removed file/symbol/DB entity, why it
  was obsolete, its replacement, verification, and rollback.
- **FR-8** No PHP/JS errors on any route after removal; all suites green.

## Acceptance criteria (from the program contract)

- [ ] No Global Sections menu in wp-admin.
- [ ] No `perego_section` post type registered.
- [ ] No `global-section` placeholder block registered or referenced.
- [ ] No frontend content loss (EN/AR) — `footer-careers` editorial still renders, now editable.
- [ ] Header/footer/CTA/404 content editable from FSE.
- [ ] Migration is idempotent; dry-run and applied reports both exist.
- [ ] Existing EN/AR content preserved.
- [ ] No PHP/JS errors; Pest/Jest/route-health/a11y/interactions all green.
- [ ] Full rollback procedure documented.

## Evidence required

- `evidence/global-sections-inventory.md` (live record dump: id/role/locale/status per record).
- Dry-run report artifact + applied-migration report artifact.
- `docs/cleanup-report.md`.
- Site-Editor screenshots proving each migrated global area is editable (no credentials in captures).
- Full suite results before/after.

## Risks

- **Live-record drift:** the static inventory (14 intended records) may differ from the live DB (owner
  edits, duplicates). The dry-run must read the live DB, not assume the seed.
- **Deployed-commit identity unproven** — verify the live build fingerprint before trusting live state.
- **Overlap with 011/017** on the exact editable surfaces — this spec delivers the *architecture and
  migration*; those specs deliver *visual fidelity*. Keep the seam clean.
