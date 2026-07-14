# Spec 010 — Tasks

## Phase 0 — Audit (done)

- [x] T001 Read-only runtime audit — active plugins, CoreX version, REST routes, admin menu, data sources.
    Findings in `evidence/runtime-audit.md`: backend healthy; all admin pages registered; only
    `submissions` data source; ACF active (flag); Projects/Services/Clients not registered as models.

## Phase 1 — Admin surface verification (browser)

- [x] T002 Logged into wp-admin (perego.local) as admin. **Found + fixed the release-blocker:** the CoreX
    admin React bundle `corex-config/build/admin/index.js` was 404 (never built) — pages rendered their PHP
    heading shell but the functional app never mounted. Built it (deployment step, gitignored, no framework
    source edited); asset now serves 200. Logged as a **framework/deployment gap** (deploy must build CoreX
    admin assets) — see `evidence/runtime-audit.md` + DECISIONS. Full interactive click-through of each page
    remains a follow-up (browser tooling was flaky), but the root cause is resolved.
- [x] T003 ACF is active but **not used by Perego** (no `acf_*`/`get_field` in perego-site) and **not
    required by any CoreX plugin/addon** (grep-confirmed). Standalone owner plugin; Perego metadata is
    native — FR-5 satisfied. Documented in `docs/corex-framework-gaps.md`. (Whether ACF stays active is an
    owner decision; not deactivating it — owner's plugin.)

## Phase 2 — Forms as CoreX flows

- [ ] T004 Inventory how the 3 Perego forms currently register (QuickMessageForm, ProjectBriefForm,
    careers endpoint) and whether they appear as flows in Forms & Flows.
- [ ] T005 Provision the 3 flows via the public seam idempotently (create-if-absent, stable slugs, no
    overwrite); bind frontend blocks by slug; verify each appears in Forms & Flows.
- [ ] T006 Submit each form on the frontend; verify the submission appears in CoreX Submissions with
    correct EN/AR field labels and routing.

## Phase 3 — Data Models

- [~] T007 **BLOCKED — framework gap (GAP-1).** `DataRegistry` only supports the framework submissions
    source + custom managed DB tables; there is no public seam/adapter to register a CPT-backed source, so
    Projects/Services/Clients cannot appear in Data Models without coupling to framework internals.
    Recorded in `docs/corex-framework-gaps.md` (GAP-1) for a CoreX Framework Mode task. Not faking it.
- [~] T008 Blocked by T007/GAP-1. Models surface in Data Models once CoreX ships the seam. Meanwhile the
    CPTs remain fully editable via native post editors + registered meta (Phase 4).

## Phase 4 — Structured metadata editor controls

- [ ] T009 Register Project/Service/Client structured metadata (REST schema, sanitization, auth, editor
    UI) via native WP APIs + CoreX contracts — not ACF, not generic Custom Fields.
- [ ] T010 Prove a new Project/Service/Client is completable in wp-admin (metadata + gallery/media)
    without CLI or DB editing. Browser evidence.

## Phase 5 — Delivery

- [ ] T011 Full suite green; guards; live verify. Update PROGRESS/DECISIONS/roadmap. Open PR.
