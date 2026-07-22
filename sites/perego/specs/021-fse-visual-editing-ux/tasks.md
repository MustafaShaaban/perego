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
- [x] T014 Implement direct visual About block editing with locked structural wrappers.
- [x] T015 Implement Clients automatic/manual/hybrid visual composer, placeholder media, and stable preview controls.
- [x] T016 Run homepage public visual/interaction regression at every baseline width and language.

## Phase 4 — Content model and admin UX [US3]

- [ ] T017 Write migration tests for Client Type and Project Service hierarchy and assigned-term retention.
- [ ] T018 Change taxonomy registrations to hierarchical and verify EN/AR relationship/filter/URL compatibility.
- [ ] T019 Replace Client raw meta box behavior with polished labelled media, metadata, hierarchical-term, and card-preview UI.
- [x] T020 Define Service↔Project portfolio query/manual/hybrid placement model, sanitizers, and migration.
- [x] T021 Implement Service portfolio picker/order/exclusions/grid placement UI and tests.
- [ ] T022 Validate Client/Project admin workflows, authorization, sanitization, RTL, and public regression.

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

- [ ] T030 Run idempotent migration on existing data and complete rollback rehearsal.
- [ ] T031 Run full public screenshot/DOM/console/overflow/a11y regression matrix and resolve only proven regressions.
- [ ] T032 Run focused editor E2E workflows and all Pest/Jest/Playwright suites.
- [ ] T033 Run clean-code, wp, test, and docs guards; update site README, docs, PROGRESS, DECISIONS, and evidence.
- [ ] T034 Commit, push, open/update stacked PR, and record merge/dependency state.

## Phase 7 — Owner review findings (2026-07-22)

- [ ] T035 Template block-validity sweep. Seven theme templates embed markup `core/group`'s `save()` cannot regenerate, so FSE renders them as "Block contains unexpected or invalid content". Fix each inside its page batch, then verify by diffing the rendered route.
- [ ] T036 Structured link picker. Replace every free-text link URL with a shared `LinkPicker` (custom vs dynamic, post-type select, record select, open-in-new-tab) plus a shared PHP resolver. Build at first need; retrofit header/footer/CTA last.

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

## Standard for every visual-block slice (per DECISIONS 2026-07-21, refining framework #43)

Static-layout blocks (Header, Footer, Hero, Services teaser, About, Service Inner Hero, What We Do, Process) render their **real markup in `edit()`** with in-canvas `RichText`/`MediaPlaceholder` and ship a **markup-parity test**; `<ServerSideRender>` is retained only for dynamic/query blocks (Clients, Portfolio grid, related/search) with a styled placeholder + `RecordPicker`. Repeaters use structured attributes with legacy JSON-string read-time normalization. Content-model rework also covers **Project admin UX (C13)** alongside the Client editor in T019/T022.

## Dependencies

T001–T005 block all implementation. T006–T010 establish the preview contract reused by T011–T029. T017–T021 precede T028. Every slice must complete its baseline comparison before the next slice.
