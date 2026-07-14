# Spec 010 — Tasks

## Phase 0 — Audit (done)

- [x] T001 Read-only runtime audit — active plugins, CoreX version, REST routes, admin menu, data sources.
    Findings in `evidence/runtime-audit.md`: backend healthy; all admin pages registered; only
    `submissions` data source; ACF active (flag); Projects/Services/Clients not registered as models.

## Phase 1 — Admin surface verification (browser)

- [ ] T002 Log into wp-admin (perego.local, admin creds — never logged/committed) and verify Overview,
    Forms & Flows, Submissions, Data Models, Data, Email Studio, Access, Operations, Insights render (not
    blank/error). Capture screenshots (no credentials visible). Diagnose any built-asset/capability break.
- [ ] T003 Determine ACF's role (CoreX-kit dependency vs owner choice); document; confirm Perego metadata
    does not require ACF.

## Phase 2 — Forms as CoreX flows

- [ ] T004 Inventory how the 3 Perego forms currently register (QuickMessageForm, ProjectBriefForm,
    careers endpoint) and whether they appear as flows in Forms & Flows.
- [ ] T005 Provision the 3 flows via the public seam idempotently (create-if-absent, stable slugs, no
    overwrite); bind frontend blocks by slug; verify each appears in Forms & Flows.
- [ ] T006 Submit each form on the frontend; verify the submission appears in CoreX Submissions with
    correct EN/AR field labels and routing.

## Phase 3 — Data Models

- [ ] T007 Register Projects/Services/Clients via the public DataRegistry seam with truthful capabilities
    (read/query/schema/detail; mutations only with a real adapter). If unsupported → framework-gap doc +
    separate CoreX task; do not fake.
- [ ] T008 Verify the models appear in Data Models with accurate schema/labels/capabilities.

## Phase 4 — Structured metadata editor controls

- [ ] T009 Register Project/Service/Client structured metadata (REST schema, sanitization, auth, editor
    UI) via native WP APIs + CoreX contracts — not ACF, not generic Custom Fields.
- [ ] T010 Prove a new Project/Service/Client is completable in wp-admin (metadata + gallery/media)
    without CLI or DB editing. Browser evidence.

## Phase 5 — Delivery

- [ ] T011 Full suite green; guards; live verify. Update PROGRESS/DECISIONS/roadmap. Open PR.
