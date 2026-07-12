# Journal (archive) — visual-acceptance evidence (US2 / T016)

**Reference**: `_design_handoff/Perego-Creative-Studio-Final-Handoff/site/archive.html`
**Live**: EN `http://perego.local/journal/` · AR `http://perego.local/ar/المدونة/`

## Material differences found and resolved

| # | Section | Handoff | Live (before) | Disposition |
| --- | --- | --- | --- | --- |
| 1 | Breadcrumb | "Home / Journal" above the H1 | Not rendered | **Resolved** — added to `JournalHeaderRenderer` using the shared `.page-crumb` class; added `GlobalContent::uiHome()` (mirrors `PortfolioContent::uiHome()`) |
| 2 | Card styling | `.blog-grid` of styled `.post-card`s (media, category, title, excerpt) | **Completely unstyled** — the native WP query-loop blocks (`wp:post-featured-image`, `wp:post-terms`, etc.) used theme-default class names (`journal-card`, `journal-grid`) that had **zero CSS anywhere** — plain text, no grid, no card treatment at all | **Resolved** — restructured `home.html`'s query-loop template to use the same `.post-card`/`.post-card__media`/`.post-card__body`/`.post-card__cat`/`.post-card__title`/`.post-card__excerpt` classNames already built and accepted for the work archive, and moved that CSS family from the portfolio-grid block's own stylesheet into the shared `main.scss` (native blocks don't load any custom block's stylesheet) |
| 3 | (recurring pattern) Section background | Full-bleed dark canvas | The archive `<main>`/query wrapper had no background of its own | **Resolved** — added `.journal-archive`/`.journal-archive__inner` background, same defensive pattern applied to every route so far |
| 4 | Featured images | Real project stills reused as post thumbnails | No featured images on any of the 6 EN+AR journal posts | **Resolved** — new idempotent `scripts/seed-journal-media.php`, same approach as the work archive's `seed-project-media.php` |
| 5 | (real content-quality issue, unrelated to the handoff) Default "Hello world!" post | — | WordPress's own auto-generated seed post was published and appearing in the real journal listing | **Resolved** — trashed (reversible, not permanently deleted) |
| 6 | Category labels in Arabic | N/A (handoff has no i18n) | AR journal cards show their category badge in **English** ("STUDIO NOTES", "CRAFT") even on `/ar/` | **Open, tracked** — these are WordPress core `category` taxonomy terms, not Perego-controlled; translating them is a Polylang admin/content task (term translation), not a code fix. Minor, same spirit as other tracked AR-content gaps. |
| 7 | A11y regression (caught by the gate) | — | Fixing the breadcrumb introduced a `span[aria-current="page"]` contrast failure: the shared `.page-crumb span { opacity: 0.5 }` rule (meant only for the "/" separator) also dimmed the current-page label | **Resolved** — scoped the dimming to `span[aria-hidden]` only; the current-page span gets full-contrast text color |

Re-verified: **72-check route-health, 12-page a11y (0 violations), 195 Pest** all green. EN + AR + mobile
captures confirm correct RTL mirroring.

## Single journal post

Done in the same pass — see [single-post.md](./single-post.md). Same gap pattern (native post blocks
had zero CSS), plus a real data bug (all posts had `post_author = 0`, so the byline silently rendered
nothing).
