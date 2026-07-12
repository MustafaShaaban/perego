# Work (archive + project singles) — visual-acceptance evidence (US2 / T015)

**Reference**: `_design_handoff/Perego-Creative-Studio-Final-Handoff/site/portfolio.html`, `project.html`
**Live**: EN `http://perego.local/work/` · AR `http://perego.local/ar/work/`

## Work archive (`portfolio.html` → `/work/`)

### Material differences found and resolved

| # | Section | Handoff | Live (before) | Disposition |
| --- | --- | --- | --- | --- |
| 1 | Breadcrumb | "Home / Work" above the H1 | Not rendered | **Resolved** — added to `PortfolioGridRenderer` using the shared `.page-crumb` class (moved to `main.scss` — a universal, page-agnostic breadcrumb style, reusable by future routes, not owned by any one block) |
| 2 | Demo-content notice | "Example projects shown below — to be replaced with Perego's real work." | Not rendered | **Resolved** — added `PortfolioContent::gridStrings()['demoNote']`, an honest, accurate transparency note (real seeded demo content, correctly disclosed, matching FR-006's spirit) |
| 3 | (site-wide bug, caught here) AR category labels/filtering | Category badge shows the localized service name; filter buttons match every card of that category | AR cards showed the **literal taxonomy term slug** as the badge (e.g. "VIDEO-AR", uppercased by CSS) — Polylang gives every language its own term (`video` EN, a separate `video-ar` AR term, linked as a translation), and `ProjectRepository::toGridCard()` used the term's own slug directly, which is never one of the four canonical slugs (`video`/`motion`/`design`/`web`) that `categoryLabel()` and the filter buttons are keyed on. This also meant **the filter buttons never matched any AR card at all** — a functional break, not just a cosmetic one. | **Resolved** — `ProjectRepository::canonicalCategorySlug()` resolves any per-language term back to its English-translation counterpart's slug via `pll_get_term()` (falls back to the term's own slug when Polylang is inactive or no translation exists) — the same class of fix already applied to `ClientsCarouselRenderer` earlier in this spec. New test coverage in `ProjectRepositoryTest.php` (previously had none). |
| 4 | Card thumbnails | Real distinct project photography | Token-driven gradient placeholder, hue-rotated per category | **Kept** — no real project thumbnails exist yet (`get_post_thumbnail_id` returns 0 for every seeded project); the existing gradient treatment is a reasonable placeholder already visually close to the handoff's own generic imagery. Real photography is an owner-content gap (FR-006), not a code defect. |
| 5 | Pagination | "Prev / 1 / 2 / Next" (handoff has more demo items than fit on one page) | All 9 seeded projects shown on one page, no pagination | **Not a defect** — the handoff's pagination is an artifact of its own arbitrarily-larger demo item count; with exactly 9 real/seeded projects there's nothing to paginate. Revisit only if the real project count grows large enough to need it. |

Re-verified: **72-check route-health, 192 Pest** all green. EN + AR + mobile captures confirm correct RTL
mirroring (breadcrumb, filter row, card grid).

## Project singles (`project.html` → `/work/<slug>/`)

Not yet compared — next in this route family.
