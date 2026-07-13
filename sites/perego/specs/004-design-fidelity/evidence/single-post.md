# Single journal post — visual-acceptance evidence (US2 / T016)

**Reference**: `_design_handoff/Perego-Creative-Studio-Final-Handoff/site/single-post.html`
**Live**: EN `http://perego.local/behind-the-scenes-of-a-brand-film-example/` · AR
`http://perego.local/ar/من-كواليس-فيلم-علامة-تجارية-مثال/`
**Template**: `perego-theme/templates/single.html` — a native-WP-block template (no page-specific PHP
renderer before this pass), same styling-gap pattern as the journal archive.

## Material differences found and resolved

| # | Section | Handoff | Live (before) | Disposition |
| --- | --- | --- | --- | --- |
| 1 | Whole layout | `.post-single` styled article: centered hero header, glass featured image, prose | **Completely unstyled** — the `post-single*` class names on the native post blocks had **zero CSS anywhere** in the codebase (grep confirmed), so the page rendered as plain left-aligned text with no card, no hero, no spacing | **Resolved** — added the `.post-single`/`.post-single__cat`/`.post-single__title`/`.post-single__meta`/featured-image/prose/tags CSS family to shared `main.scss`, porting the handoff's `.post-hero`/`.post-cat`/`.post-title`/`.post-meta`/`.post-featured`/`.post-tag` treatment onto the theme's own tokens (logical properties, RTL-safe) |
| 2 | Breadcrumb | "Home / Journal / Article" above the category pill | Not rendered | **Resolved** — new `perego-theme/post-breadcrumb` server block + `PostBreadcrumbRenderer` (mirrors `JournalHeaderRenderer`); locale-aware via `GlobalContent` so AR shows "الرئيسية / مدونة بيريجو / <عنوان>". `single.html` has no PHP renderer of its own, so a tiny server block was the only way to keep the crumb language-aware rather than hardcoding English into the neutral FSE template |
| 3 | Author byline | "By Mostafa Emam" in the meta row | **Missing entirely** — every journal post had `post_author = 0`, and core's `wp:post-author-name` block early-returns an empty string on an empty author ID, so the byline silently rendered nothing (a real data bug, not a styling gap) | **Resolved** — new idempotent `scripts/fix-journal-post-authors.php` assigns the site's admin account as author on any post with `post_author = 0`; also set the owner's display name to "Mostafa Emam" (the actual site owner per the git author + footer contact emails, and the person named in the handoff byline) so the byline reads correctly |
| 4 | (recurring pattern) Section background | Full-bleed dark canvas | The `.post-single` `<main>` had no background of its own | **Resolved** — explicit `background: var(--wp--preset--color--bg-deep)` on `.post-single`, same defensive pattern applied to every route so far |
| 5 | Featured image | `.post-featured` — max-width, radius, shadow, overflow-clip | Bare full-width `<figure>` | **Resolved** — `.post-single .wp-block-post-featured-image` gets max-inline-size, `--perego-radius-card`, `--perego-shadow-featured`, `overflow:hidden` |
| 6 | Reading time | "· 6 min read" segment in the meta row (`single-post.html:71`; handoff `readTime` string) | Not rendered — native post blocks emit no reading estimate | **Resolved** — new `perego-theme/post-reading-time` server block + `PostReadingTimeRenderer` (mirrors the post-breadcrumb pattern): estimates from the post body at 200 wpm, floored to a 1-minute minimum, unicode-aware word split so Arabic counts correctly. Locale-aware via `GlobalContent::readTime()` — EN "N min read", AR "N دقيقة للقراءة" (handoff `readTime` in `content/{en,ar}.json`). Verified live: EN `1 min read`, AR `1 دقيقة للقراءة` |

## Deliberately deferred (documented, not silently skipped)

- **Meta embellishments** — the handoff meta row also has a round author avatar, a "By" prefix, and a
  separator dot before each segment. The native `wp:post-author-name`/`wp:post-date` blocks don't emit a
  "By" prefix or an avatar, and these are cosmetic; the reading-time estimate (row 6 above) is now
  rendered. The functional, correctly-styled author + date + reading-time row is present.
- **Author-bio card + "Related articles"** — net-new sections (same class as the deferred
  related-projects block on the work single); not required for the core single-post layout and would
  be a separate feature slice.
- **Post tags** — `.post-single__tags` pill styling is in place and will render for any tagged post;
  the demo posts have no tags assigned yet, so nothing renders today (content, not a defect).

## Verification

- **Route-health** (`verify-visual.mjs`): 72 checks, 0 failures — includes EN+AR desktop+mobile
  `single-post`.
- **A11y** (`verify-a11y.mjs`): 12 pages, 0 serious/critical — includes `single post (en)`.
- **Interactions** (`verify-interactions.mjs`): 4/4.
- **Unit** (Pest): 212 passed (601 assertions) — includes `PostReadingTimeRendererTest` (EN/AR label,
  200-wpm estimate, 1-minute floor, Arabic word count, null/empty guards).
- EN + AR + mobile captures confirm correct RTL mirroring (breadcrumb, title, meta, prose all mirror;
  `dir="rtl" lang="ar"` confirmed on the AR route).
