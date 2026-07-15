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

- [x] T004 Forms inventory (`/corex/v1/forms` as admin returns 3): `perego-quick-message` (footer, extends
    `Corex\Forms\Form`, stable slug) and `perego-project-brief` (contact) are **real CoreX forms** through
    the public `Corex\Forms\Form` API — they run through the CoreX engine so their submissions persist. The
    **careers/join** form is a **separate custom endpoint** (`perego/v1/careers/apply`), NOT a CoreX form,
    because CoreX Forms has no file-upload field type (needs the CV upload — see DECISIONS Decision 7 /
    corex-careers). So 2 of 3 are CoreX forms; the 3rd is a documented framework limitation.
- [~] T005 The 2 CoreX forms already register via the public `Corex\Forms\Form` API with stable slugs
    (`perego-quick-message`, `perego-project-brief`) and appear in `/corex/v1/forms`; frontend blocks bind
    by slug. **Remaining:** confirm they render/edit in the Forms & Flows *screen* (interactive admin, now
    that the admin bundle is built) and decide whether code-forms need mirroring as persisted DB flows.
    The careers form stays a custom endpoint (file-upload gap).
- [x] T006 (substantially) Submissions land in CoreX Submissions — `/corex/v1/submissions` (admin) returns
    real entries incl. id 150 from flow `perego-project-brief`. Pipeline works end-to-end for the CoreX
    forms. Remaining: per-form fresh test submission + EN/AR label spot-check (interactive), tracked to 016.

## Phase 3 — Data Models

- [~] T007 **BLOCKED — framework gap (GAP-1).** `DataRegistry` only supports the framework submissions
    source + custom managed DB tables; there is no public seam/adapter to register a CPT-backed source, so
    Projects/Services/Clients cannot appear in Data Models without coupling to framework internals.
    Recorded in `docs/corex-framework-gaps.md` (GAP-1) for a CoreX Framework Mode task. Not faking it.
- [~] T008 Blocked by T007/GAP-1. Models surface in Data Models once CoreX ships the seam. Meanwhile the
    CPTs remain fully editable via native post editors + registered meta (Phase 4).

## Phase 4 — Structured metadata editor controls

- [~] T009 **In progress.** ✅ Project: registered all 5 structured meta fields via `register_post_meta`
    (`_perego_client/_year/_role/_deliverables` as sanitized strings; `_gallery_attachment_ids` as a typed
    integer list) with `show_in_rest`, `sanitize_callback`, and `auth_callback` — verified registered
    (show_in_rest=1) and REST-exposed on project #84 with existing values intact; Pest +2 (235 total).
    ✅ Service (`_perego_service_slug`) + Client (`_perego_client_stat`, `_perego_client_video_url`) meta
    registered too (Pest +2). ✅ **Editor UI:** `PeregoSite\Admin\PostMetaBoxes` adds a "Project/Service/
    Client details" meta box (admin-only) with labelled text inputs for the scalar fields and a guarded
    `save_post` handler (nonce + `edit_post` cap + autosave/revision guards; sanitises via each field's
    registered callback; deletes on empty). Unit-tested (schema + all save guards + sanitize + delete);
    verified all 3 meta boxes register. **Remaining:** a real media UI (wp.media) for the Project gallery
    (`_perego_gallery_attachment_ids`) instead of relying on seeds — follow-up.
    (History: Service/Client previously had no `register_post_meta`.)
    all meta is seed-only `update_post_meta`. Register every field via native `register_post_meta`
    (`show_in_rest` + `type` + `sanitize_callback` + `auth_callback`) + editor UI (block-editor
    `PluginDocumentSettingPanel` or classic meta boxes). Fields per `spec.md`. Confirmed stored keys today:
    Project `_perego_client/_year/_role/_deliverables/_gallery_attachment_ids` (+ `_thumbnail_id`); Service
    + Client keys to enumerate from their seeds. No ACF; no generic Custom Fields.
- [x] T010 (scalar fields) Proved a new Project is completable through the registered controls: created a
    draft via the editor path and read the meta back via `/wp/v2/perego_project/{id}` with `context=edit`
    (`client/year/gallery` all exposed), then cleaned up (0 probes left). The meta-box save is separately
    unit-tested (nonce/cap/autosave guards). **The rich gallery media picker (wp.media) moves to spec 014**
    (Work/Project — "gallery editable with a real media UI"). Browser screenshot deferred: the local
    playwright-cli admin context proved unreliable (open/snapshot/run-code use different page contexts);
    the REST edit-context round-trip is the authoritative editor-path proof here.

## Phase 5 — Delivery

- [x] T011 Pest 243/243, Jest 76/76; home/work/services + AR 200; no fatals; PROGRESS/DECISIONS/roadmap +
    `corex-framework-gaps.md` updated. Spec 010 PR opened. **Spec 010 delivers:** admin-bundle fix (GAP-2),
    forms/submissions verification, structured metadata + editor controls for all 3 CPTs. **Deferred with
    reason:** T005 interactive Forms&Flows screen click-through + T007/T008 (framework GAP-1) + gallery
    media UI (→ spec 014).
