# Perego — Decision Log

## 2026-07-13 — Serve the locked handoff fonts locally

**Decision**: Perego serves Open Sans weights 300/400/600/700 and Cairo weights 400/600/700 from
`perego-theme/assets/fonts/`, rather than enqueueing Google Fonts or its preconnect hints.

**Why**: The full browser matrix exposed one external Google Fonts request as a broken resource on every
normal route when external network access was denied. The locked handoff requires those families but does
not include font files; the open-licensed Fontsource packages provide the exact required WOFF2 assets.

**Status**: verified: the local fonts preserve the theme's existing font-family tokens and the full
72-route EN/AR matrix now has zero broken resources. This is a client-theme asset change only; no CoreX
framework code changed.

## 2026-07-12 — Final handoff is the locked visual authority; design-fidelity recovery is the active work

**Decision**: `_design_handoff/Perego-Creative-Studio-Final-Handoff/site/` is the single binding visual,
responsive, interaction, content, and asset reference for Perego. The active client feature is
`sites/perego/specs/004-design-fidelity/` on branch `feature/004-design-fidelity`.

**Why**: The delivery audit established that substantial functionality exists, but specs, progress records,
and exact visual acceptance are out of sync. Existing route-health and accessibility checks are valuable but
do not prove pixel-level parity with the handoff. Future UI work must be evidence-led, route-scoped, and
compared with the handoff before it can be marked complete.

**Consequences**: No redesign, substitute styling, invented imagery, or new interaction may be introduced.
Missing owner-supplied business content, production assets, or legally approved copy is a launch blocker, not
permission to invent a substitute. Older status notes remain historical evidence; the authoritative delivery
queue is `PROGRESS.md`'s recovery section and Spec 004's task register.

Record each non-trivial decision (context · decision · why · status).

## 2026-07-11 — This repo is a CoreX checkout with the client site nested at sites/perego/

**Context**: initially set up as two separate directories (a standalone CoreX checkout at
`C:\wamp64\www\corex`, and an independent WordPress install at `C:\wamp64\www\perego` referencing it
via cross-directory junctions) — a real test of CoreX, so a real fork-and-build workflow, not a
convenience shortcut.

**Decision**: `C:\wamp64\www\perego` is instead a full CoreX framework checkout in its own right —
`origin` = `git@github.com:MustafaShaaban/perego.git`, `upstream` = `git@github.com:MustafaShaaban/corex.git`
(merged at `v0.33.0`, `--allow-unrelated-histories`). The client site is generated at `sites/perego/`
inside it, exactly matching CoreX's own documented `sites/<client>/` convention. `C:\wamp64\www\corex`
(the separate checkout) is not referenced anywhere in this project.

**Why**: this is meant to validate the framework's real intended consumption model — fork, build a
client site under `sites/<client>/`, and be able to pull framework updates via `git fetch upstream` —
not a shortcut that happens to boot a site.

**Status**: done. Follow-on: pulling future `upstream` updates should go through the same merge
pattern used here (fetch, merge `--allow-unrelated-histories` only needed for this first merge since
histories were unrelated; ordinary `git merge upstream/main` going forward).

## 2026-07-11 — Client feature work uses CoreX's native Spec Kit, not ad hoc plans

**Context**: the environment bootstrap above was executed via hand-written PowerShell + ad hoc task
tracking, before this checkout had CoreX's own Spec Kit scaffolding available to it.

**Decision**: every feature from here on (`M2` onward per the design doc) goes through
`/specify → /clarify → /plan → /tasks → /implement`, the same flow `COREX-SPECKIT-START.md` and this
repo's own `AGENTS.md`/`CLAUDE.md` mandate, with the guard skills as the quality gate.

**Why**: this is a real test of CoreX's own workflow, not just its code — using a different planning
system for the client site than the framework itself uses would defeat that.

**Status**: done for this repo's tooling availability; each milestone spec still to be written.

## 2026-07-11 — perego-site tests need their own Pest config + ABSPATH-defining bootstrap

