# Spec 010 — CoreX runtime audit (2026-07-14, local WAMP via wp-cli)

Read-only audit of the deployed CoreX runtime. Target: WAMP install (`wp/`, perego.local); ngrok mirrors it.

## Active plugins (all v0.33.0 unless noted)

corex-core, corex-config, corex-blocks, corex-forms, corex-ui, corex-kit-company, corex-media,
corex-captcha, corex-careers, corex-email, corex-newsletter — **all active**. Plus `perego-site` 0.1.0,
`polylang` 3.8.5, and **`advanced-custom-fields` 6.8.5 — ACF is ACTIVE**.

> ⚠ **ACF is active.** The program rule is "do not introduce ACF as a required dependency." Need to
> determine whether ACF is (a) an owner/CoreX-kit dependency or (b) removable. Perego's own metadata must
> not *require* ACF. Flagged for a decision — see Open questions.

## CoreX REST — healthy

82 `/corex/v1/*` routes registered, including: `submissions` (+ notes/reply/resend/email-log/bulk/exports),
`access`, `security`, `activity`, `jobs`, `data/sources` + `data/{source}` (+ mutations/imports/exports/
migrations), `insights`. Authenticated as admin, `GET /corex/v1/data/sources` → **200**.

## CoreX admin menu — fully registered

Top-level "COREX FRAMEWORK" (`corex-settings`) with submenus: Overview, Settings, Add-ons,
**Forms & Flows**, **Submissions**, **Data Models**, Operations & Security, Access & Abilities, Blog Pro,
**Email Studio**, **Data**, Insights. **The reported "dashboard does not expose Forms/Submissions/Data
Models" is NOT a menu-registration or backend problem** — the pages are registered and the REST API answers.
The remaining hypothesis is admin-app rendering (built JS assets) or a capability gate on page load →
**must be confirmed in a real admin browser session** (next task).

## Data Models — only `submissions` registered

`GET /corex/v1/data/sources` (admin) returns exactly one source: `submissions` (Form submissions;
read/query/schema/detail/delete/export_csv; create/update unsupported — no CPT adapter). **Projects,
Services, and Clients are NOT registered as CoreX data models.** Registering them via the public
DataRegistry/extension seam (read + truthful capabilities only) is core spec 010 work.

## Open questions / decisions

1. **ACF active** — is it a hard dependency of an active CoreX kit (e.g. corex-kit-company) or the owner's
   choice? Perego's structured metadata must not *require* ACF. Determine + document; do not build Perego
   metadata on ACF.
2. Do the Forms & Flows / Submissions / Data Models admin **pages render** for the admin user in a browser,
   or show a blank/error (built-asset/capability issue)? → browser verification next.
3. Are the 3 Perego forms (footer quick message, project brief, careers/join) persisted CoreX **flows**
   visible in Forms & Flows, or only code-defined? → verify in Forms & Flows.
