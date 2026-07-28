# Implementation Plan: Perego FSE visual editing and backend UX

**Branch**: `feature/021-fse-visual-editing-ux` | **Date**: 2026-07-20 | **Spec**: [spec.md](spec.md)

## Summary

Replace editor-only text/form approximations with data-contract-driven visual block previews while preserving the approved public renderer and CSS contract. Work is sequenced: baseline and inventory; reusable editor foundation; shared chrome; home blocks; content models; service composition; migration and full regression.

## Technical Context

**Language/Version**: PHP 8.3, JavaScript (WordPress block packages), SCSS  
**Primary Dependencies**: WordPress 7, Gutenberg packages, CoreX client runtime; Polylang optional behind the existing driver  
**Storage**: WordPress posts, terms, options, post meta, template-part/block attributes  
**Testing**: Pest, Jest, Playwright visual/interaction/a11y matrix  
**Target Platform**: Perego WordPress FSE theme and Site Editor  
**Constraints**: immutable public output; scoped editor assets; no ACF dependency; logical CSS; native controls; no raw technical inputs  
**Scale/Scope**: 17 components delivered in six dependency-ordered slices.

## Constitution Check

- [x] Theme is a skin; business/data behavior remains in `perego-site`.
- [x] Plugin boot and service-provider registration remain self-contained.
- [x] Render/data logic remains in renderers, repositories, and post-type services.
- [x] Dependencies are container-provided; new reusable services are injected.
- [x] Styles consume existing theme tokens and are editor-scoped.
- [x] Assets are block-declared and conditional.
- [x] Data writes use capabilities, nonces, sanitization, escaping, and REST permissions.
- [x] Editor UI is RTL-first, i18n-ready, and WCAG 2.2 AA.
- [x] Polylang remains optional through `LanguageDriver`.
- [x] Spec/doc/progress/decision and guard gates are part of every slice.

## Architecture and Delivery Slices

1. **Baseline and contract inventory**: capture frozen public matrix; map every current block editor, renderer, attribute/meta owner, and migration source.
2. **Editor foundation**: extend the existing `perego-site/src/Editor/` toolkit (already has `MediaField`, `RepeaterControls`, `collection`) with in-canvas primitives (`EditableText`, `EditableMedia`, `RecordPicker`, `LinkControl`, `LanguagePair`) plus REST query contracts, **and a markup-parity test harness** (Jest snapshot of editor markup vs. a fixture of the PHP `render_callback` output). Built once before the first component. No public asset changes.
3. **Shared chrome**: refactor Header and Footers to reuse public contract/classes in editor canvas, block unsafe navigation, add automatic/manual Services menu.
4. **Homepage visual blocks**: Hero parent/slide, Services composer, About, Clients query composer; migrate existing attributes without changing public behavior.
5. **Content-model UX**: hierarchical taxonomies; polished Client editor; service-project relationship and placement configuration; idempotent migration.
6. **Service templates and sections**: explicit service template assignment; Inner Hero, What We Do, Process, selected-work visual blocks; locked layouts.
7. **Final migration and acceptance**: run migrations, EN/AR regression matrix, editor workflow/a11y tests, rollback rehearsal, docs and PR evidence.

## Data Ownership

| Area | Canonical owner | Display configuration |
|---|---|---|
| Header/footer shared content | template-part block attributes / controlled site options | block attributes, versioned and localized where required |
| Hero, About | front-page blocks | visual block attributes and inner blocks |
| Services and Clients | Service/Client posts and taxonomies | query/manual selection attributes and optional card overrides |
| Service sections | Service post content/meta | locked template blocks and section attributes |
| Service portfolio | Project records and taxonomy | ordered, validated service relation + placement metadata |

## Editor rendering model (see DECISIONS 2026-07-21, refining framework #43)

Two block classes replace the previous `<ServerSideRender>`-iframe-only preview:

- **Static-layout blocks** (Header, Footer, Hero, Services teaser, About, Service Hero, What We Do, Process): `edit()` renders the **real component markup** using the **same compiled SCSS** the front-end loads (via `block.json` `style`/`editorStyle`), so the canvas is pixel-identical to production. Editable text is `RichText` placed in that markup; media uses `MediaPlaceholder`/`MediaReplaceFlow` in place; the Inspector holds only non-content settings. Each such block ships a **markup-parity test** so editor and PHP output cannot drift.
- **Dynamic/query blocks** (Clients carousel, Portfolio grid, related/search): retain `<ServerSideRender>` but with an on-brand styled placeholder and a `RecordPicker` (select/reorder/add/exclude by title+thumbnail, no raw IDs).

Repeaters (nav, slides, cards, steps, contact/social) use **structured array/object attributes**, not JSON strings; legacy JSON-string values are normalized at read time in both PHP and `edit()` so no saved content is lost.

## Public Render and Rollback

Public renderers remain the authority. `edit()` is a faithful editor-only projection of the PHP render, tied to it by the parity test; editor-only styles are under `.editor-styles-wrapper`. Migrations are versioned/idempotent, preserve legacy values until acceptance, record a completion marker, and support rollback by restoring a prior version/legacy read fallback. A failed visual comparison reverts the relevant slice rather than changing the frontend baseline.

## Project Structure

```text
sites/perego/
├── perego-site/src/Blocks/                 # block editor + renderers
├── perego-site/src/Admin/                  # post editor UX
├── perego-site/src/PostTypes/              # CPT/taxonomy/data contracts
├── perego-site/src/Repositories/           # normalized query data
├── perego-site/tests/                      # Pest + Jest
├── perego-theme/parts/ and templates/      # locked FSE composition only
├── perego-theme/assets/src/scss/           # editor-scoped presentation support
└── specs/021-fse-visual-editing-ux/        # this program and evidence
```

**Structure Decision**: Client plugin owns data, security, block behavior, and editor components; client theme owns composition and visual tokens only.
