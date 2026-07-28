# Spec 023 — Project documents, lightbox reading, and canvas ordering

**Branch:** `feature/023-project-media-and-canvas-ordering` (off `feature/022-project-presentation-fixes`)
**Mode:** Client Site Mode
**Status:** Complete (2026-07-28), including real-editor verification.
**Depends on:** 021 (`Editor/parity.js`, `collection.js`, the EditorPanels sidebar), 022 (`page-attributes`).

## Goal

The remaining three items of the owner's 2026-07-28 request:

1. Clients can attach **PDFs** to a project and read them in the lightbox; the lightbox also gains
   **image zoom** and **full screen**.
2. Projects can be **reordered wherever they are listed**, by dragging the real cards on the editor
   canvas — the home grid and the single-service grids.
3. The single-service grid must be draggable, which the block could not be while it lived in a
   template.

## Scope

### A. Documents (`_perego_project_pdf_ids`)

A separate meta key rather than widening `META_GALLERY` into the Client-style typed rows: that meta is
a flat id list read in six places, and converting it would be a migration for every project to buy
something only this feature needs. `pdfsFor()` mirrors `galleryFor()`, English fallback included — a
PDF is language-neutral data.

In the lightbox they are simply more slides: `mediaType()` gains a `pdf` branch before the image
fallthrough, and the trigger appends documents after the artwork so a card still leads with what it
led with. Rendered in the browser's own viewer (self-hosted, so same-origin, so no PDF.js), with a
visible link beneath it — **not decoration**: iOS Safari paints only the first page of a framed PDF
and offers no way to scroll it.

### B. Zoom and full screen

Wheel, double-click, pointer drag-to-pan and `+`/`-`/`0`, clamped 1×–4×, **reset per slide** — carrying
zoom between slides would land the visitor on a photo already cropped into its own corner. Full screen
targets the whole dialog so documents and video benefit, is driven by `fullscreenchange` rather than by
the click so leaving it via browser chrome keeps the button honest, and Escape unwinds one layer at a
time. New controls are real buttons inside `.lightbox__inner`, so the existing focus trap covers them
unchanged.

### C. Ordering

**Stored as a per-block `projectOrder` attribute, not `menu_order`.** Home and `/work` render the same
block and now show different sets, so one global position cannot express both; and `menu_order` already
carries the client's deliberate 29-site website sequence, which a canvas drag would overwrite. It stays
the fallback order. `Content\ProjectOrder` is the override laid on top: empty means "no manual order",
which is what made this a no-op on the front end until someone drags.

`ProjectOrder` indexes by canonical English id **and** raw id, because a grid's order is authored
against the English posts (its template is language-neutral) while a Service post's order is authored
in its own language. Unnamed posts are appended, never promoted.

**Pointer events, no dependency.** `useBlockProps` sets `draggable="true"` on the block wrapper, the
editor installs document-level `dragover` handling for its own insertion indicator, and the canvas is
an iframe while the drag chip is not — a nested HTML5 drag source fights all three. Pointer events also
give the `getBoundingClientRect()` hit-testing the mosaic requires, because `.m1`–`.m15` carry explicit
grid placement and DOM order is **not** visual order there.

**WCAG 2.2 2.5.7 requires a single-pointer alternative that is not a drag**, so every card carries Move
earlier / Move later buttons at 24 px (also satisfying 2.5.8), plus `Ctrl/Cmd`+arrow, `Home`/`End` and
Escape-to-cancel. Announcements go through `@wordpress/a11y`'s `speak()`. One `setAttributes` per move,
so a drag is one undo step.

### D. Both query blocks become live canvases

This **reverses spec 021's rule** that a query block keeps `ServerSideRender`. That rule assumed the
editing surface was the block's settings; the owner's request makes it the query result itself, and
nothing inside an SSR iframe can be dragged. Superseded for these two blocks only, and recorded in
`DECISIONS.md`.

The canvas shows the whole **unfiltered, unpaginated** set with the chips and pager inert: `PER_PAGE`
is 9, and dragging a card from position 52 to position 3 is impossible through a nine-card window. It
also **previews English and says so** — both grid instances live in language-neutral templates, so
there is no current language to honour, and the English posts carry all the media.

The **sortable wrapper is the card**: the sorting affordance is a pair of buttons, and nesting those
inside the front end's `<button>`/`<a>` would be invalid, so in the editor the card is a `<div>`
carrying the same classes — what the grid places and what the stylesheet targets.

### E. The service block moves into post content

