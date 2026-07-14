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

## ROOT CAUSE FOUND + FIXED — the CoreX admin JS bundle was unbuilt (2026-07-14)

Browser session (admin login at perego.local/wp-admin): the CoreX admin pages render their **server-side
PHP shell** (e.g. Forms & Flows shows its `<h1>` + description), but the browser console showed:

```
[ERROR] Failed to load resource: 404 (Not Found)
  http://perego.local/wp-content/plugins/corex-config/build/admin/index.js?ver=dev
```

**Root cause:** the unified CoreX admin React app (`plugins/corex-config/build/admin/index.js`) was **never
built** in this environment (`plugins/corex-*/build/` did not exist). So every CoreX admin page rendered
its heading but the interactive app (the actual Forms/Submissions/Data-Models functionality) failed to
mount — exactly the reported "dashboard does not expose Forms/Submissions/Data Models."

**Fix (deployment step, in-bounds):** built the bundle with the repo's hoisted `wp-scripts`
(`wp-scripts build --webpack-src-dir=src/admin --output-path=build/admin` in `plugins/corex-config`). The
asset now serves **200** (140 KiB). **No framework source was edited; `build/` is gitignored** — this is a
runtime/deployment build, not a code change. corex-config is the sole admin-app host (`src/admin`); other
CoreX plugins register features via the working REST API, so this single bundle restores the dashboard.

**⚠ Framework/deployment gap (durable):** the deployment pipeline must build CoreX admin assets
(`npm run build --workspaces` / per-plugin `build`) — otherwise a fresh deploy 404s the admin app again.
Since the build output is gitignored, this environment-local build does not persist in git. See DECISIONS
2026-07-14 "CoreX admin bundle build gap". This is a CoreX/deployment concern, not a Perego client defect —
no private Perego duplicate was created.

## Open questions / decisions

1. **ACF active** — is it a hard dependency of an active CoreX kit (e.g. corex-kit-company) or the owner's
   choice? Perego's structured metadata must not *require* ACF. Determine + document; do not build Perego
   metadata on ACF.
2. Do the Forms & Flows / Submissions / Data Models admin **pages render** for the admin user in a browser,
   or show a blank/error (built-asset/capability issue)? → browser verification next.
3. Are the 3 Perego forms (footer quick message, project brief, careers/join) persisted CoreX **flows**
   visible in Forms & Flows, or only code-defined? → verify in Forms & Flows.
