# Feature Specification: Local Font Delivery

**Feature Branch**: `feature/006-local-font-delivery`  
**Created**: 2026-07-13  
**Status**: Active  
**Input**: Replace the remote Google font request with locally served Open Sans and Cairo files while preserving the locked handoff’s typography.

## User Scenarios & Testing

### User Story 1 - Reliable matching typography (Priority: P1)

As a visitor, I see the approved Open Sans English typography and Cairo Arabic typography without the page requesting a third-party font host.

**Why this priority**: The remote request currently fails in the browser verification environment on every normal route, preventing reliable visual acceptance.

**Independent Test**: Load English and Arabic routes in a browser with external network access denied; all required local font files load successfully and no Google Fonts request is present.

**Acceptance Scenarios**:

1. **Given** an English route, **When** it loads, **Then** it uses the locally served Open Sans weights required by the handoff.
2. **Given** an Arabic route, **When** it loads, **Then** it uses the locally served Cairo weights required by the handoff and retains correct RTL layout.
3. **Given** any verified route, **When** browser resources are inspected, **Then** no request is made to `fonts.googleapis.com` or `fonts.gstatic.com`.

## Requirements

- **FR-001**: The site MUST serve Open Sans weights 300, 400, 600, and 700 from Perego theme assets.
- **FR-002**: The site MUST serve Cairo weights 400, 600, and 700 from Perego theme assets.
- **FR-003**: Remote Google Fonts stylesheet and preconnect tags MUST be removed.
- **FR-004**: Theme font presets and EN/AR language font selection MUST remain unchanged in visitor-facing behavior.
- **FR-005**: Browser route-health, a11y, interaction, Pest, and Jest gates MUST remain green.

## Success Criteria

- **SC-001**: The 72-check route-health run reports zero broken resources.
- **SC-002**: Browser captures use the handoff’s English and Arabic font families without external font requests.
- **SC-003**: Existing automated unit, accessibility, and interaction suites remain green.

## Assumptions

- The Open Sans and Cairo webfont files may be obtained from their open-licensed Fontsource packages because the locked handoff specifies these exact families but supplies no local files.
