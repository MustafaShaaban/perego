# Perego — Cleanup Report

> Evidence-based record of removed code, assets, and database entities. Inventory first; delete only
> proven-dead. Each entry: what · why obsolete · replacement/migration · verification · rollback.
> Started 2026-07-14 (spec 009). Append future cleanups here.

## Spec 009 — Global Sections architecture removal (2026-07-14)

### Database entities removed

| Entity | Why obsolete | Migration/replacement | Verification | Rollback |
|--------|--------------|-----------------------|--------------|----------|
| 14 `perego_section` posts (7 roles × EN/AR) | Hidden CPT for global copy the block-first rule forbids; only `footer-careers` was ever rendered, the other 6 roles were seeded-but-unconsumed | `footer-careers` → `perego-theme/footer-careers` block; other roles' copy already lived in PHP providers | `wp post list --post_type=perego_section --format=count` → 0; footer editorial byte-identical EN/AR | DB export `db-backup-20260714-134449.sql`; re-run migration reverted from backup |
| `_perego_section_role` post meta (×14) | Removed with their posts (`wp_delete_post` force) | n/a | `SELECT COUNT(*) … meta_key='_perego_section_role'` → 0 | via DB backup |
| Polylang translation relations for the pairs | Removed on post deletion | n/a | count=0 `post_translations` orphan groups → 0; `language` terms en=30/ar=29 (live) | via DB backup |

Migration tool (retained): `perego-site/scripts/migrate-global-sections.php` — `dry-run` / `backup-check`
/ `apply` (idempotent; gated on a verified backup + the footer-careers block). Reports in gitignored
`scripts/output/`. Evidence: `specs/009-cms-block-architecture-cleanup/evidence/global-sections-inventory.md`.

### Code removed

| File / symbol | Why obsolete | Replacement | Verification |
|---------------|--------------|-------------|--------------|
| `src/PostTypes/GlobalSectionPostType.php` | CPT registration removed | — | `post_type_exists('perego_section')` → false |
| `src/Content/GlobalSectionResolver.php` | role+locale resolver for the CPT | — | Pest green (233) |
| `src/Blocks/GlobalSectionRenderer.php` | rendered CPT records | `FooterCareersRenderer` | block gone; footer intact |
| `src/Blocks/global-section/{block.json,index.js,style.scss}` | the CPT-backed block (had the forbidden "edit another record" editor placeholder) | `perego-theme/footer-careers` block | `is_registered('perego-theme/global-section')` → false |
| `scripts/seed-global-sections.php` | seeded the removed CPT | — | n/a |
| `tests/{Blocks/GlobalSectionRenderTest,PostTypes/GlobalSectionPostTypeTest,Content/GlobalSectionResolverTest}.php` | tested removed code | `tests/Blocks/FooterCareersRenderTest.php` | Pest 233/233 |
| provider `registerGlobalSections()` + 3 imports + `perego_section` translatable entry | wiring for removed code | `footer-careers` registered in `registerGlobalSurfaces()` | home 200, no fatal |

Rollback: `git revert` the removal commit (`4a5cfdc`); code is recoverable from history. The retained
migration script's constants are inlined, so it still runs post-removal.

### Dead assets removed

| Asset | Why obsolete | Verification | Rollback |
|-------|--------------|--------------|----------|
| `src/Blocks/site-footer/style.scss` (+ its `style` key in `block.json`, `import` in `index.js`) | Styled `.perego-footer` / `.perego-footer--flat` classes the renderer never emits (it emits `.site-footer`, styled by `perego-reference.scss`); flagged in spec 008 as a cleanup candidate; grep-confirmed unused | Rebuilt clean; footer renders `class="site-footer"` and stays styled | `git revert` |

### New editable surface (replacement)

`perego-theme/footer-careers` — bilingual block; EN/AR variants in attributes
(`headingEn/headingAr/blurbEn/blurbAr`); renders only the current language on the frontend; shows both in
the editor (RichText). Registered with an editor script (verified via the block registry). **Note:** the
block is editor-available and editable when inserted; wiring the footer itself as editable Site-Editor
template-part blocks (so an owner edits it on the canvas) is **spec 011** — spec 009 delivers the
architecture removal + content preservation, not the full footer canvas rebuild.

### Known pre-existing i18n warnings (not from this work, candidates for later)

`make-pot` reports two strings lacking a `translators:` comment: `ProjectGalleryLightboxRenderer.php:75`
("Open project media %d") and `ServiceSelectedWorkRenderer.php:64` ("Open %s"). Pre-existing; track for a
later i18n cleanup pass.
