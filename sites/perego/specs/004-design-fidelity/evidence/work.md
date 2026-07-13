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
| 4 | Card thumbnails | The handoff itself reuses nine generic `portfolio-N.png` stills across its cards/galleries — not unique per-project photography (no such assets exist even in the handoff) | Token-driven gradient placeholder, hue-rotated per category (no featured images set on any of the 18 EN+AR project posts) | **Resolved** — new idempotent `scripts/seed-project-media.php` imports the same nine approved handoff stills as real media-library attachments and sets them as featured images (each EN project and its AR translation share the same still, cycling through the fixed seed order). All 9 archive cards now show a real image; still clearly demo content pending the owner's real project photography (FR-006). |
| 5 | Pagination | "Prev / 1 / 2 / Next" (handoff has more demo items than fit on one page) | All 9 seeded projects shown on one page, no pagination | **Not a defect** — the handoff's pagination is an artifact of its own arbitrarily-larger demo item count; with exactly 9 real/seeded projects there's nothing to paginate. Revisit only if the real project count grows large enough to need it. |

Re-verified: **72-check route-health, 192 Pest** all green. EN + AR + mobile captures confirm correct RTL
mirroring (breadcrumb, filter row, card grid).

## Project singles (`project.html` → `/work/<slug>/`)

Checked `landing-page-microsite` as the representative single (all singles share the same
renderer/template).

### Material differences found and resolved

| # | Section | Handoff | Live (before) | Disposition |
| --- | --- | --- | --- | --- |
| 1 | Featured image | A large hero still directly under the title | Not rendered — `ProjectHeroRenderer` never had an image slot at all | **Resolved** — added a `project-hero__featured` block rendering `get_the_post_thumbnail()` when set; uses the same seeded images as the archive (`scripts/seed-project-media.php`) |
| 2 | Full-bleed section backgrounds | Every section paints edge-to-edge | Two separate white gaps: (a) the entire prose/case-study body had no background at all (`.project-single__body` — free-form prose, unlike service singles' `.svc-whatwedo`/`.svc-process` groups which self-paint); (b) `.project-hero` has a content-constrained `max-inline-size`, so even giving *it* a background wouldn't reach the viewport edges | **Resolved** — gave `.project-single__body` an explicit `bg-deep` background; gave `.project-hero` a full-bleed `::before` pseudo-element (the standard "full-bleed section, constrained content" CSS technique) rather than relying on `body`'s own background (still unreliable — see spec 004 T014 evidence) |
| 3 | Project gallery (`Project gallery` masonry + lightbox) | 3-image gallery per project, click to open a lightbox | Not built — `ProjectGalleryLightboxRenderer`/`project-gallery-lightbox` block exists in source (spec 003 US2) but was never registered or wired into the template | **Deliberately deferred** — a real, separately-scoped feature (gallery meta storage, block registration, template wiring, seed content), not a quick fix. Tracked as open below. |
| 4 | Prev/next project navigation | "‹ Previous project" / "Next project ›" | Not rendered | **Deliberately deferred** — smaller than the gallery but still net-new (adjacent-post query + rendering), grouped with the same follow-up. |
| 5 | Related projects section | 3-card grid of other projects + a "Start a Project" CTA | Not rendered | **Deliberately deferred** — same reasoning; reasonably achievable (could reuse `ProjectRepository`) but not a visual-parity bug fix, grouped as a follow-up feature slice. |

Re-verified: **72-check route-health, 12-page a11y (0 violations), 194 Pest** all green. EN + AR + mobile
captures confirm correct RTL mirroring.

### Historical deferred record (superseded by Spec 007)

Rows 3–5 above are real, valuable content-completeness gaps for the case-study experience, but are new
features (gallery/lightbox, adjacent-post nav, related-projects query) rather than corrections to what's
already there — the same category of decision as Home's clients-gallery scope call. Suggested next slice:
wire the existing `ProjectGalleryLightboxRenderer` in first (it already exists and is unit-tested; only
registration + template wiring + gallery-meta seeding remain), then prev/next nav, then related projects.

## Completion update — 2026-07-13 (Spec 007)

The three deferred project-single surfaces are now complete without a redesign:

- The existing accessible `perego/project-gallery-lightbox` is registered, has the locked “Project gallery” heading, and is placed in the structural FSE template.
- `seed-project-galleries.php` idempotently created exactly three approved handoff-image attachment IDs for every EN/AR demo project; later editor galleries are never replaced.
- Adjacent links and a three-card, current-language related-project row are server-rendered; sparse service categories fill from other current-language work to preserve the locked three-card layout.

Verification after the local seed: `npm run build`, Pest (**219 tests / 617 assertions**), Jest (**67 tests**), route health (**72 / 0**), accessibility (**12 pages / 0 serious-critical**), and interaction (**4 / 4**) all pass. Focused real-browser checks also confirm exactly three gallery thumbnails, an opening dialog, three related cards, and both wrapped adjacent links in EN and AR.
