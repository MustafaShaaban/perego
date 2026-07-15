# Global Sections — record inventory (spec 009)

Produced by the read-only `scripts/migrate-global-sections.php` dry run (writes nothing to the DB).
Raw JSON reports land in the gitignored `scripts/output/`; this file is the committed summary.

## Run 1 — LOCAL WAMP DB (`C:\wamp64\www\perego\wp`), 2026-07-14 13:15 UTC

- **Total records:** 14 (7 roles × EN/AR), all `status=publish`.
- **Anomalies:** none (both `footer-careers` locales present; no unclassifiable records; no duplicate
  singletons).

| id | role | locale | status | consumed (frontend) |
|----|------|--------|--------|---------------------|
| 141 | footer-careers | en | publish | **YES** |
| 142 | footer-careers | ar | publish | **YES** |
| 72 | header | en | publish | no |
| 73 | header | ar | publish | no |
| 74 | standard-footer | en | publish | no |
| 75 | standard-footer | ar | publish | no |
| 76 | contact-footer | en | publish | no |
| 77 | contact-footer | ar | publish | no |
| 78 | global-cta | en | publish | no |
| 79 | global-cta | ar | publish | no |
| 80 | contact-details | en | publish | no |
| 81 | contact-details | ar | publish | no |
| 82 | not-found | en | publish | no |
| 83 | not-found | ar | publish | no |

**Interpretation:** confirms the static code inventory exactly. Only `footer-careers` (#141/#142) is
rendered on the public frontend (`SiteFooterRenderer::careersEditorial()`); the other six roles are
seeded-but-unconsumed and their canonical copy already renders from PHP providers, so their removal causes
no frontend content loss. The one migration target required before removal is the `footer-careers` EN/AR
editorial.

## Runtime resolver (2026-07-14)

Canonical runtime = local WAMP `http://perego.local/` (200; siteurl/home=perego.local — the DB wp-cli
manages). ngrok `mower-hamstring-baggy.ngrok-free.dev` (200) is an optional mirror serving ngrok-domain
URLs. DB inventory/dry-run/backup/migration run via wp-cli against `wp/`. Never stop if ngrok is offline;
do not change WP home/siteurl. Per DATABASE/CLEANUP guidance, the WAMP DB behind perego.local IS the
migration target — so **Run 1 (local) is the authoritative inventory**, not a placeholder.

## Migration applied — 2026-07-14 13:47 UTC (local WAMP)

- **Backup gate:** `wp db export` → `scripts/output/db-backup-20260714-134449.sql` (1,557,421 bytes,
  gitignored); `backup-check` passed. This is the rollback artifact.
- **footer-careers migrated first** (T005/T009): the `perego-theme/footer-careers` block renders the
  EN/AR editorial byte-identically (verified live perego.local + ngrok) before any deletion.
- **Apply result:** removed **14** `perego_section` records (all 7 roles × EN/AR, incl. #141/#142
  footer-careers). Report: `scripts/output/global-sections-apply-20260714-134716.json`.
- **Post-conditions verified:**
  - `wp post list --post_type=perego_section --format=count` → **0**
  - orphaned `_perego_section_role` meta rows → **0**
  - orphaned (count=0) `post_translations` groups → **0** (Polylang cleaned on delete)
  - `language` terms → en=30, ar=29 (live content only; unaffected)
  - Footer "Join us" editorial still renders identically EN + AR (content now from block defaults).
- **Idempotency:** a second `apply` removed **0** records (no-op).
- **ngrok == local fingerprint:** the newly-built `footer-careers` block appeared on both perego.local
  and the ngrok mirror, and both reflect the post-migration state — confirming ngrok tunnels to this same
  WAMP install/DB.

## Run 2 — ngrok mirror cross-check — optional (not required to proceed)

The authoritative deployment is the ngrok site, whose deployed-commit identity is not yet proven and whose
DB may differ from local (owner edits, duplicates). Before any destructive apply:
1. Fingerprint the deployed build (spec 009 T006).
2. Re-run this dry run against the live DB and record the result here as Run 2.
3. Resolve any anomaly the live run surfaces.
