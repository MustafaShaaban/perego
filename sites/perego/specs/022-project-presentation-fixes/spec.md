# Spec 022 — Project presentation fixes, featured shortlist, and demonstrable crops

**Branch:** `feature/022-project-presentation-fixes` (off `feature/021-fse-visual-editing-ux`)
**Mode:** Client Site Mode
**Status:** Complete (2026-07-28).
**Depends on:** 021 (`ProjectThumbnails`, `ProjectTileImage`, the EditorPanels sidebar).
**Owner request:** 2026-07-28, seven items; this spec covers four of them. The other three
(PDF in the lightbox, image zoom/fullscreen, canvas drag reordering) are spec 023.

## Goal

Four owner-reported problems, three of them presentation and one of them a missing editorial control:

1. On the Website Creation service page, client logos are not centred in their card, and the card is
   white — so a mark drawn in white is invisible on it.
2. On the home Work grid, the play badge is centred horizontally but sits near the foot of the
   thumbnail instead of in the middle of it.
3. The home page should lead with a shortlist of featured projects, not the whole portfolio.
4. The several-shapes crop set belongs to Video / Motion / Graphic Design, not Website Creation.

Plus: seed real crops, because the feature is invisible on seeded content — every fallback resolves to
the same still, so nothing on screen shows what the four crop fields are for.

## Scope

### 1. Logo plate centring and background (`perego-wordpress-adapter.scss`)

Two independent defects behind one symptom:

- `a.web-card__shot` sets `display: block` with a type selector, outweighing the class-only
  `.web-card__shot--logo, .web-card__shot--plate { display: grid }`. Every card carrying a live URL is
  an anchor, so the plate's `place-items: center` was losing on nearly all of them.
- `.web-card__shot--logo img` overrode sizing but not the four declarations it inherits from
  `perego-reference.scss:950` — `position: absolute`, `inset: 0`, `min-height: 100%`,
  `object-position: center top`. An abspos child with non-auto insets is not a grid item, and
  `min-height` beats `max-block-size`, so the mark stretched to the plate and sat at its top.

Background moves from `#fff` to `var(--panel-overlay, #160435)`, matching the home grid, which makes
the now-redundant `.web-card__shot--plate` override removable.

### 2. Play badge containing block (`perego-wordpress-adapter.scss`)

`.post-card__media` established no containing block, and neither does `.post-card`, so the absolutely
positioned `.play-btn` resolved against `.reveal.is-visible` — whose settled `translateY(0)` is still
a transform. Its `top: 50%` therefore measured half the whole card, title and excerpt included. Fixed
with `position: relative` on `.post-card__media`, which also re-anchors the sibling `.work-badge`.

### 3. Featured shortlist

- `ProjectPostType::META_FEATURED` (`_perego_project_featured`), integer, `absint`, `show_in_rest`,
  **no registered default** — the same rule the icon and crops follow, so "never written" stays
  distinguishable from "explicitly zero".
- `ProjectRepository::allForGrid( $content, $featuredOnly = false )`, filtering in PHP rather than
  through a `meta_query`: an Arabic project holds no meta of its own, so a `meta_query` would empty the
  Arabic home page. `isFeatured()` reads the post's own flag and falls back to the linked English
  record only when the key was never written.
- `portfolio-grid` gains a `featuredOnly` attribute; only `front-page.html` sets it. The `/work`
  archive is untouched and still lists everything.
- A `toggle` field in the editor's "In the grids" panel.

`page-attributes` is added to the CPT's `supports`. Core gates both the REST `menu_order` field and the
`orderby=menu_order` enum on it, and `allForGrid()` orders by `menu_order` first — spec 023's canvas
cannot otherwise reproduce the order visitors actually see.

### 4. Crops offered only where they render

`showWhen: ( meta, context ) => ! context.isWebProject` on the four `PROJECT_THUMB_FIELDS`. The front
end already routed Website Making through `WebShowcaseRenderer` and never through the mosaic; what was
wrong is that the editor still offered four fields nothing would ever display.

### 5. Seeds

- `scripts/seed-featured-projects.php` — up to three per category, English posts only, additive.
- `scripts/seed-project-crops.php` (`manifest` / `import` modes) + `scripts/generate-project-crops.mjs`
  (`sharp`, `fit: cover`, `position: attention`). PHP owns the shape table and eligibility; the node
  step owns only the pixels. Same fetch-then-import shape as `fetch-portfolio-logos.mjs` +
  `import-portfolio-logos.php`.

## Out of scope

- PDF attachments, lightbox zoom and fullscreen, canvas drag reordering — spec 023.
- The CoreX v0.37.0 reconciliation, still deferred to its own branch per `DECISIONS.md`.
- The pre-existing failures this work did not introduce and does not address (see Acceptance).

## Content findings (not defects, but they bound the result)

The published portfolio is **33 EN projects: web 29, video 3, motion 1, design 0**. Consequences the
owner should know about:

- The home shortlist is 7, not 12, because three categories cannot supply three projects each.
- The **Graphic Design filter chip on the home page resolves to nothing**, and the Graphic Design
  service page has an empty Selected Work section. `seed-featured-projects.php` warns about this.
- Only four projects are eligible for crops, so the mosaic demonstrates itself on the Video service
  page and the services overview, not everywhere.

## Acceptance

- [x] Logo marks centred on both axes on `/services/website-making/`, on `--panel-overlay`. Measured in
      a real browser engine: 20/20 cards, gaps equal on both axes.
- [x] Play badge centred on both axes on the home grid. Measured: `top 101.8 / bottom 101.8`,
      `left 179 / right 179`.
- [x] Home renders 7 cards, `/work` renders 33, Arabic home renders 7 via the English fallback.
- [x] Crop fields hidden on web projects, offered on non-web; icon and featured toggle on both.
- [x] `/services/video-editing/` emits `<picture>` with distinct mobile/tablet/desktop bands, and
      slot `m1` resolves `hero` where `m2` resolves `banner`.
- [x] Both seed scripts idempotent on re-run (verified: 0 newly featured, nothing to generate).
- [x] Pest 569, Jest 231, build clean, `.pot` regenerated.
- [x] `verify-visual.mjs` 72 checks / 10 failures, **all pre-existing** — 4 × the home page's two
      `<h1>`s (proved unchanged by re-running against the stashed baseline), and 6 × routes with no
      content (`/services/`, `/sample-page/`, `/work/landing-page-microsite/`).
- [x] `verify-a11y.mjs` 12 pages / 2 serious, **both pre-existing** — `color-contrast` on
      `.btn--accent`, a token in `perego-reference.scss` this branch does not touch, on two pages whose
      styling it does not change.
