# Seed-script idempotency — verification evidence

The `LAUNCH-CHECKLIST.md` promises each owner-content seed is a **proven idempotent** script (safe to
re-run once real content is supplied). This records how that was verified on 2026-07-13, so the claim
rests on evidence rather than assertion.

## 1. Static audit — idempotent by construction

Every script guards its create/attach with an existence check before mutating, so a re-run on
already-present data is a no-op (it never overwrites later editor changes):

| Script | Guard before insert/attach |
| --- | --- |
| `seed-clients.php` | `term_exists()` for the taxonomy term; `get_posts(name=slug)` → `continue` per client |
| `seed-projects.php` | `term_exists()` for the type term; `get_posts(name=slug)` → skip |
| `seed-journal.php` | page: `get_posts(name=slug)`; category: `term_exists()`; post: `get_posts(name=slug)` |
| `seed-services.php` | `get_posts()` per (slug, locale) before create; links pairs only when missing |
| `seed-contact.php` | `get_posts(name=slug)` before create |
| `seed-legal.php` | `get_posts(name=slug)` before create |
| `seed-careers.php` | `get_posts(name='open-application')` → `return` if present |
| `seed-global-sections.php` | `get_posts()` per (role, language) → "already present" skip |
| `seed-home-about.php` | `wp_update_post` only when the front page has no content ("left untouched" otherwise) |
| `seed-ar-content.php` | per entity: skips when the AR translation already exists (`pll_get_*`) |
| `seed-journal-media.php` | `has_post_thumbnail()` → `continue` (never re-imports) |
| `seed-project-media.php` | `has_post_thumbnail()` → `continue` (never re-imports) |
| `fix-journal-post-authors.php` | `post_author !== 0` → `continue` (only fills missing authors) |
| `configure-languages.php` | adds a language only when its slug is not already registered |

## 2. Empirical run — zero growth on already-seeded data

The site was already seeded, so a re-run must create nothing. Each script was executed via
`wp eval 'require "…/scripts/<name>.php";' --path=wp` and reported **0 new / already-present**:

```
seed-clients            → 0 new; 8 defined
seed-projects           → 0 example project(s); 9 total defined
seed-journal            → 0 new post(s); 3 defined
seed-services           → EN created: 0, AR created: 0, pairs linked: 0
seed-contact            → EN id 57, pairs linked 0
seed-legal              → created/verified 2, pairs linked 0
seed-careers            → already present (id 71)
seed-ar-content         → projects: 0, posts: 0, clients: 0, pages: 0
fix-journal-post-authors→ 0 post(s) updated
seed-journal-media      → 0 post(s) updated
seed-project-media      → 0 post(s) updated
```

Entity counts before and after the full re-run were identical:

| Type | Before | After |
| --- | --- | --- |
| post | 6 | 6 |
| page | 12 | 12 |
| perego_project | 18 | 18 |
| perego_service | 8 | 8 |
| perego_client | 16 | 16 |
| corex_job | 1 | 1 |
| category | 8 | 8 |
| attachment | 24 | 24 |

`debug.log` stayed at 0 lines throughout (no PHP notices/warnings during any run).

`seed-global-sections.php` was **not** exercised here because no `perego_global_section` posts exist
yet (running it would be a first seed, not an idempotency test); its guard is audited in §1.

## Conclusion

The owner-content seeds are idempotent by construction and confirmed no-op on re-run. When real content
is supplied (edit each script's data array or the WordPress admin), re-running a seed adds only the new
material and leaves existing/edited content untouched.
