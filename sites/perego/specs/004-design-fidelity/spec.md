# Feature Specification: Design Fidelity Recovery

**Feature Branch**: `feature/004-design-fidelity`  
**Created**: 2026-07-12  
**Status**: Active  
**Input**: The final design handoff at `_design_handoff/Perego-Creative-Studio-Final-Handoff/site/` is the binding visual, responsive, interaction, content, and asset reference. No redesign or visual reinterpretation is permitted.

## User Scenarios & Testing

### User Story 1 - Approved home experience (Priority: P1)

As a visitor, I can use the English and Arabic home page at every supplied breakpoint and see the same hierarchy, spacing, imagery, typography, controls, and motion as the final handoff.

**Why this priority**: Home is the primary entry point and establishes the reusable visual system for every route.

**Independent Test**: Capture deterministic English and Arabic desktop/mobile screenshots of the live home page and compare each state with the corresponding handoff render; record every deviation and close it before approval.

**Acceptance Scenarios**:

1. **Given** the home page in English or Arabic, **When** it is viewed at a handoff breakpoint, **Then** its rendered sections, assets, controls, and layout match the approved reference without intentional design changes.
2. **Given** a visitor uses motion, navigation, carousel, or language controls, **When** the interaction completes, **Then** its state and accessible behavior match the reference and remain usable with keyboard and reduced motion.

---

### User Story 2 - Approved content-route experience (Priority: P1)

As a visitor, I can browse services, work, projects, journal, legal, search, contact, and not-found pages that faithfully reproduce their matching handoff templates in both languages.

**Why this priority**: A visually complete home page is insufficient when primary conversion and content routes still diverge from the supplied design.

**Independent Test**: For each route, capture its default and required interactive states in English and Arabic at the defined breakpoints; approve only when comparison evidence records no unresolved material difference.

**Acceptance Scenarios**:

1. **Given** any supplied handoff route, **When** its Perego counterpart renders with matched seeded content, **Then** the structure, content hierarchy, tokens, imagery treatment, and responsive layout match the reference.
2. **Given** a required state such as filtering, navigation, form validation, lightbox, legal TOC, or a search empty result, **When** it is activated, **Then** the state matches the handoff and remains accessible.

---

### User Story 3 - Editor-owned, launchable content (Priority: P2)

As a Perego editor, I can replace approved content and assets without losing the accepted design, while visitors never see unapproved demo content, placeholder media, or unreviewed legal copy at launch.

**Why this priority**: The current site contains intentional demo records and placeholder media, which are valid during development but not for release.

**Independent Test**: Replace a seeded record and asset in each content type through WordPress, verify it renders in the correct language and layout, and audit the public routes for placeholder and draft notices.

## Edge Cases

- A reference state has no supplied screenshot: render the supplied static handoff at the same viewport and use that deterministic capture as the baseline.
- An approved production asset or final business/legal copy is unavailable: preserve an explicit tracked blocker; do not silently invent a substitute or mark the route launch-ready.
- Arabic and English have different Polylang slugs: compare each route using its actual translated URL, never a guessed URL pattern.
- A visual match would violate accessibility: preserve the approved visual treatment where possible and record the smallest necessary accessible adjustment with comparison evidence.

## Requirements

### Functional Requirements

- **FR-001**: The handoff directory named above MUST be used as the sole design reference for every visual decision in this feature.
- **FR-002**: Every handoff route and required state MUST have a mapped Perego route, owner, status, and comparison evidence.
- **FR-003**: No route or section may be declared visually complete without English and Arabic evidence at its required breakpoints.
- **FR-004**: Existing route-health, accessibility, RTL, and interaction checks MUST remain green while visual differences are corrected.
- **FR-005**: Visible editorial prose MUST be migrated to editor-canvas content where the implementation prompt requires it; runtime providers may supply defaults only.
- **FR-006**: Placeholder assets, demo business content, and legal-review copy MUST be separately tracked and must block launch approval for the affected route.
- **FR-007**: The authoritative task register, progress record, and decisions record MUST remain synchronized with each completed slice.
- **FR-008**: Every UI change MUST be token-driven, RTL-correct, conditionally loaded, translation-ready, and WCAG 2.2 AA verified.

### Key Entities

- **Route acceptance record**: A route/language/viewport/state comparison result, including baseline, capture, differences, decision, and approval status.
- **Design baseline**: A deterministic render of a supplied handoff page or screenshot for one route, viewport, language, and state.
- **Launch-content record**: A content or asset item marked approved, demo, placeholder, or legal-review-required.

## Success Criteria

### Measurable Outcomes

- **SC-001**: All 16 supplied handoff templates have a mapped Perego route and an explicit acceptance status.
- **SC-002**: Every approved route has comparison evidence for English and Arabic at desktop and mobile widths, with no unresolved material visual differences.
- **SC-003**: All required keyboard, reduced-motion, RTL, overflow, console, and automated accessibility checks pass for approved routes.
- **SC-004**: A launch review identifies zero untracked placeholders, demo records, or unreviewed legal content on public production routes.

## Assumptions

- The final handoff under `_design_handoff/Perego-Creative-Studio-Final-Handoff/` is complete and supersedes earlier design material.
- The current WordPress runtime and Perego client source remain the implementation target; CoreX framework directories are out of scope.
- Real client assets and final legal/business content will be supplied by the owner before launch where the handoff intentionally provides examples.
