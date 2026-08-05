# Implementation Plan: Lighthouse Client Performance

**Branch**: `fix/026-lighthouse-client-performance` | **Date**: 2026-07-30 |
**Spec**: [spec.md](spec.md)  
**Input**: Feature specification from
`sites/perego/specs/026-lighthouse-client-performance/spec.md`

## Summary

Reduce the homepage's first-party image transfer cost, correct missing image
dimensions and invalid carousel roles, add a client-owned static-asset cache
policy, and verify results in a clean browser profile. Perego will consume the
existing `Corex\Assets\Image` contract from its own PHP renderers and emit
equivalent `<picture>` markup in static FSE templates. CoreX source, runtime
WordPress, and distribution output remain read-only.

## Technical Context

**Language/Version**: PHP 8.3+, JavaScript ES modules on Node 20+, WordPress 7.0+  
**Primary Dependencies**: CoreX public Assets API, WordPress render APIs, Sharp
0.33, Sass, `@wordpress/scripts`  
**Storage**: Tracked source images and generated theme image artifacts; no
database change  
**Testing**: Pest, Jest, Playwright, Lighthouse, Sharp metadata verification  
**Target Platform**: Apache/WAMP locally and the existing Cloudflare-fronted
WordPress production host  
**Project Type**: Client WordPress plugin plus FSE block theme  
**Performance Goals**: At least 60% combined reduction for the three audited
large images; no regression below the supplied 97 Performance score  
**Constraints**: Client-only changes; preserve bilingual visual fidelity; retain
fallback images; do not edit CoreX, `wp/wp-content/`, or `dist/`  
**Scale/Scope**: Homepage and shared header/footer/preloader media plus the client
asset build and cache policy

## Constitution Check

*GATE: Must pass before implementation and again after design.*

- **Role Gate**: PASS — Client Site Mode; all planned runtime edits are within
  `sites/perego/`.
- **Model Routing Gate**: PASS — Route H because the change spans media build,
  PHP renderers, FSE templates, server caching, tests, and documentation; the
  parent remains the sole writer.
- **Spec-first**: PASS — specification, clarification record, quality checklist,
  plan, research, data model, quickstart, and tasks precede production edits.
- **Source ownership**: PASS — CoreX and runtime/generated WordPress paths are
  explicitly excluded.
- **Thin rendering / public contracts**: PASS — renderers consume the existing
  CoreX media API; no new service container dependency or data access is added.
- **Accessibility and RTL**: PASS — native semantics, intrinsic dimensions, and
  EN/AR responsive checks are acceptance requirements.
- **Testing and Guard Gate**: PASS — focused Pest/Jest/Playwright tests,
  Lighthouse, and clean-code/WP/test/docs guards are required before handoff.
- **Versioning and cache busting**: PASS — client theme version changes with
  generated asset changes.

No constitutional violation requires an exception.

## Project Structure

### Documentation (this feature)

```text
sites/perego/specs/026-lighthouse-client-performance/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── checklists/
│   └── requirements.md
├── spec.md
└── tasks.md
```

### Source Code (repository root)

```text
sites/perego/
├── perego-site/
│   ├── src/Blocks/                  # PHP server renderers
│   └── tests/Blocks/                # renderer regression tests
├── perego-theme/
│   ├── assets/src/images/           # canonical raster sources
│   ├── assets/images/               # deterministic optimized artifacts
│   ├── assets/src/scss/             # editable client adapter styles
│   ├── scripts/                     # image build and verification
│   ├── templates/                   # static FSE picture markup
│   ├── .htaccess                    # client asset cache policy
│   ├── package.json
│   └── style.css
├── DECISIONS.md
└── PROGRESS.md
```

**Structure Decision**: Keep build-time optimization in the client theme, media
markup in the existing client renderers/templates, and behavioral tests in the
client plugin. No generic media abstraction is added to Perego because CoreX
already owns that public contract.

## Phase 0: Research

Research resolves:

1. Which supplied Lighthouse findings are reproducible first-party defects.
2. How the existing CoreX Assets API selects WebP and fallback sources.
3. Which image quality/compression settings meet both transfer and fidelity
   goals.
4. How PHP renderers and non-PHP FSE templates can share equivalent output
   semantics.
5. Which cache lifetime is safe for stable filenames and versioned assets.

See [research.md](research.md).

## Phase 1: Design

- Treat image files as deterministic build artifacts derived from canonical
  client sources.
- Use CoreX's public image helper in PHP renderers and matching standards-based
  markup in static templates.
- Preserve native roles on interactive carousel cards and group the carousel
  without incompatible list-item overrides.
- Apply a finite client asset cache lifetime; do not claim immutable caching for
  stable un-hashed image filenames.
- Verify dimensions, byte budgets, response headers, clean-profile audits, and
  EN/AR responsive rendering.

See [data-model.md](data-model.md) and [quickstart.md](quickstart.md).

## Phase 2: Implementation Strategy

1. Add failing renderer and asset-contract tests.
2. Make the theme image build deterministic and compression-aware; introduce
   canonical inputs for currently output-only assets.
3. Rebuild tracked image artifacts and compare dimensions/bytes.
4. Update PHP renderers and static templates to select WebP with valid fallbacks
   and intrinsic dimensions.
5. Correct carousel roles without changing interaction or direct-child layout.
6. Add the client cache policy and theme cache-busting version.
7. Run focused then full test/build suites, local HTTP checks, clean Lighthouse,
   and responsive screenshots.
8. Run the required guards and record verified outcomes in client durable
   memory.

## Post-Design Constitution Check

- Planned files remain under `sites/perego/`: PASS.
- No framework, runtime, generated distribution, database, or public contract
  change: PASS.
- Existing CoreX helper is consumed rather than duplicated in PHP: PASS.
- Static-template duplication is limited to markup that cannot invoke PHP:
  PASS, documented in research.
- Tests precede production implementation for changed behavior: PASS by task
  ordering.
- Asset version and rollback path are explicit: PASS.

## Complexity Tracking

No constitutional exception or unjustified new abstraction is introduced.
