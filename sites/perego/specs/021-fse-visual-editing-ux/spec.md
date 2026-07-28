# Feature Specification: Perego FSE visual editing and backend UX

**Feature Branch**: `feature/021-fse-visual-editing-ux`  
**Created**: 2026-07-20  
**Status**: Planned implementation

## User Scenarios & Testing

### User Story 1 - Edit real shared chrome (Priority: P1)

An editor opens the Site Editor and sees the actual Perego header or either footer variant, with safe controls for the supported content and behavior rather than a text-only placeholder.

**Why this priority**: Shared chrome is present on every public route and is the most visible editor defect.

**Independent Test**: Edit a header CTA, a logo, and a footer contact/social item; save; compare the rendered public page against the protected baseline with unchanged defaults.

**Acceptance Scenarios**:

1. **Given** an existing template part, **When** it is opened in Site Editor, **Then** it displays the approved Perego component layout and its links cannot navigate the editor away.
2. **Given** an editor changes a supported attribute, **When** it is saved, **Then** English and Arabic output resolves correctly and unedited content retains its current presentation.

### User Story 2 - Compose home sections visually (Priority: P1)

An editor can compose, reorder, and directly edit Hero slides, Services, About, and Clients using visual previews and labelled native controls.

**Independent Test**: Edit and reorder a slide and service/client selection, then verify editor preview and public output at desktop and mobile widths.

### User Story 3 - Maintain Client, Service, and Project content confidently (Priority: P1)

An editor uses understandable media, taxonomy, relationship, priority, and grid-placement controls without raw IDs, comma-separated values, or internal meta names.

**Independent Test**: Create each record type, assign hierarchical terms and service-specific work, and verify selection, sorting, localization fallback, and public display.

### User Story 4 - Build controlled service pages (Priority: P1)

An editor selects a Default or Website Making service template and edits real Inner Hero, What We Do, Process, and portfolio sections in the Service editor.

**Independent Test**: Edit each supported section on a translated service; verify visual output and localized links at 1440, 768, and 375 pixels.

### User Story 5 - Preserve the approved public site (Priority: P1)

Visitors receive the identical public frontend, including visual design, HTML semantics, interactions, responsive behavior, accessibility, and RTL behavior.

**Independent Test**: Run the route/viewport/interactions baseline matrix before and after every implementation slice; unexplained differences fail the slice.

### Edge Cases

- A queried entity has no final media, translation, or term.
- An editor has no permission to edit a referenced entity or media item.
- A saved legacy attribute/meta value is malformed or references a deleted record.
- A service is renamed or translated; template selection and service relations remain stable.
- JavaScript is unavailable in the public view; server-rendered content remains useful.

## Requirements

### Functional Requirements

- **FR-001**: The editor MUST render the actual approved Perego design for every in-scope visual block; placeholder-only previews are forbidden.
- **FR-002**: Shared Header, Standard Footer, and Flat Footer MUST be visually editable with locked required structure and editor-safe interactions.
- **FR-003**: Header Services navigation MUST support automatic published-service records, manual order/exclusions, short labels, and localized permalinks while retaining migrated defaults.
- **FR-004**: Hero, Services, About, and Clients MUST support visual, labelled editing and preserve the existing public output by default.
- **FR-005**: Clients MUST provide a polished media and metadata editor, hierarchical Client Type selection, validation, and a card preview.
- **FR-005a** (owner, 2026-07-28): Client Type MUST be the first choice in the Client editor and MUST decide which other fields exist — a Corporate client is a logo tile (logo, name), an Individual client is a card (thumbnail, title, subtitle, body content). Fields the chosen type cannot use MUST NOT be offered.
- **FR-005b**: Every client MUST carry an explicit **Behavior** — `No actions` (default), `Lightbox`, or `Link` — and a card MUST be interactive only when its behaviour says so. No behaviour may be inferred from which fields happen to be filled in. `Lightbox` MUST offer one ordered gallery accepting multiple images, multiple uploaded videos, and multiple YouTube/Vimeo links; `Link` MUST offer a single destination. An Individual client MUST additionally control its own play badge.
- **FR-005c** (owner, 2026-07-28): A project MUST carry an explicit **Icon** — `No icon` (default), `Show play icon`, or `Gallery label` — which decides what its tile advertises in BOTH the work grid and the services grid. No affordance may be inferred from the project's media. What a tile opens remains media-derived and is a separate concern.
- **FR-005d**: A project MUST be able to supply a distinct thumbnail per tile shape, and each grid MUST render the crop matching the shape that tile occupies at the current layout band. Every crop is optional, falling back to the nearest shape and then the featured image. Resolution MUST NOT depend on client-side JavaScript.
- **FR-006**: Project Service and Client Type taxonomies MUST be hierarchical while preserving existing term assignments, localized relations, filtering, and compatible URLs.
- **FR-007**: Services MUST provide explicit, translation-safe template assignment plus visual Inner Hero, What We Do, Process, and selected-work editing.
- **FR-008**: Service portfolio selection MUST support automatic, manual, and hybrid selection, ordering, exclusions, and supported grid placement without raw identifiers.
- **FR-009**: All data inputs and render paths MUST validate, sanitize, authorize, escape, and fail safely.
- **FR-010**: Existing content MUST migrate idempotently with a documented rollback path; no editor data loss is permitted.
- **FR-011**: All visible editor UI MUST be keyboard-operable, WCAG 2.2 AA, translation-ready, and RTL-aware.
- **FR-012**: Every implementation slice MUST pass public visual/interaction regression verification against the frozen EN/AR viewport baseline.

### Key Entities

- **Editor component contract**: reusable, labelled controls and editor-only preview state.
- **Display configuration**: versioned block attributes and post metadata that select/query/order content without replacing canonical records.
- **Service template assignment**: stable identity separate from translated slugs.
- **Client Type / Project Service**: hierarchical taxonomies retaining prior assignments.
- **Portfolio placement**: an ordered service-to-project relationship with optional supported grid placement.

## Success Criteria

- **SC-001**: Editors can complete each supported content action without entering an attachment ID, raw meta key, or comma-separated relation.
- **SC-002**: All in-scope editor previews display real component structure and visual styling; zero placeholder-only blocks remain.
- **SC-003**: The protected public route matrix has no unexplained visual, functional, accessibility, RTL, console, or overflow regression after every shipped slice.
- **SC-004**: Existing Client, Service, Project, Header, Footer, and homepage data survive migration and render equivalently in both languages.

## Assumptions

- Existing approved public rendering and design handoff are the immutable baseline; editor-only assets solve preview differences.
- Native WordPress/Gutenberg controls are sufficient; ACF remains optional and is not introduced as a dependency.
- Work is delivered as ordered, independently verifiable slices on this stacked feature branch.
