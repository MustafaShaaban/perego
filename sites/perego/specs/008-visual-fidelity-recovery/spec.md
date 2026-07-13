# Feature Specification: Visual fidelity recovery

**Branch**: `feature/008-visual-fidelity-recovery`
**Created**: 2026-07-13
**Status**: Active
**Input**: Recover the Perego FSE frontend so its rendered English and Arabic site is visually identical to the locked Final Design & Developer Handoff.

## User Scenarios & Testing

### User Story 1 - Faithful global shell (P1)

Visitors receive the exact handoff header, navigation/dropdown, global fields, and footer rather than generic WordPress output.

**Independent test**: Deterministic desktop/mobile EN/AR captures of the handoff and WordPress render have reviewed baseline/actual/diff artifacts for default and interactive shell states.

### User Story 2 - Faithful editable routes (P1)

Visitors see every handoff route with the same DOM/class contract, assets, typography, spacing, responsive layout, and interactions while editors retain canvas-managed content.

**Independent test**: Every route has matching captures at all required widths in EN and AR and no unreviewed structural visual difference.

### User Story 3 - Production-safe dynamic behavior (P1)

Editors retain FSE, Polylang Free, CoreX forms, submissions, and dynamic content without generic framework styling leaking into the handoff experience.

**Independent test**: Existing content, translations, forms, and server-rendered routes work with the recovered presentation layer; quality and security gates pass.

## Functional Requirements

- **FR-001**: Treat `_design_handoff/Perego-Creative-Studio-Final-Handoff/site/css/styles.css` and its rendered prototype as the authoritative CSS and DOM contract.
- **FR-002**: Preserve reference component classes, hierarchy, data hooks, assets, typography, dimensions, breakpoints, and behavior in FSE-compatible server-rendered blocks and templates.
- **FR-003**: Separate the faithful reference stylesheet from minimal WordPress/CoreX adapter and editor styles; do not replace it with an approximate token system.
- **FR-004**: Record and correct all WordPress/CoreX style interference through the smallest scoped client-side adapter.
- **FR-005**: Rebuild global shell first, then each mapped route and its required interactive states in English and Arabic.
- **FR-006**: Keep visible editorial prose editable on the block editor canvas and retain only structural/behavioral settings outside it.
- **FR-007**: Capture and retain deterministic baseline, actual, and diff evidence for every required route, viewport, language, and state.
- **FR-008**: Never mark a route visually accepted based only on functional, DOM, accessibility, or resource checks.

## Success Criteria

- **SC-001**: All 16 handoff routes and required states have reviewed EN/AR visual evidence at 320, 375, 430, 768, 1024, 1280, 1440, and wide desktop.
- **SC-002**: No component has an unresolved structural, spacing, typography, asset, form-control, or responsive mismatch against the locked handoff.
- **SC-003**: Full functional, accessibility, security, SEO, performance, build, and visual suites pass without weakening thresholds or exclusions.

## Assumptions

- Final production content and counsel approval remain external launch inputs, but they do not prevent completing the faithful demo/default presentation layer.
