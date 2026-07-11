# Implementation Plan: Global Foundation (tokens, header/footer shell, bilingual, preloader)

**Branch**: `001-global-foundation` | **Date**: 2026-07-11 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `sites/perego/specs/001-global-foundation/spec.md`

## Summary

Every Perego page shares one branded, token-driven shell (header/footer), works correctly in
English/Arabic with a persisted language choice, and the homepage shows a branded first-visit
preloader. Built as three CoreX dynamic blocks in `perego-site` (`perego/site-header`,
`perego/site-footer`, `perego/preloader`) consumed by `perego-theme`'s FSE template parts/templates,
styled entirely from `design-tokens.json` → `theme.json` (already done in a prior session — this plan
covers what remains: the language mechanism, the two shell blocks, and the preloader block).

## Technical Context

**Language/Version**: PHP 8.3 (plugin blocks), WordPress 7.0 FSE (theme templates/parts), JS (WordPress
Interactivity API — `@wordpress/interactivity`), SCSS (compiled via the theme's existing `wp-scripts`/
`sass` pipeline).

**Primary Dependencies**: `corex-core` (Boot/Container/Model), `corex-blocks` (block auto-discovery,
conditional assets), `corex-config` (Options), Polylang (optional — detected behind a driver interface
per constitution IX; a same-effect fallback driver if absent).

**Storage**: WordPress options (`perego_language_fallback` — only used by the fallback driver to record
nothing server-side; the actual preference is browser-persisted, not server storage) + Polylang's own
storage when active. N/A beyond that for this feature.

**Testing**: Pest (PHP unit — block render callbacks, the language driver interface + both
implementations), Jest (the Interactivity API view-script logic: sticky/mobile-menu/dropdown/preloader
state machines, mocked DOM), manual Playwright-style verification against the handoff's reference
screenshots (visual/behavioral fidelity is an explicit spec success criterion).

**Target Platform**: WordPress 7.0+ / PHP 8.3+ (per `perego-theme/style.css` `Requires at least`/
`Requires PHP`), desktop + mobile browsers per `RESPONSIVE_BEHAVIOR.md`.

**Project Type**: WordPress plugin (business logic + blocks) + FSE theme (presentation) — a CoreX
client site.

**Performance Goals**: preloader clears ≤0.9s after first paint (soft) / ≤2.5s hard timeout (spec
FR-009); language switch completes in <1s (spec SC-003) — both are client-side JS timing constraints,
no server round-trip.

**Constraints**: no global JS/CSS library (constitution VI) — each block's assets load only when that
block is present; no hardcoded design values (constitution V) — everything through `theme.json`/
`--wp--custom--perego--*`; RTL via logical properties only (constitution VIII); Polylang optional,
never a hard dependency (constitution IX).

**Scale/Scope**: 3 new dynamic blocks, 1 language-driver abstraction (2 implementations), theme parts/
templates wiring. No new CPTs (those start at M3).

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- [x] **I. Theme is a skin** — `perego-theme` only holds `theme.json`, template parts, and template
  markup that *references* the three blocks; all block PHP/JS/render logic lives in `perego-site`.
- [x] **II. Plugins boot themselves** — the language driver resolves via the container on
  `plugins_loaded` through `PeregoSiteServiceProvider`; blocks self-register via `corex-blocks`
  auto-discovery, independent of which admin/front-end/CLI context loads.
- [x] **III. Thin controllers, fat services** — no controllers in this feature (no REST endpoints);
  the language mechanism is a `Services/LanguageService` orchestrating a `LanguageDriver` interface,
  not logic embedded in block render callbacks.
- [x] **IV. Everything injected** — `LanguageService` and the active `LanguageDriver` are resolved via
  the PSR-11 container in the service provider, not `new`'d inside block render callbacks.
- [x] **V. Runtime tokens** — already done: `theme.json` + `main.scss` consume `design-tokens.json`
  exclusively via `--wp--preset--*`/`--wp--custom--perego--*`; this plan's new blocks consume the same,
  add zero new literals.
