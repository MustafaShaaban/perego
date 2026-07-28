# T030 — migration idempotency + rollback rehearsal

**Run:** 2026-07-23 · branch `feature/021-fse-visual-editing-ux` · local WAMP (`perego.local`, WP 7.0.2)
**Rollback point:** `wp/db-backup-t030-20260723-183636.sql` (gitignored, 1.48 MB)

## What was exercised

Every migration script the site ships, run **twice** against already-migrated data. Spec 021 introduces
no schema change of its own — its "migrations" are read-time normalisation in the block editor
(`normalizeRepeater`, `normalizeSlides`), which is exercised by the Jest suites, not by a DB pass. The
data-level retrofits are these eight, and the question T030 answers is whether they are still safe to
re-run on a live site after deploy.

| Script | Run 1 | Run 2 |
|---|---|---|
| `migrate-contact-page-template.php` | 0 posts | 0 posts |
| `migrate-global-sections.php` | CPT already removed, nothing to inventory (dry-run default) | same |
| `migrate-header-contact-anchor.php` | 0 links | 0 links |
| `migrate-project-site-meta.php` | 0 posts | 0 posts |
| `migrate-project-video-meta.php` | 0 posts | 0 posts |
| `migrate-service-process-icons.php` | 0 posts | 0 posts |
| `migrate-service-process.php` | 0 posts | 0 posts |
| `migrate-service-whatwedo-media.php` | 0 posts | 0 posts |

`migrate-global-sections.php` stays in its read-only default: `apply` is gated on both `MIGRATE_GS_MODE`
and a verified backup, and the `perego_section` CPT it targets is already gone (spec 009).

## Public output — unchanged by the sweep

Six routes captured before the sweep and re-captured after, compared line-by-line:

| Route | Changed lines |
|---|---|
| `/` · `/ar/` · `/work/` · `/services/video-editing/` · `/journal/` · `/contact/` | **0** each |

A migration that is a no-op in its own report but moves rendered output would be the failure this
catches. Total across all six routes: **0**.

## Rollback rehearsal

Exported → ran the sweep → restored the export → re-verified:

- **Content fingerprint identical across the restore:** 154 published projects, 46 published clients,
  1 stored template part, before and after.
- **All six routes 200** after the restore, and **0 changed lines** against the pre-sweep capture.
- The header `#contact` retrofit is present in the restored database, confirming the backup captured
  the day's data change rather than a pre-migration state.

**Restore command** (mariadb tools are not on PATH by default on this machine):

```bash
export PATH="/c/wamp64/bin/mariadb/mariadb11.3.2/bin:$PATH"
wp db import wp/db-backup-t030-<stamp>.sql --path=wp
```

## Carried forward

Two data changes do **not** travel in git and are not covered by any script — they are listed in
`LAUNCH-CHECKLIST.md` §5 and must be applied per environment:

1. `migrate-header-contact-anchor.php` on each environment's own saved header template part.
2. The eight Arabic demo clients (`…تجريبي`) unpublished by hand on 2026-07-23.
