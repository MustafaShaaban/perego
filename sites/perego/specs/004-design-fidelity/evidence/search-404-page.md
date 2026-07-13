# Search / 404 / Page — visual-acceptance evidence (US2 / T016)

**References**: `search.html`, `404.html`, `page.html` in the handoff `site/`.

## Search (`/?s=<q>` → `search.html` / `SearchResultsRenderer`)

**Live**: EN `http://perego.local/?s=video` · AR `http://perego.local/ar/?s=video`

| # | Section | Handoff | Live (before) | Disposition |
| --- | --- | --- | --- | --- |
| 1 | Results layout | 3-column grid of result **cards** (featured image, kicker tag, title, excerpt, date) | A plain `<ul>` text list (title link + excerpt) | **Resolved** — `SearchResultsRenderer` now renders each result with the shared `.post-card` / `.blog-grid` family (same accepted component as the work/journal archives); featured image when present, a `.post-card__media-placeholder` otherwise |
| 2 | Kicker tag | Short category kicker above each title | None | **Resolved** — `kicker()` uses the post's first category term, falling back to the post-type singular label (Service/Page/Journal/Project) for non-post results |
| 3 | Breadcrumb | "Home / Search" above the H1 | None | **Resolved** — added a locale-aware `.page-crumb` (uses `GlobalContent::uiHome()` + the search H1) |
| 4 | Pagination | Prev / 1 / 2 / Next | None | **Resolved** — `paginate_links` pills, rendered only when results exceed one page (per-page dropped 20→12); with the current demo content there is one page, so it correctly does not render |
| 5 | Page title | Large centered H1 | 16px (the site-wide preset bug) | **Resolved** by the h1 preset fix — see [legal.md](./legal.md) |
| 6 | (recurring) Background | Full-bleed dark canvas | White gap under short results | **Resolved** — `background: bg-deep` on `.search-page`; widened the container to content width so the 3-col grid fits, header block centered |

Dead CSS from the old list markup (`.search-page__list`, `.search-result__*`) was removed.

## 404 (unknown route → `404.html` / `NotFoundRenderer`)

**Live**: EN `http://perego.local/this-route-does-not-exist/` · AR `/ar/this-route-does-not-exist/`

**Design-complete, no change needed** — large accent "404", H1 "This page took a creative detour",
description, and the two CTAs ("Back to Home" accent + "Contact Us" dark) all match the handoff. The
`.perego-btn` class bug here was already fixed in the services slice (T014). Background fills; footer
styled.

## Page (representative standard page → `page.html`)

**Live**: EN `http://perego.local/sample-page/`

**Template design-complete** — H1 + constrained prose on the dark canvas, styled footer, background
fills, correct heading size (after the h1 preset fix). The `page.html` template renders any standard
page faithfully to the handoff's generic-page structure.

**Launch-content note**: the live `sample-page` still holds WordPress's default "Sample Page"
placeholder copy (bike-messenger / XYZ Doohickey / "your dashboard" link). This is WP seed content,
not a design defect — a launch cleanup item for Phase 5 (same category as the trashed "Hello world!"
post). No AR translation exists for it (a `[GAP]` the route-health check already reports as content,
not a defect).

## Verification

72-check route-health (0 fails), 12-page a11y (0 serious/critical, incl. search en), 4/4 interactions,
195 Pest. EN + AR captures confirm RTL mirroring on search + 404.