**Context**: the root `phpunit.xml.dist` only covers the framework's own `tests/Unit` — a client
site's Pest suite needs its own config. Running the `--starter` example's `ExampleTest.php` through a
naive bootstrap (autoload only) produced **zero output and exit code 0** — no error, no failure, just
silence. Traced it (expensive — looked like a PHP crash at first) to every generated `PeregoSite\*`
class carrying `defined('ABSPATH') || exit;` (the same direct-access guard convention as Corex's own
classes, see the root repo's `DECISIONS.md` #20). Outside WordPress, `ABSPATH` is undefined, so the
guard's `exit;` fires — silently, since bare `exit` with no argument prints nothing.

**Decision**: added `sites/perego/perego-site/phpunit.xml.dist` (own `testsuite`, bootstrap) +
`tests/bootstrap.php` that defines `ABSPATH` (and requires the root Composer autoloader) before any
`PeregoSite\` class loads — mirroring the root `tests/bootstrap.php` pattern exactly, just scoped to
this client site.

**Why**: this is the established framework convention, not a bug to work around differently; matching
it exactly keeps client tests consistent with how the framework tests itself.

**Status**: done. Run via `cd sites/perego/perego-site && php ../../../vendor/bin/pest`.

## 2026-07-11 — Polylang installed + active; languages not yet configured

**Context**: spec 001 T027 — attempt installing Polylang to validate `PolylangLanguageDriver`
against the real plugin, not just Brain Monkey stubs.

**Decision**: `wp plugin install polylang --activate` succeeded (v3.8.5). `LanguageService` correctly
auto-detects it (`function_exists('pll_current_language')` is now true) and resolves
`PolylangLanguageDriver` in the live environment — verified via `wp eval`. Polylang has **no
languages configured yet** (`pll_current_language()` returns `false`, which our driver already
handles by defaulting to `'en'`) — it exposes no simple public API for programmatic language setup
(`PLL()->model` has no public `add_language()`); that's normally done through its own wp-admin setup
wizard (Languages → Add New Language), not something to reverse-engineer via WP-CLI.

**Why**: forcing Polylang's internal, undocumented setup path is riskier than just doing the one-time
admin-UI step a real site owner would do anyway.

**Status**: follow-up needed before real bilingual content authoring: visit `/wp-admin/admin.php?
page=mlang` and add English (default) + Arabic. Not blocking for spec 001 — the language *mechanism*
(driver resolution, toggle, persistence) is fully built and tested either way, per the driver's own
graceful default.

## 2026-07-11 — `upstream` (corex) is fetch-only, never push, from this project

**Context**: owner directive: "corex repo should be just a starting point and updates, DON'T EVER PUSH
TO COREX REPO FROM PEREGO PROJECT."

**Decision**: `git remote set-url --push upstream DISABLED_DO_NOT_PUSH_TO_COREX` — a hard technical
guardrail, not just a remembered rule. Any accidental `git push upstream` now fails immediately (no
such remote URL) instead of silently succeeding. `upstream` exists **only** to `git fetch`/`merge` in
framework updates; all pushes from this project go to `origin` (`MustafaShaaban/perego`) only.

**Why**: corex is the shared framework other projects also build on; this client site must never be
able to write to it, even by accident (wrong remote name typed, muscle memory from another repo, etc).

**Status**: done. Verify with `git remote -v` — `upstream`'s push URL must always show the disabled
placeholder, never a real one.

## 2026-07-11 — M3 needs pretty permalinks + .htaccess (and a Git-Bash path-conversion gotcha)

**Context**: the `project` CPT (M3) has a `work` archive + per-project singles, which need pretty
permalinks. The dev install shipped with plain permalinks and **no `wp/.htaccess`**, so every
CPT URL 404'd (Apache had nothing routing `/work/` to `index.php`).

**Decision / environment steps** (runtime only — `wp/` is gitignored, so none of this is committed;
reproduce on any fresh install):
1. `wp eval 'update_option("permalink_structure","/%postname%/"); flush_rewrite_rules(true);'
   --path=wp` — **not** `wp rewrite structure '/%postname%/'`: Git Bash's MSYS runtime rewrites a
   leading-slash CLI argument into a Windows path (`/%postname%/` → `/C:/Program Files/Git/%postname%/`),
   silently corrupting the permalink structure. Setting the option via `wp eval` (no leading-slash
   arg on the command line) avoids the conversion. (General rule for this repo: never pass a bare
   `/...` value as a wp-cli positional arg from Git Bash; use `wp eval`/`update_option`, or prefix
   with `MSYS_NO_PATHCONV=1`.)
2. Created `wp/.htaccess` with the standard WordPress `mod_rewrite` block. The perego vhost already
   has `AllowOverride All` + `mod_rewrite` loaded, so it took effect immediately.

**Also**: seeded 9 placeholder example projects + the 4 category terms via
`sites/perego/perego-site/scripts/seed-projects.php`. Run it with
`wp eval 'require "sites/perego/perego-site/scripts/seed-projects.php";' --path=wp` (the direct
`wp eval-file` path hits a wp-cli quirk with the script's `use`/`WP_CLI::log` ordering; the `require`
form works). These are the handoff's PLACEHOLDER projects ("Sample Client") — replace with Perego's
real work before launch; do not invent results/metrics (CONTENT_MODEL.md).

**Status**: done for this dev environment. The owner's environment + the M7 deploy pipeline must apply
the same permalink structure + `.htaccess` (WordPress writes the latter automatically when permalinks
are saved through wp-admin).

## 2026-07-11 — M2 asset build pipeline (the blocks were never compiled)

**Context**: M1 shipped the header/footer/preloader blocks with `block.json` referencing raw
`style.scss` + an ESM `view.js` (Interactivity API). Browsers can't load either — so **no block CSS
and no interactivity actually loaded**, and the theme's `main.scss`/`main.js` were never compiled to
`assets/css/main.css` / `assets/js/main.js` at all. The site rendered unstyled server markup; that's
the real reason M1's "visual fidelity unverified" gap existed. M2 (the first milestone with visible
front-end that must look right) cannot ship without fixing this.

**Decision**: stood up the compile step (Node 20+ is available; `@wordpress/scripts` + `sass` resolve
from the repo-root `node_modules`):
- **Blocks**: `wp-scripts build --experimental-modules --webpack-src-dir=src/Blocks
  --output-path=build/Blocks` compiles each block's `style.scss → style-index.css` and, for the
  Interactivity API blocks, `view.js →` a real script **module** with a `view.asset.php` declaring the
  `@wordpress/interactivity` dependency (WP core provides that module + the import map). The
  `--experimental-modules` flag is required in wp-scripts 32.x to build `viewScriptModule` fields.
  Each source `block.json`'s `style` now points at the built `style-index.css` (create-block
  convention). `register_block_type` resolves `build/Blocks/<name>` via a new
  `PeregoSiteServiceProvider::blockDir()` helper, falling back to `src/Blocks/<name>` pre-build so
  registration/markup still works (only the compiled assets are absent) until a build runs.
- **Theme**: `sass main.scss → assets/css/main.css` (the `--perego-*` token :root that every block's
  CSS depends on) + `wp-scripts build assets/src/js/main.js`.
- **Committing**: compiled output is **gitignored** (`sites/perego/.gitignore` + the root's
  `**/build/`), matching the repo's "compiled assets are generated, never committed" rule. Built for
  local dev now; the M7 deploy pipeline (`dist/`) will build for production.

**Why**: this is the standard WordPress block toolchain; using `--experimental-modules` keeps the
Interactivity API code (the design doc's mandated approach) rather than rewriting all three blocks to
plain view scripts. Verified end-to-end over HTTP: the homepage enqueues all block styles + the theme
token base, the import map resolves `@wordpress/interactivity`, and all three view modules load.

**Status**: done for M1+M2 blocks. Follow-up: wire `npm run build` (both packages) into the M7 deploy
pipeline so production gets compiled assets without a manual step.

## 2026-07-11 — Jest for perego-site gets its own config, not a root jest.config.js edit

**Context**: spec 001 T013/T023/T031 — the three Interactivity API blocks' `view.js` files had no
automated JS coverage, only manual verification. `@wordpress/interactivity` (the module `view.js`
imports for `store()`/`getContext()`/`getElement()`) is a WordPress runtime script handle, not an
installed npm package — Jest can't resolve it without a mapping, and the root framework's
`jest.config.js` has no such mapping (nothing in the framework uses the Interactivity API's `store()`
this way yet).

**Decision**: added `sites/perego/perego-site/jest.config.js` (extends the framework's
`@wordpress/scripts` jest-unit preset, scoped to this plugin via its own `rootDir`) +
`tests-js/wp-interactivity-mock.js` (a minimal test double: `store()` records its config under the
namespace so a test can pull the real `actions`/`callbacks` back out; `getContext()`/`getElement()`
return whatever the test last set). Did **not** edit the framework's root `jest.config.js` to add the
same mapping there, even though that would let the monorepo-wide `npm run test:js` also pick these
tests up cleanly — that file is framework tooling, out of bounds for Client Site Mode.

**Why**: keeping the fix entirely inside `sites/perego/perego-site/` respects the Role Gate boundary
(don't edit CoreX framework internals from client-site work) even though it leaves a rough edge: running
`npm run test:js` from the repo root now discovers these three test files (root's `jest.config.js` only
excludes `wp/` and `docs-app/`, not `sites/`) and fails to resolve `@wordpress/interactivity` for them.

**Status**: done for this site — 25/25 Jest tests green via
`npx jest --config sites/perego/perego-site/jest.config.js --rootDir sites/perego/perego-site`.
Follow-up (framework side, not this site's to fix): add `<rootDir>/sites/` to the root
`jest.config.js`'s `testPathIgnorePatterns`, matching the existing `wp/`/`docs-app/` exclusions, so the
root aggregate command doesn't reach into per-site test configs it can't resolve.

## 2026-07-11 — Autonomous implementation run: repair-in-place, editor-first CPTs, Polylang Free

**Context**: `PEREGO_IMPLEMENTATION_PROMPT.md` drives an end-to-end completion pass on the latest
compatible CoreX baseline.

**Decision 1 — Repair in place, not clean restart.** The `sites/perego/` client layer already follows
CoreX + FSE correctly (FSE blocks, container-wired renderers, real CPTs, tests, guards; no framework
pollution). Continue building on it rather than regenerating via `make:site`. Evidence + trigger-by-trigger
analysis in `docs/decision-repair-vs-restart.md`.

**Decision 2 — CoreX baseline already current.** `v0.33.0`/`71639e7` is the latest stable tag and is an
ancestor of HEAD; `upstream/main` `ff61bf0` is also already merged. No re-sync needed this run; recorded in
`docs/corex-baseline.md`. Recovery checkpoint `recovery/2026-07-11-pre-impl` pushed before structural work.

**Decision 3 — Editor-first service content.** The four `perego_service` posts store their "What we do" /
"Our Process" prose as real block markup in `post_content` (editable on the canvas), rendered by
`single-perego_service.html` via `core/post-content`. Only structural chrome (eyebrow, H1, tabs) is a
dynamic block (`perego-theme/service-hero`). ServiceContent is the seed default, not a runtime prose
source. **Why**: satisfies the prompt's editor-canvas rule and is the pattern to follow when retrofitting
the Home/portfolio surfaces (which still render prose from PHP providers — tracked remediation).

**Decision 4 — Polylang **Free** slug strategy: never share slugs.** Slug-sharing across translations is
Pro-only. The seeders let WordPress give the AR post a distinct slug (`video-editing-2`), namespaced by the
`/ar/` directory prefix, and keep the canonical service key in `_perego_service_slug` meta (read by the
hero block). We explicitly do **not** force a shared slug via `$wpdb` — an earlier attempt did, and it made
AR URLs 301-collapse onto EN, exactly the Pro-only assumption the prompt forbids. **Why**: the build must
pass with zero Pro dependency.

**Decision 5 — Polylang `/ar/` rewrite flush is a documented admin step.** Polylang registers its
directory rewrite rules only on a real admin/browser request, never under `wp-cli`/`wp eval` (verified:
`wp rewrite flush`, deleting `rewrite_rules` + front-end regeneration, and manual
`PLL_Links_Directory::rewrite_rules` re-registration all leave 0 language rules). So the one-time
**Settings → Permalinks → Save** step is documented in `docs/multilingual-guide.md` rather than worked
around with any Pro-only or hacky mechanism. All translation data/config is complete and correct without it.

**Status**: services surface (singles) + Polylang EN/AR configuration + bilingual service seeding shipped,
verified live, committed (`114a0b4`, `c902fd5`, `5017611`, `edd1f12`, `31b07ea`) and pushed. Remaining
scope tracked in `PROGRESS.md` "Next".

**Decision 6 — Client forms register into CoreX Forms via the app container.** `corex-forms` exposes no
filter/hook for third-party form registration and its `FormsServiceProvider::registerForms()` hardcodes the
example form. Perego forms (`QuickMessageForm`, `ProjectBriefForm`) therefore register into the shared
`FormRegistry` singleton resolved through the public `\Corex\Boot::app()->container()` accessor, on `init`,
guarded so the site degrades when `corex-forms` is inactive. The engine's default listeners (store + email)
are shared across forms, so registering after boot still delivers. **Why**: this is client-site composition
using a public accessor, not an edit to framework code (Role Gate: Client Site Mode).

**Decision 7 (RESOLVED 2026-07-12) — Join-us CV upload + branded emails path.** Resolved with a
platform-safe bespoke endpoint that does **not** hard-depend on the inactive add-ons. `PeregoCareersController`
(`POST perego/v1/careers/apply`) validates the CV with WordPress's own `wp_check_filetype_and_ext` + a `finfo`
content sniff (never trusting the browser MIME) under the exact CoreX Careers policy (pdf/doc/docx ≤ 5 MB),
stores it as a **private** attachment via `wp_handle_upload`, and — **best-effort only** — records the
application against a standing "Open Application" `corex_job` through `ApplicationStore` **when `corex-careers`
is active** (guarded; the email is the primary record otherwise). The public form is anonymous, so it is gated
by a honeypot + per-IP rate limit rather than a nonce (a `wp_rest` nonce cannot authenticate anonymous
visitors). Branded EN/AR confirmation + admin-notification emails go through the already-shipped
`PeregoMailer`/`PeregoEmailRenderer` seam (Phase 7/8 email commits), not the deferred `corex-email` wiring.
**Why this over the earlier options:** it neither pulls in the full careers CPT/table footprint as a hard
dependency (the option-a concern) nor reinvents upload security (the option-b concern) — it reuses core's
upload/filetype APIs and degrades cleanly when either add-on is absent. `scripts/seed-careers.php` idempotently
seeds the standing job when Careers is present. _Original open context preserved below for the record._

**Decision 7 (superseded open context) — Join-us CV upload + branded emails path.** The remaining Phase 7 items
depend on capabilities not currently active:
- *Join-us / CV form*: `corex-forms` has no `file` field type (its `FieldTypeRegistry` built-ins stop at
  text/email/phone/select/…). Adding one is CoreX **Framework Mode** work, out of Client Site Mode.
  `corex-careers` ships the intended secure upload path (`UploadValidator` spec 012 + `ApplicationService`)
  but is job-centric and inactive. Options: (a) activate + adapt `corex-careers` (general "open application"
  job) — spec-preferred but pulls in the careers CPT/table/status-flow footprint; (b) a bespoke client-side
  upload endpoint — reinvents platform security (discouraged by wp-guard); (c) request a CoreX Framework
  Mode task to add a first-class `file` field to `corex-forms`.
- *Six EN/AR branded emails*: need `corex-email` activated and wired to the `RoutedMailer`/
  `MailTemplateCatalog` seam (the `SendEmailListener` already routes `forms.<slug>.submitted`); until then
  the engine uses the `wp_mail` fallback to the admin address.
Both change the site's active-plugin footprint and/or mail behaviour — **held for owner direction.**

**Decision 8 (2026-07-12) — `perego_section`, not `perego_global_section`, for the global-content CPT.**
The implementation prompt suggests `perego_global_section` (its "such as" wording allows latitude), but
WordPress hard-limits `register_post_type` names to **20 characters** and `perego_global_section` is 21 —
the CPT registered as a silent no-op (no error, no post type). We renamed it to **`perego_section`** (14
chars), keeping the `perego_` prefix and the meaning, and added a unit test asserting the name stays ≤ 20 so
it cannot regress. **Why**: a 21-char name is not a style choice, it is a WordPress constraint; the shorter
name is the only correct option. Roles are carried in `_perego_section_role` meta (not slugs), and the `404`
role key is stored as `not-found` because a purely-numeric array key (`'404'`) coerces to int in PHP and
would break string role comparisons.

**Decision 9 (2026-07-12) — Polylang-Free translatable CPT via `pll_get_post_types`; strict no-mix render.**
`perego_section` is declared translatable through Polylang's **Free** `pll_get_post_types` filter (a public
WordPress.org API — no Pro dependency), so linked EN/AR records resolve by language and the editor gets the
normal language + translation-linking UI. The `GlobalSectionRenderer` enforces a hard **no-language-mixing**
rule: a role renders only from a record in the *exact* current locale — a missing translation shows an inline
notice to editors and **nothing** to visitors, never the other language's copy. This satisfies the prompt's
"fallbacks that do not mix interface languages" and "missing translations must be visible to administrators
and covered by tests" without any Pro-only synchronization/duplication feature. Verified live: the header
EN/AR pair links `{"en":72,"ar":73}` and the block renders per-language content correctly.

**Decision 10 (2026-07-12) — Language switcher is real navigation, not a JS toggle.** The prototype's
JS-only language toggle (swap `lang`/`dir`, persist a cookie, `window.location.reload()` the same URL) is
explicitly forbidden by the implementation prompt, and it never reached the translated entity. The header
switcher now renders **server-side anchors to `LanguageDriver::urlFor($locale)`**: under Polylang each is the
real translated URL (`/ar/…` via `pll_the_languages(raw)`), so switching is genuine navigation that works with
no JavaScript; the current locale is a non-link `aria-current` marker. A new driver-contract method
`managesLanguageViaUrl()` (Polylang `true`, fallback `false`) drives a `data-lang-url-managed` flag: the
view-script now touches language state **only** in the fallback mode (mirror the cookie into `<html>` on load,
persist each switch click for cross-page memory) and never applies a stale cookie under Polylang. To make the
no-Polylang fallback switch without JS too, `FallbackLanguageDriver::currentLocale()` now honors a `?lang=`
query var (authoritative for the request) ahead of the cookie. **Why**: it satisfies the prompt's "correct
current-language URLs / switch to the translated entity / no JS-only toggle / no Pro dependency" with real
Polylang Free URLs, degrades gracefully (a missing translation link falls back to home, never a mixed/broken
URL), and keeps EN/AR fully working when Polylang is absent.

**Decision 11 (2026-07-12) — Serve a real `/llms.txt`; add `x-default` to reuse, not duplicate, CoreX
readiness + Polylang hreflang.** For agent readiness (Phase 10) the prompt says to make CoreX's existing
readiness checks pass rather than build a parallel dashboard. CoreX's `ReadinessScorer` (corex-config Insights)
checks an `llms_txt` signal, so `PeregoAgentReadiness` serves a real `/llms.txt` on `init` (virtual, no file):
`text/plain` + `X-Content-Type-Options: nosniff`, built from the site name, the handoff tagline, the real
navigation routes, and the EN/AR language URLs — no fabricated claims. For hreflang, Polylang Free already
emits per-language `alternate` links, so we add only the missing **`x-default`** on `wp_head` to complete the
EN/AR/x-default set the handoff SEO model requires. **Why**: both reuse platform/framework facilities (WP
sitemap + robots, Polylang alternates, CoreX readiness) and add only the genuine deltas, keeping the site free
of duplicate SEO machinery. Verified live: `/llms.txt` 200 text/plain; `<head>` carries EN + AR + x-default.

<!-- language-switcher verification anchor --> Verified live: `/ar/` → `<html dir="rtl" lang="ar">`.

**Decision 12 (2026-07-12) — All Perego CPTs are Polylang-translatable; verification is headless + slug-aware.**
A headless verification pass (`scripts/verify-visual.mjs`, Chromium via Playwright) found `/ar/services/` and
`/ar/work/` returning **404**: `perego_service`, `perego_project`, and `perego_client` were never declared
translatable, so Polylang produced no `/ar/…` archive routes for them. Fixed by generalizing the
`pll_get_post_types` filter (`PeregoSiteServiceProvider::registerTranslatablePostTypes`) to register all four
Perego CPTs — a Polylang **Free** API, no Pro dependency — after which a rewrite flush made the AR archives
resolve 200. The verifier derives each Arabic URL from the page's own `hreflang="ar"` alternate rather than
prefixing `/ar/`, because Polylang Free de-duplicates AR slugs (the contact page's AR translation is
`/ar/contact-2/`, not `/ar/contact/`) — a naïve prefix tests the wrong URL and reports false failures. **Why**:
the fix restores real AR routing for the content types with the platform's own mechanism, and the slug-aware
verifier is a correct, reusable gate. Result: 24 checks, 0 hard failures; the only remaining items are honest
content gaps (AR Work/Journal translations) surfaced informationally, not as defects.

**Decision 13 (2026-07-12) — Client-side styling of server-rendered CoreX Forms (`.corex-form__*`).**
The CoreX Forms engine renders the contact "Start a Project" brief and the footer "quick message" form as
server-rendered `.corex-form__*` markup, but the framework ships those styles **only as a Gutenberg-block
stylesheet** (loaded conditionally when the *block* renders) and against framework tokens (`--ink`/`--surface`/
`--primary`) the Perego dark theme does not define — so on the client both forms rendered as raw, unstyled
browser controls, including a **visible honeypot** input. Fixed in **Client Site Mode** by styling `.corex-form__*`
in the client theme's `main.scss` with Perego tokens (12-col grid, underlined inputs, select/multi-select, submit
via the shared `.perego-btn`, off-screen honeypot, error/status states), and setting `width:half` on the paired
brief fields (a supported CoreX Forms field key) for the 2-column handoff layout. **Why**: the form's presentation
is a legitimate client concern; we did not touch the framework. The service-chooser toggle-buttons in the handoff
are adapted to the engine's native `<select multiple>` (a new field type would be framework scope).

**Decision 14 (2026-07-12) — Resilient re-declaration of the h1/h2/h3 font-size presets.**
WordPress 7.0.1 in this install **drops the `h1`/`h2`/`h3` font-size presets** from the CSS emitted by
`wp_get_global_stylesheet()`, so `var(--wp--preset--font-size--h1)` resolved to empty and every heading using it
fell back to 16px site-wide (legal title, services section titles, etc.). Verified deterministic, not a cache:
`theme.json` is correct, `WP_Theme_JSON_Resolver::get_theme_data()`/`get_merged_data()` **settings** both contain
the presets with the right clamp values, but the emitted stylesheet omits them (and core's `x-large`) while
keeping core `medium`/`large`; survives `clean_cached_data()` + transient purge in a fresh process; corex's
`wp_theme_json_data_theme` filter no-ops (no `brand.json`). Fixed by re-declaring the three presets in the theme's
`:root` (`main.scss`) from the **same** theme.json clamp values. **Why**: it makes the theme resilient to the core
quirk, keeps theme.json as the conceptual source of truth, and is harmless if a future core/build fix emits the
presets. The core emission quirk itself is worth a separate framework-mode investigation.

**Decision 15 (2026-07-12) — Locale-aware breadcrumbs on native FSE templates via tiny server blocks.**
`single.html` (journal post) and the search page had no per-page PHP renderer, so a locale-aware breadcrumb could
not be added as static FSE-template HTML without hardcoding English on the AR route. Added a small
`perego-theme/post-breadcrumb` server block (`PostBreadcrumbRenderer`, mirroring `JournalHeaderRenderer`) and
rendered the search breadcrumb inside `SearchResultsRenderer`, both language-aware via `GlobalContent`. **Why**:
consistent with the established server-block pattern; avoids the "hardcoded English on AR" bug class on
language-neutral templates.

**Decision 16 (2026-07-12) — UI-string i18n deferred to its own slice (launch blocker, not a 004 fix).**
Design-fidelity work surfaced that every AR route renders the header nav, form labels, and buttons in **English**:
there are no `.po`/`.mo` files and no `pll_register_string` calls for the `perego-site`/theme text domains, and
`SiteHeaderRenderer`'s nav labels are hardcoded English (not `__()`-wrapped). This is pre-existing (present on the
already-accepted AR home) and the handoff is English-only, so it is **not a deviation from the handoff** and is out
of spec 004's scope. **Decision**: track it as the top bilingual-launch blocker for a dedicated i18n slice rather
than absorb a large cross-cutting change into per-route design work; the approach (gettext `.po`/`.mo` vs Polylang
string translations) is an owner call. Recorded in `specs/004-design-fidelity/content-manifest.md`.

**Decision 17 (2026-07-13) — Single-post reading time as a locale-aware server block.** The handoff
single-post meta row (`single-post.html:71`, plus the `readTime` string in `content/{en,ar}.json`) shows a
"6 min read" estimate that no native WP block emits. Added `perego-theme/post-reading-time`
(`PostReadingTimeRenderer`), mirroring the Decision-15 breadcrumb pattern: it estimates from the post body
at 200 wpm (floored to 1 minute, unicode-aware word split so Arabic counts correctly) and renders the
localized label via `GlobalContent::readTime()` (EN "N min read" / AR "N دقيقة للقراءة"). **Why**: closes a
real 004 fidelity gap that was previously listed as a deferred "meta embellishment", using the established
server-block-for-locale-aware-native-templates pattern rather than a plugin. The label lives in
`GlobalContent` (locale-keyed array, like the other neutral-template strings), so it needs no `.po`/`.mo`
entry; the block's admin-only title/description follow the existing convention of being POT-tracked but not
AR-translated. The remaining meta embellishments (author avatar, "By" prefix, separator dots) stay deferred
as cosmetic.

**Decision 18 (2026-07-13) — Keep WordPress's default "Sample Page" out of the index/sitemap in code.**
The `page`-template route-health fixture is WordPress's auto-created `sample-page`, which still carries
the default lorem copy and was appearing in `wp-sitemap-posts-page-1.xml` and indexable (`blog_public=1`).
Rather than delete the fixture (needed for the `page`-route health check) or hand-edit content, added
`Seo\PlaceholderPageIndexing`: a `wp_robots` filter setting `noindex,nofollow` on that page and a
`wp_sitemaps_posts_query_args` filter dropping it from the posts sitemap, both keyed on WordPress's fixed
`sample-page` slug. **Why**: closes a real (if minor) placeholder-content indexing leak now, without
disturbing the fixture; the checklist already sanctioned "exclude from indexing" as a resolution. Scoped
to the well-known WP default slug and documented as removable once the fixture is deleted at launch
(LAUNCH-CHECKLIST §4). Verified live: the page emits `noindex,nofollow` and no longer appears in the
sitemap, while real pages (e.g. `/contact/`) are unaffected.
