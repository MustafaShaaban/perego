# Feature Specification: Project-single completion

**Feature Branch**: `feature/007-project-single-completion`
**Created**: 2026-07-13
**Status**: Active
**Input**: Complete the locked final handoff's project-detail gallery/lightbox, adjacent-project navigation, and related-project section without introducing a redesign.

## User Scenarios & Testing

### User Story 1 - Inspect project media (Priority: P1)

As a visitor, I can open the three approved project-gallery images in an accessible lightbox from a project single.

**Independent Test**: A seeded English and Arabic project single renders three media cards; keyboard and pointer interaction open, navigate, and close the dialog with focus restoration.

### User Story 2 - Continue exploring work (Priority: P1)

As a visitor, I can move to adjacent work and see three related projects plus the approved Start a Project CTA.

**Independent Test**: A project single renders language-matched adjacent links and three language-matched cards excluding the current project.

## Requirements

- **FR-001**: The existing server-rendered gallery block MUST be registered and included in the project-single FSE template.
- **FR-002**: Default seed data MUST assign exactly three approved handoff images to every EN and AR demo project, idempotently, without overwriting editor changes.
- **FR-003**: The dialog MUST retain its existing accessible keyboard, focus-management, counter, and reduced-JavaScript-safe behavior.
- **FR-004**: Adjacent and related project content MUST be queried dynamically, resolve the current Polylang language, and exclude the current project.
- **FR-005**: Labels, accessible names, and CTA text MUST use the current language's existing content map.
- **FR-006**: The layout, cards, spacing, and interactions MUST follow `site/project.html`; no new design or unapproved imagery may be introduced.

## Success Criteria

- **SC-001**: English and Arabic project singles render the gallery, adjacent links, related cards, and CTA with no broken assets.
- **SC-002**: The existing gallery unit and interaction tests pass, along with the supported Pest, Jest, route-health, accessibility, and interaction gates.
- **SC-003**: The project-single visual-acceptance evidence has no unimplemented handoff rows.