- [x] **VI. Conditional assets** — each of the 3 blocks declares its own `view.js`/`style.scss` in its
  own `block.json`; none are enqueued globally.
- [x] **VII. Declarative security** — N/A (no REST/AJAX routes in this feature); the language toggle is
  a pure client-side preference, not a privileged action.
- [x] **VIII. RTL-first** — header/footer/preloader CSS uses logical properties throughout; the mobile
  nav's slide direction mirrors via `inset-inline` rather than a `dir`-specific override.
- [x] **IX. No optional dep is hard** — `LanguageDriver` interface with `PolylangLanguageDriver`
  (adapts to Polylang's actual API) and `FallbackLanguageDriver` (cookie + `<html>` attribute swap, no
  plugin needed); `LanguageService` picks whichever driver is available. The site is not broken with
  Polylang absent.
- [x] **X. Spec is source of truth** — this plan traces to `spec.md`; any change in intent updates the
  spec first.
- [x] **Guard Gate + Definition of Done acknowledged** — `wp-guard` + `clean-code-guard` run on every
  PHP/block diff, `test-guard` on the Pest/Jest suites, `docs-guard` on this plan/spec and any README
  touched; Pest+Jest green; WCAG 2.2 AA (focus trap, `aria-*`, skip link already in `main.scss`); RTL
  verified; `PROGRESS.md`/`DECISIONS.md` updated in the same change as every task.

## Project Structure

### Documentation (this feature)

```text
sites/perego/specs/001-global-foundation/
├── plan.md              # This file
├── tasks.md             # Phase 2 output (next: /speckit-tasks, done manually per DECISIONS.md)
└── checklists/
    └── requirements.md  # Spec quality checklist (done)
```

### Source Code (client site: `sites/perego/`)

```text
sites/perego/
├── perego-site/                          # app/business code — PeregoSite\
│   └── src/
│       ├── Blocks/
│       │   ├── site-header/
│       │   │   ├── block.json
│       │   │   ├── render.php            # server-rendered markup, consumes LanguageService
│       │   │   ├── view.js               # Interactivity API: sticky, mobile menu, dropdown, lang toggle
│       │   │   └── style.scss
│       │   ├── site-footer/
│       │   │   ├── block.json
│       │   │   ├── render.php
│       │   │   └── style.scss
│       │   └── preloader/
│       │       ├── block.json
│       │       ├── render.php
│       │       ├── view.js               # Interactivity API: session-gated, reduced-motion-safe
│       │       └── style.scss
│       ├── Language/
│       │   ├── LanguageDriver.php        # interface: currentLocale(), isRtl(), switchUrl()...
│       │   ├── PolylangLanguageDriver.php
│       │   └── FallbackLanguageDriver.php
│       ├── Services/
│       │   └── LanguageService.php       # picks the active driver, exposes it to blocks
│       └── PeregoSiteServiceProvider.php # binds LanguageDriver -> LanguageService in the container
├── perego-site/tests/
│   ├── LanguageServiceTest.php
│   ├── FallbackLanguageDriverTest.php
│   └── Blocks/
│       ├── SiteHeaderRenderTest.php
│       ├── SiteFooterRenderTest.php
│       └── PreloaderRenderTest.php
├── perego-theme/
│   ├── theme.json                        # done (prior session)
│   ├── parts/header.html                 # <!-- wp:perego/site-header /-->
│   ├── parts/footer.html                 # <!-- wp:perego/site-footer /-->
│   ├── templates/front-page.html         # header part + wp:post-content + preloader block + footer part
│   ├── templates/index.html              # header part + wp:post-content + footer part (no preloader)
│   └── assets/src/{scss,js}/             # existing pipeline; JS tests live alongside as *.test.js
└── tests/e2e/                            # (later) Playwright, out of scope for this plan's Pest/Jest gate
```

**Structure Decision**: standard CoreX client-site split — all business logic, block registration, and
render/Interactivity JS in `perego-site` (the plugin); `perego-theme` only wires the three blocks into
its template parts/templates and carries the token-driven `theme.json`/SCSS. No new CPTs this feature.

## Complexity Tracking

*No constitution violations — table intentionally empty.*
