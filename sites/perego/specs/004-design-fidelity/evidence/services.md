# Services (archive + singles) — visual-acceptance evidence (US2 / T014)

**Reference**: `_design_handoff/Perego-Creative-Studio-Final-Handoff/site/services.html`,
`service-{video-editing,motion-graphics,graphic-design,website-making}.html`
**Live**: EN `http://perego.local/services/` · AR `http://perego.local/ar/services/`

## Services archive (`services.html` → `/services/`)

Captured EN/AR desktop+mobile, compared against the handoff, corrected, re-verified.

### Material differences found and resolved

| # | Section | Handoff | Live (before) | Disposition |
| --- | --- | --- | --- | --- |
| 1 | Hero background | Full-bleed portrait image behind H1 + tabs | No image, flat background | **Resolved** — wired `svc-hero-bg.png` |
| 2 | Page structure | Two sections: hero (H1+tabs) then a separate "what we do" (H2+subline+2 paragraphs+media image) | Merged into one intro block; no media image; tabs showed an extra subline not in the handoff | **Resolved** — split into `.svc-hero` + `.svc-whatwedo`/`.services-overview__whatwedo-*`, restored the media image (`ui-video-editing.png`), each service tab now label-only with a hover "Start your project" sub-link matching the handoff |
| 3 | Closing CTA | Full-bleed dark section, filled accent button | Rendered on a **white background** (see below) with an unstyled default link | **Resolved** — see rows 4 and 5 |
| 4 | (site-wide bug) CTA button classes | — | `NotFoundRenderer`, `SearchResultsRenderer`, and this archive's CTA all used the handoff's own `.btn`/`.btn--accent`/`.btn--dark` class names, which don't exist in this theme (`.perego-btn`/`.perego-btn--accent` is the real primitive) — so the buttons rendered with no styling at all | **Resolved site-wide**: fixed all three files; added a missing `.perego-btn--dark` variant (used by 404's secondary CTA) |
| 5 | (site-wide finding) No reliable page-level background | — | `body`'s background computes to fully transparent on every route (a `main.css` rule sets `body { background: var(--perego-gradient-bg) }` as a shorthand, which — combined with `background-attachment: fixed` — does not reliably paint behind every section on a page taller than one viewport). Home never exposed this because every section there already self-paints edge-to-edge with no gaps; this archive's CTA section had no background of its own, so the gap showed literal white | **Resolved for this route**: gave `.services-overview__cta` its own explicit `--wp--preset--color--bg-deep` background (full-width, with an inner max-width wrapper for the centered content) — matching the handoff's own per-section self-painting approach used throughout. **Not a one-off**: the same gap risk applies to any future route whose last section has no explicit background; check for it as a matter of course, not just when it happens to be visible. |
| 6 | Process step visuals | Icon images (clapper/film/star) with arrow connectors | Numbered circles (1–4), no arrows | **Kept** — this is `.svc-process`, the same shared styling already used by service singles (spec 003); changing it here would make singles inconsistent. Treated as an established, consistently-applied simplification (same category as Home's row 10 hero-controls decision), not a per-page defect. |
| 7 | "Selected work" masonry teaser | A 15-card demo masonry grid (video/image/gallery cards, mostly placeholder — even the handoff's own JS reuses the same few portfolio images repeatedly with a rick-roll video embed) + Load More | Not present | **Deliberately not built** — duplicates the existing `/work/` archive's job, and the underlying real project thumbnails don't exist yet (`get_post_thumbnail_id` returns 0 for every seeded project), so a faithful teaser would show empty/placeholder cards regardless of approach. Same category of decision as Home's clients-gallery scope call. Tracked as a content-dependent gap, not a defect. |

Re-verified after fixes: **72-check route-health, 187 Pest, 0 a11y regressions** (not yet re-run per-route,
verified via the full-suite pass). EN + AR + mobile captures confirm correct RTL mirroring (whatwedo
grid flips, process step order reverses correctly reading right-to-left).

## Service singles (`service-*.html` → `/services/<slug>/`)

Not yet compared — next in this route family. `single-perego_service.html` already uses the
editor-canvas pattern (`wp:post-content`) for "What we do"/"Our Process" prose (spec 003), so T012-style
migration is not needed here; only visual-parity comparison remains.
