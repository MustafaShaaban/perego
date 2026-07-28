# Tasks: Perego FSE visual editing and backend UX

## Phase 1 — Freeze and foundation

- [ ] T001 [P] Capture the full EN/AR public baseline matrix and route/interactions/a11y evidence in `sites/perego/specs/021-fse-visual-editing-ux/evidence/`.
- [x] T002 [P] Inventory current blocks, renderers, attributes, metadata, template parts, and editor placeholders in `sites/perego/specs/021-fse-visual-editing-ux/contracts/`.
- [ ] T003 Extend the existing `perego-site/src/Editor/` toolkit (`MediaField`, `RepeaterControls`, `collection`) with in-canvas primitives (`EditableText`, `EditableMedia`, `RecordPicker`, `LinkControl`, `LanguagePair`) and focused Jest tests, **and add the markup-parity harness** (Jest snapshot of editor markup vs. a fixture of each block's PHP `render_callback` output). See DECISIONS 2026-07-21.
- [ ] T004 Create normalized block-editor data/REST contracts, permissions, loading/error/empty handling, and Pest tests.
- [ ] T005 Verify the foundation’s keyboard, screen-reader, RTL, and no-raw-ID/key acceptance criteria.

## Phase 2 — Shared chrome [US1]

- [ ] T006 Write Header and Footer editor-preview/render contract tests.
- [ ] T007 Refactor `src/Blocks/site-header/` to a real visual, editor-safe preview and structured controls.
- [x] T008 Implement automatic/manual/excluded/reordered localized Services-menu configuration and migration.
- [ ] T009 Refactor `src/Blocks/site-footer/` and flat footer to visual locked previews with labelled supported controls.
- [ ] T010 Compare all header/footer public states and editor workflows to baseline.

## Phase 3 — Homepage visual composition [US2]

- [x] T011 Write visual/editor tests for Hero parent/slide data and migration.
- [x] T012 Replace the fixed Hero editor with parent/slide visual composition, safe controls, and migrated EN/AR content.
- [x] T013 Implement visual Services query/manual/hybrid composer with card overrides, ordering, and migration.
- [x] T014 Implement direct visual About block editing with locked structural wrappers. **Reopened and
  closed again 2026-07-23 (owner report).** Treating About as "already editable because it is
  `wp:post-content`" was only true on the *page* screen: in the Front Page **template** canvas core can
  only draw its "This is the Content block…" placeholder, because `render_block_core_post_content()`
  returns early without a `postId` context. New `perego-theme/home-about` block replaces the
  `wp:post-content` in `front-page.html` — the front end delegates to a real `core/post-content`
  `WP_Block` (byte-identical output, and each language renders its own page via the seeded context), while
  `edit()` binds the canvas to the page with `useEntityBlockEditor`, so the real glass panels render and
  their text is editable in place. **The copy never moved out of the pages** (spec 004 T012 stands).
- [x] T015 Implement Clients automatic/manual/hybrid visual composer, placeholder media, and stable preview controls.
- [x] T016 Run homepage public visual/interaction regression at every baseline width and language.

## Phase 4 — Content model and admin UX [US3]

- [x] T017 Migration tests for Client Type and Project Service hierarchy — **no migration needed**: both taxonomies were already registered `hierarchical => true` with assigned terms retained. Verified against the live term list (`web`/`web-ar` etc. intact).
- [x] T018 Taxonomy registrations are hierarchical — verified already in place in `ProjectPostType::taxonomyArgs()` and `ClientPostType::taxonomyArgs()`; EN/AR term pairs confirmed live.
- [x] T019 Client (and Project/Service) raw meta boxes replaced by typed, grouped sidebar panels — `src/EditorPanels/` with media pickers, selects, textareas and help text; no meta key changed. Old boxes unregistered but retained pending owner verification.
- [x] T020 Define Service↔Project portfolio query/manual/hybrid placement model, sanitizers, and migration.
- [x] T021 Implement Service portfolio picker/order/exclusions/grid placement UI and tests.
- [x] T022 Admin workflows validated — schema-contract tests read the real PHP meta constants and sanitizer enums so a key or enum typo cannot pass; both admin classes register without fatals; CPT list columns added (thumbnail after `cb`, then identifying fields) and their ordering pinned. Authorization is unchanged: the panels write registered meta whose `auth_callback` still gates protected keys.

## Phase 5 — Service templates and visual sections [US4]

- [ ] T023 Write stable template-assignment migration and template-selection tests.
- [ ] T024 Add Default and Website Making Service templates with protected approved composition.
- [x] T025 Implement real visual Service Inner Hero including tabs and localized service links.
- [x] T026 Implement visual What We Do editor and supported image/layout controls. **(no code needed — see note)**
- [x] T027 Implement Process parent/step visual editing, reordering, media/icon selection, and migration. **(no code needed — see note)**

> **T026/T027 note (2026-07-22, verified against the live `/services/video-editing/` markup).** "What We Do"
> (`svc-whatwedo` / `__grid` / `__text` / `__media`) and "Process" (`process` / `process-list` / `process-step`
> / `process-step__icon` / `process-arrow`) are **not custom blocks** — they are composed entirely from native
> core blocks (`wp:group`, `wp:heading`, `wp:paragraph`, `wp:image`) with Perego classNames, living inside each
> Service post's `wp:post-content`. There is no `ServerSideRender` and no custom `edit()` to replace, so the
> spec-021 live-canvas problem does not apply: editors already edit these sections directly and visually, with
> native image/layout controls, native drag-reorder for process steps, and native media selection for step
> icons. Same situation as the homepage About section (T014). No migration is needed because nothing moves.
- [ ] T028 Implement visual Service Portfolio section using the Phase 4 relation model.
- [ ] T029 Validate every Service template in EN/AR, desktop/mobile, and public interactions.

## Phase 6 — Migration, release verification, documentation [US5]

- [x] T030 Run idempotent migration on existing data and complete rollback rehearsal. **Done
  2026-07-23** — all eight migration scripts run twice against live data, every one reporting 0 changes
  on both passes; six routes byte-identical after the sweep; export → sweep → restore rehearsed with the
  content fingerprint (154 projects / 46 clients / 1 template part) and all six routes matching after the
  restore. Spec 021 adds no schema migration of its own — its repeater/slide upgrades are read-time
  normalisation covered by Jest. Evidence: `evidence/t030-migration-rollback.md`.
- [ ] T031 Run full public screenshot/DOM/console/overflow/a11y regression matrix and resolve only proven regressions.
- [ ] T032 Run focused editor E2E workflows and all Pest/Jest/Playwright suites.
- [ ] T033 Run clean-code, wp, test, and docs guards; update site README, docs, PROGRESS, DECISIONS, and evidence.
- [ ] T034 Commit, push, open/update stacked PR, and record merge/dependency state.

## Phase 8 — Remaining pages (the bare-sentence blocks)

Page batches, one commit each. C9–C10 done; the rest follow the standard below.

- [x] C9 `project-gallery-lightbox` — Work single. Real `.portfolio.project-gallery` skeleton mirroring the edited Project's gallery meta, placeholder tiles in the shared template. Locked (no controls).
- [x] C10 `project-navigation` — Work single. Had **no editor script at all** (FSE showed "your site doesn't include support for this block"); now renders the real prev/next nav and related-projects surface, with a `surface` select in the Inspector.
- [x] C11 `portfolio-grid` — Work archive. **Dynamic/query block**, so it keeps `ServerSideRender` per the standard below; the Inspector gains the editable archive heading/intro and closing CTA (En/Ar, empty = seed copy) plus a toggle for the launch demo note. The Work archive template (`archive-perego_project.html`) has no `core/group` at all, so there is no T035 fix here — `archive.html` is the *journal* archive and belongs to C12.
- [x] C12 Journal + Search — all six blocks. `journal-header` (editable title/lead per locale via the C11 seed-override seam), `post-breadcrumb`, `post-reading-time`, `journal-comments`, `related-posts` and `search-results` all render real markup with parity tests. `search-results` pins its **head** only — a real search renders one card per match (25 on the captured page), so a fixture of the results list would pin today's content rather than the contract. Includes the `home.html` and `archive.html` T035 fixes.
- [x] C13 Contact — `contact-service-chooser` and `join-form` both render real markup with parity tests (the join-form fixture is the block's own PHP render, captured via `wp eval do_blocks()`). Includes the `page-contact.html` T035 fix: the raw `id` moved to `TemplateSectionAttributes`, and the decorative background div is wrapped in `wp:html`. **That structural fix relocated core's layout classes onto the section and would have pushed the contact form down 24px** — neutralized with one CSS rule mirroring the one the stylesheet already carried a level deeper. See DECISIONS 2026-07-22 (C13).
- [x] C14 Services archive — `services-overview` renders the real composition (hero + four service tabs, the two-column intro, the process rail with interleaved arrows, the selected-work masonry, the closing CTA). Parity pins the **fixed sections**; the masonry renders one tile per project (23 here, 9 KB) so it is a documented sample, same split as `search-results`. Includes the `archive-perego_service.html` T035 fix. **Finding: that template is currently unreachable** — `ServicePostType` registers the CPT with `has_archive => false`, so `/services/` 404s while the singles work. Owner decision, recorded in PROGRESS.
- [x] C15 Legal + misc — `legal-toc`, `legal-updated`, `not-found`, `preloader`, `media-lightbox` all render real markup with parity tests, and `footer-careers` moved off its two stacked `<fieldset>`s onto the standard (English edited in place, Arabic on `LanguagePair`). Includes the `legal.html` / `page.html` / `search.html` T035 fixes — the shared `<main id="main" tabindex="-1">` landmark, restored together because `anchor` could give the id but never the tabindex.
- [x] C16 Retrofit T036 onto the header nav/dropdown/CTA and footer links, with the shared PHP resolver. **Done up front** (owner-chosen order: link picker → pages → fields), so each remaining page batch adopts the picker as it is built rather than being edited twice.

## Phase 7 — Owner review findings (2026-07-22)

- [x] T035 Template block-validity sweep — **all seven templates fixed**: `front-page.html`, `home.html`, `archive.html`, `page-contact.html`, `archive-perego_service.html`, `legal.html`, `page.html`, `search.html`. Every fix verified by curl-diffing the affected route.
- [x] T036 Structured link picker — replace every free-text link URL with a shared picker (custom vs dynamic, post-type select, record select, open-in-new-tab) plus a shared PHP resolver. **Done:** `Editor/LinkPicker.js`, `PeregoSite\Blocks\LinkTarget`, and `LanguageDriver::localizedPermalink()`, applied to the header nav/dropdown/CTA, footer legal links and contact channels, hero-slider CTA, services-teaser "See all", and the portfolio-grid and project-navigation CTAs. Each remaining page batch adopts it as it is built.

### T035 — the affected templates

`front-page.html` is **done** (see below). Remaining, each with the offending markup:

| Template | Offending markup inside a `core/group` boundary |
|---|---|
| `page-contact.html` | `id="contactChoose"` + an unwrapped `<div class="contact-hero__bg">…</div>` child |
| `page.html` | `<main id="main" tabindex="-1">`; `post-hero__inner` `style="text-align:center"`; `prose` `style="margin-top:…"` |
| `archive.html` | `post-hero__inner` `style="text-align:center;"`; `wp:query` `style="margin-top:…"` |
| `home.html` | `wp:query` `style="margin-top:…"` |
| `archive-perego_service.html` | `<main id="main" tabindex="-1">` |
| `legal.html` | `<main id="main" tabindex="-1">` |
| `search.html` | `<main id="main" tabindex="-1">` |

Order of preference per case: **(1)** express it natively where core reproduces the string exactly — `anchor` for a plain `id`, `style.spacing.margin` for a margin; **(2)** wrap genuinely raw markup in `wp:html`, which core round-trips verbatim; **(3)** fall back to `PeregoSite\Theme\TemplateSectionAttributes` for attributes core cannot express at all (`aria-labelledby`, `tabindex`). Every fix is verified by curl-diffing the affected route before and after — expect DOM-identical, not byte-identical, wherever option 3 is used.

`front-page.html` (the owner-reported About section) is fixed: the template now carries exactly `<section class="wp-block-group home-about">`, and `TemplateSectionAttributes` puts `id="about"` and `aria-labelledby="home-about-title"` back on `render_block`.

### T036 — the link picker contract

Editor (shared `Editor/LinkPicker.js`, superseding `LinkControl` for new work):

1. **Link type** — *Custom URL* or *Existing content*.
2. When *Existing content*: **Content type** — the site's public, viewable post types (from the `core` store's `getPostTypes`, minus `attachment`), then **Item** — that type's published records listed by their own title, never a raw ID (the `RecordPicker` rule).
3. When *Custom URL*: the current free-text field, keeping the existing help text.
4. **Open in a new tab** — toggle, both modes.

Storage is additive so existing `{label, href}` data keeps working:
`{label, href, linkKind: 'custom'|'dynamic', postType, postId, openInNewTab}`.

Rendering is a shared PHP resolver: `custom` follows the existing `SiteHeaderRenderer::ctaHref()` rule (localize an internal path; use an external/`mailto:`/`tel:`/`#anchor` verbatim); `dynamic` resolves the Polylang-translated permalink for the current locale and falls back to `href` when the target is unpublished or gone; `openInNewTab` emits `target="_blank" rel="noopener"`, the pairing already used by `SiteFooterRenderer` and `ClientsCarouselRenderer`.

## Phase 9 — Client content model: type-driven fields + explicit behaviour (owner, 2026-07-28)

T019 gave Clients typed panels but kept the field set flat: every client saw every field, and what a card
*did* was inferred rather than chosen. The owner's model makes client type the first choice, and makes
**Behavior** — `No actions` (default) / `Lightbox` / `Link` — the single thing that decides what a card does.

- [x] T037 **Behaviour data model.** `_perego_client_behavior` (`none|lightbox|link`, default `none`),
  `_perego_client_hide_play_icon`, and the flat `_perego_client_link_*` `LinkTarget` set registered on
  `ClientPostType` with REST schema, sanitizers and `auth_callback`. `_perego_client_gallery` now also
  keeps an uploaded video's attachment id. Retired: `_perego_client_sub`, `_perego_client_video_url`,
  `_perego_client_video_type`, `VIDEO_TYPES`. The play badge is stored **inverted** (`hide`, default
  false) because WordPress writes a `false` boolean as `''`, which is indistinguishable from unset — a
  default-true `show` flag could never have been switched off.
- [x] T038 **Behaviour rendering.** `ClientsCarouselRenderer` builds both card types through one
  `cardTags()`: `lightbox` → `<button>` + the trigger from the new shared `Blocks\LightboxTrigger`,
  `link` → `<a>` via `LinkTarget`, `none` → an inert `<div>`. A behaviour whose data is missing degrades
  to inert rather than rendering a control that does nothing. The implicit corporate logo-lightbox
  fallback is **gone** (owner-confirmed). `LightboxTrigger` also percent-encodes commas, closing the
  `data-gallery` splitting hazard `view.js` has always had.
- [x] T039 **Individual card copy.** The former "Statistic" field is relabelled **Subtitle** (same
  `_perego_client_stat` key and `<strong>` sanitizer — the owner adds the tag), and the card's body is
  now the client post's **own editor content**, reduced by `wp_kses` to a non-interactive subset because
  the card element is itself a `<button>`/`<a>`.
- [x] T040 **One grouped panel.** `EditorPanels` composes Client type (writing the taxonomy, Polylang
  locale-preserving), name, logo/thumbnail, subtitle, behaviour, gallery repeater, link picker, per-client
  play badge and a live card preview. The default taxonomy panel is removed so type is chosen in exactly
  one place. **The last classic meta box (`ClientMediaMetaBox`) is deleted**, closing T019's holdout;
  the dead `PostMetaBoxes` goes with it. The carousel-wide `showPlayIcon` block attribute is removed.
- [x] T041 **Migration.** `scripts/migrate-client-behavior.php` — idempotent, `--dry-run`, snapshots into
  `_perego_client_migration_backup` before writing, sweeps EN and AR. Derives the behaviour each client
  already had, folds a single video into the gallery, moves `_perego_client_sub` into the post body.
- [x] T042 **Card refinements** (owner, 2026-07-28). (a) The individual subtitle is capped at
  `ClientPostType::SUBTITLE_MAX_CHARS` = **30 visible characters** — measured, not chosen: the rendered
  box fits 15 characters per line at its narrowest desktop breakpoint, so 30 is exactly two lines
  everywhere and a long subtitle can no longer grow the card and push the carousel row. Enforced in the
  panel by a live counter that refuses over-long input, and in `sanitizeStat()` as a backstop for REST,
  WP-CLI and imports; the cap counts text only, leaves `<strong>` intact and closes a tag left open by
  the cut. (b) The corporate tile shows the **client's logo**; the equalizer glyph is demoted to a
  placeholder for clients without artwork. The logo is absolutely positioned and inset to a
  `--corp-pad-*` padding with `object-fit: contain`, so no logo — of any size or aspect — can change
  the tile's height. Verified by injecting 3000×100, 100×3000 and 4000×4000 logos: tile heights stayed
  identical and nothing overflowed.

## Phase 10 — Work grid: explicit icons, per-slot crops, tablet layout (owner, 2026-07-28)

- [x] T043 **Explicit tile icon.** `_perego_project_icon` (`none|play|gallery`, no registered default)
  replaces the inference that gave a ▶ to any project with a video and a badge to any with 2+ images.
  Applies to the services mosaic **and** the work/portfolio grid, which previously showed no affordance
  at all even on cards that open a lightbox. The tile's *trigger* stays media-derived: what a tile
  opens and what it advertises are separate questions.
- [x] T044 **Per-shape crops.** Four optional attachment metas (`_perego_thumb_hero|banner|tall|card`,
  578×332 / 380×158 / 182×332 / 406×254) plus `PostTypes\ProjectThumbnails`, which owns the shape
  table, the slot→shape map and the fallback chain (own crop → nearest shape by aspect ratio →
  featured image). `Blocks\ProjectTileImage` emits a `<picture>` naming the desktop, tablet and mobile
  bands; a project with no crops still emits the plain `<img>` it always did.
  **Resolved server-side, not by measuring in JS**: the slot is already decided by the renderer and
  the bands are viewport-based, so the markup can simply state the mapping — and the tile keeps
  working with JavaScript off.
- [x] T045 **Slot-map guard.** `EditorPanels/slot-shapes.test.js` parses the theme's `.m{n}` spans and
  asserts each slot's real geometry still matches the shape PHP assigns it — the two live in different
  languages in different packages and nothing else makes them agree.
- [x] T046 **Tablet band.** New `max-width: 900px` layout: 4 equal columns, relative `span N` in place
  of the desktop mosaic's absolute line numbers, brand tile hidden. The 2-column collapse moves 520px
  → 560px so nothing lands in the old dead band. Measured: the narrowest tile went from **63–74px** to
  **119px**, pinned by 14 new checks in `verify-interactions.mjs`.
- [x] T047 **Subtitle two-line clamp.** `.indiv-card__sub` is `line-clamp: 2`, and
  `ClientPostType::SUBTITLE_MAX_CHARS` rises 30 → 48: the clamp guarantees two lines at every width,
  which no character count can, so the cap is a writing guide rather than the layout's protection.
  Editor copy reworded and the Content hint given its own label — the two help texts ran together.
- [x] T048 **Migration.** `scripts/migrate-project-icon.php`, same shape as the client one: idempotent,
  `--dry-run`, batched, snapshots, `metadata_exists()` marker, EN+AR sweep, and a derived `none` left
  unwritten so translations keep inheriting. Applied: 3 projects given an icon, 63 genuinely have none.

## Standard for every visual-block slice (per DECISIONS 2026-07-21, refining framework #43)

Static-layout blocks (Header, Footer, Hero, Services teaser, About, Service Inner Hero, What We Do, Process) render their **real markup in `edit()`** with in-canvas `RichText`/`MediaPlaceholder` and ship a **markup-parity test**; `<ServerSideRender>` is retained only for dynamic/query blocks (Clients, Portfolio grid, related/search) with a styled placeholder + `RecordPicker`. Repeaters use structured attributes with legacy JSON-string read-time normalization. Content-model rework also covers **Project admin UX (C13)** alongside the Client editor in T019/T022.

## Dependencies

T001–T005 block all implementation. T006–T010 establish the preview contract reused by T011–T029. T017–T021 precede T028. Every slice must complete its baseline comparison before the next slice.
