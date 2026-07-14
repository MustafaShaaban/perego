# Spec 010 — CoreX Runtime, Forms, Submissions, and Content Models

**Branch:** `feature/010-corex-runtime-content-models`
**Mode:** Client Site Mode
**Status:** In progress (runtime audit done 2026-07-14)
**Depends on:** none hard (independent of 009's code). **Blocks:** 016 (contact/forms/email).

## Problem

The live dashboard was reported not to expose expected Forms & Flows, Submissions, and Data Models
functionality. The read-only audit (`evidence/runtime-audit.md`) shows the CoreX backend is actually
healthy: all addons active (v0.33.0), 82 `/corex/v1` REST routes, the full admin menu registered, and the
data API answering 200 for admin. So the real gaps are narrower:

1. Whether the admin **pages render** for admin in a browser (built-asset/capability), vs a dead backend.
2. Only the `submissions` data source is registered — **Projects/Services/Clients are not** CoreX data
   models.
3. The 3 Perego forms may still be code-defined rather than persisted CoreX flows visible in Forms & Flows.
4. **ACF is active** — must not become a required dependency for Perego metadata.

## Goal

Make the required CoreX admin surfaces genuinely usable for an administrator, provision the three Perego
forms as persisted CoreX flows (idempotently), register Projects/Services/Clients with CoreX Data Models
via the public seam (truthful capabilities only), and ensure structured metadata is editable through
proper registered controls — without duplicating CoreX-owned functionality or requiring ACF.

## Functional requirements

- **FR-1** Confirm (browser, admin) that Overview, Forms & Flows, Submissions, Data Models, Data, Email
  Studio, Access & Abilities, Operations & Security, Insights render — capture evidence. Fix any
  built-asset/capability breakage found (client-side; a framework bug → CoreX Framework Mode task).
- **FR-2** Provision the 3 flows (footer quick message, project brief/contact, careers/join) as persisted
  CoreX flows via the public seam: create-if-absent, stable slugs, never overwrite owner edits; frontend
  blocks bind by slug; each appears in Forms & Flows; submissions land in Submissions.
- **FR-3** Register Projects, Services, Clients as CoreX data sources via the public DataRegistry seam,
  advertising only truthful capabilities (read/query/schema/detail; mutations only with a real safe
  adapter). If no public CPT-backed adapter exists → record a framework gap, do not fake it.
- **FR-4** Structured metadata for Projects/Services/Clients registered via native WP metadata APIs +
  CoreX contracts (REST schema, sanitization, auth, editor UI), editable in the post editors — **not** ACF
  and **not** the generic Custom Fields panel. A new Project/Service/Client is completable in wp-admin
  without CLI or direct DB edits.
- **FR-5** ACF must not be a *required* dependency; document its role and keep Perego metadata independent.

## Acceptance criteria

- [ ] Forms & Flows, Submissions, Data Models render for admin (browser evidence).
- [ ] 3 Perego flows visible in Forms & Flows; real submissions appear in Submissions.
- [ ] Projects/Services/Clients visible in Data Models with truthful capabilities (or a documented gap).
- [ ] Structured metadata editable via registered controls; no ACF requirement; no hidden-meta-only path.
- [ ] A new Project/Service/Client can be completed in wp-admin without CLI/DB.

## Risks / notes

- Framework gaps (e.g. no public CPT mutation adapter) get a separate CoreX Framework Mode task — not a
  private Perego duplicate.
- Existing Perego forms already register into CoreX (`QuickMessageForm`, `ProjectBriefForm`) and the
  careers endpoint — verify how they surface as flows before adding anything.
