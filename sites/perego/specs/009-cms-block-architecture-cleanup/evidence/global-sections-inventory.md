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

## Run 2 — LIVE ngrok DB — **STILL REQUIRED (not yet run)**

The authoritative deployment is the ngrok site, whose deployed-commit identity is not yet proven and whose
DB may differ from local (owner edits, duplicates). Before any destructive apply:
1. Fingerprint the deployed build (spec 009 T006).
2. Re-run this dry run against the live DB and record the result here as Run 2.
3. Resolve any anomaly the live run surfaces.