It resolved its service from `get_queried_object()`, which the Site Editor never provides while editing
a template — so its SSR preview was blank, *and* a block in a shared template has no service to save an
order against. `scripts/migrate-service-selected-work-block.php` (dry-run / verify / apply) moves it
into each Service post's content; the template line is removed in the same commit, after apply.

Dragging here re-crops: index → slot → shape → crop. A tile moved from position 5 to 3 changes from a
`card` crop to a `tall` one, and when the project has no crop for its new shape the tile says which one
it borrowed. Website Making keeps SSR — it branches to `WebShowcaseRenderer`, one uniform card shape,
so ordering it changes no crops.

### F. Three JS mirrors of PHP, each guarded by parsing the PHP

`Editor/projectSlots.js` (shapes, slot map, nearest-ratio fallback, `<picture>` collapse rule, work
caps, bands), `Editor/servicePortfolio.js` (the automatic/manual/hybrid composition), and the
`PER_PAGE` constant. `EditorPanels/slot-shapes.test.js` already guarded PHP against the stylesheet;
these guard JS against PHP — the other edge of the same triangle.

A real cost, named: once each Service post's block owns an ordered list, the
`mode`/`project_ids`/`exclude_ids` meta triad is arguably redundant and `ServicePortfolioSelection`
could be retired. **A separate pass, not this one.**

## Out of scope

- The CoreX v0.37.0 reconciliation, still deferred to its own branch.
- Retiring the Service portfolio-mode meta (see F).

## Verification

- [x] Pest **582** (2005 assertions) · Jest **46 suites / 299**.
- [x] Parity: 8 tests pin the grid's three card shapes and section chrome; 7 pin the mosaic, including
      that array order maps to slot order — the drag's own output.
- [x] Mirrors: 20 `projectSlots` tests and 6 `servicePortfolio` tests, all parsing the PHP.
- [x] Sorting arithmetic: 15 tests, including the mosaic case where DOM order is not visual order.
- [x] PDF, zoom and full screen exercised in a **real browser**: zoom measurably magnifies the painted
      image (640 → 960 px), the document frame is page-shaped not 16/9, controls stand down for
      documents and video, full screen targets the dialog and reports its state.
- [x] Front end unchanged by the ordering work: 7 / 33 cards on home and `/work`, and 3 / 1 / 0 / 22
      tiles across the four services, identical before and after — on **both** languages.
- [x] Migration applied to 8 Service posts, idempotent, section renders exactly once afterwards.
- [x] `verify-a11y` **0 serious/critical**. `verify-visual` 72 checks / 10 failures, all pre-existing
      (4 × the home page's two `<h1>`s, 6 × routes with no content).

### Real-editor verification — `scripts/verify-editor-sorting.mjs`

```
node sites/perego/perego-site/scripts/verify-editor-sorting.mjs
```

Drives the actual Site Editor. Auth is a session minted through WP-CLI, so no password is handled and
nothing is typed into a login form. **`--url` is load-bearing**: `COOKIEHASH` is `md5()` of the
*resolved* site URL, and this install defines `WP_SITEURL` per host — so a cookie minted without it is
named `wordpress_logged_in_<md5 of the DB option>` while the browser sends
`<md5 of the constant>`, and WordPress simply never sees it. That mismatch is what made this look
unverifiable at first.

All six checks pass: the grid renders as a live canvas (7 cards), every card offers the non-drag Move
controls, the Move later button reorders, a pointer drag lands the card at the drop position, the
editor does not hijack the gesture into moving the block, and there are no page errors. The harness
deletes the `wp_template` override its save creates, so the theme file stays authoritative.

Verified separately, by hand, in the same session:

- The saved order is what the front end renders, and the featured shortlist is still 7 cards.
- On the Video Editing Service post, moving tile 3 to position 1 moved it from slot `m3` to `m1` and
  **re-drew it from a different crop** — `261-tall.webp` → `261-hero.webp` — with the on-tile note
  changing from "tall crop" to "hero crop". That is the whole design claim, demonstrated.

**It caught a real bug that no unit test could.** The Move earlier / Move later buttons live inside the
card, so their `pointerdown` bubbled to the sort handler — whose `preventDefault()`, the thing that
stops a native drag ever starting, also suppressed the button's own `click`. Dragging worked; the
single-pointer alternative WCAG 2.2 **2.5.7** requires silently did not. jsdom dispatches `click`
directly and never reproduces it. `useCanvasSort` now ignores `pointerdown` originating inside
`.perego-sortable__controls`, and the harness is committed as the standing guard.
