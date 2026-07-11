# Perego — Progress

> Live status. First action each session: read this, then continue from **Next**.

## Environment bootstrap (2026-07-11)

- **This repo IS a CoreX framework checkout** (`origin` = `MustafaShaaban/perego`, `upstream` =
  `MustafaShaaban/corex`, merged at framework `v0.33.0` via `git merge upstream/main
  --allow-unrelated-histories`). The client site lives at `sites/perego/` (this directory), matching
  CoreX's own documented `sites/<client>/` convention — there is no separate framework checkout
  elsewhere on disk for this project.
- **WordPress install** at `C:\wamp64\www\perego\wp` (gitignored dev runtime — never committed):
  WordPress 7.0.1, DB `perego` (prefix `perego_wp_`), MySQL `root` / no password / `localhost`.
  Vhost `http://perego.local`, admin `admin` / `changeme` (dev-only — **change this password**).
  Set up via the framework's own unmodified `scripts/setup-wordpress.ps1` (repo root), run with
  `-SiteUrl http://perego.local -Title "Perego Creative Studio" -DbName perego -DbPrefix perego_wp_`.
- **Scaffolded** via `wp corex make:site Perego --path=sites/perego --starter`. Generated `perego-site/`
  (plugin, `PeregoSite\` namespace) + `perego-theme/` (FSE theme) + this governance set, plus the
  `--starter` vertical slice (`perego-site/src/Models/Example.php` etc. — remove per
  `perego-site/REMOVE-EXAMPLE.md` once no longer needed as a reference).
- **Active**: foundation (`corex-core`, `corex-blocks`, `corex-config`, `corex-forms`) + recommended
  add-ons (`corex-ui`, `corex-kit-company`, `corex-media`), junctioned in from the repo root's own
  `plugins/`/`addons/`; `perego-site` (plugin) + `perego-theme` (active theme), junctioned in from this
  directory. `wp corex doctor` → all GOOD except "Brand tokens: RECOMMENDED — no brand.json found"
  (expected — `perego-theme` has its own complete `theme.json`, not the parent `corex` theme's
  brand-overlay mechanism; not applicable here). No PHP fatals.
- **Two framework bugs found + worked around (not fixed upstream — out of scope for a client site;
  flag to the CoreX team separately)**:
  1. `scripts/setup-wordpress.ps1` pipes a here-string into `wp config create --extra-php`, which on
     Windows PowerShell 5.1 injects a UTF-8 BOM mid-file, corrupting `wp-config.php`
     (`Call to undefined function define()`, every subsequent `wp` call fails). Worked around by
     patching the generated `wp-config.php` directly (strip the BOM, rewrite BOM-less UTF-8) — a
     runtime artifact, not framework source, so this doesn't touch `scripts/setup-wordpress.ps1` itself.
  2. The same script's plugin activation (`wp plugin activate @pluginSlugs`) uses
     `Get-ChildItem`'s alphabetical order, activating `corex-blocks`/`corex-config` before
     `corex-core`, which fails their "Requires Plugins" dependency check on a *fresh* database.
     Worked around by activating manually in dependency order
     (`corex-core corex-blocks corex-config corex-forms`).
- **Outstanding (needs an elevated shell — not something this session could do):** add
  `127.0.0.1 perego.local` / `::1 perego.local` to `C:\Windows\System32\drivers\etc\hosts`, and
  `Restart-Service wampapache64`. The vhost block is already appended to
  `C:\wamp64\bin\apache\apache2.4.59\conf\extra\httpd-vhosts.conf` and verified with `httpd -t` →
  `Syntax OK`.

## spec 001 — Global Foundation (2026-07-11)

Branch `feature/001-global-foundation`. Full spec/plan/tasks at `specs/001-global-foundation/`. Built
via real TDD (Pest, red-green-refactor) against the live WordPress install, not just written and hoped
to work.

**Shipped and verified live** (rendered through `wp eval do_blocks(...)` against the actual install,
not only unit-tested):
- `perego-theme/site-header` + `perego-theme/site-footer` — the shared shell every template uses.
  Full desktop+mobile markup, Interactivity API wiring (sticky header, mobile slide-in panel with
  focus trap + scroll lock + Esc/backdrop/link-click close + focus restore, mobile Services
  tap-accordion, desktop dropdown via pure CSS).
- Bilingual mechanism: `LanguageDriver` interface, `PolylangLanguageDriver` + `FallbackLanguageDriver`
  (constitution IX — Polylang is never a hard dependency), resolved via `LanguageService`. Polylang is
  actually installed + active (v3.8.5) and confirmed to be the driver `LanguageService` resolves live —
  it just has no languages configured yet (needs its own wp-admin wizard, see DECISIONS.md).
- `perego-theme/preloader` — session-gated, reduced-motion-safe, soft-hide ~0.9s / hard-hide 2.5s.
  Wired only into `front-page.html`.
- 32 Pest tests, all green. `wp corex doctor` green throughout (only the expected "Brand tokens"
  advisory, which doesn't apply here). No PHP fatals introduced at any point.
- Found + worked around two more pre-existing framework quirks along the way (both logged in
  DECISIONS.md): every generated `PeregoSite\*` class's `defined('ABSPATH') || exit;` guard silently
  kills a headless test process with zero output unless the test bootstrap defines `ABSPATH` first
  (same fix as the framework's own `tests/bootstrap.php`); `wp corex make:site`'s `--path` collides
  with WP-CLI's own reserved `--path` flag, so it must be invoked from a context with no separate
  `--path` on the command line (see spec 001's plan.md Task 1 for the exact working invocation).

**Real gaps — not done, not hidden**:
- **No Jest coverage** for any of the three blocks' `view.js` (the actual JS logic — sticky scroll,
  focus trap, session-gating, language toggle). This is the single biggest risk left: the JS is written
  and was manually verified by rendering + reading, not automated-tested. Tracked as its own follow-up.
- **No pixel/behavioral fidelity check** against the design handoff's screenshots yet — structure and
  tokens are faithful by construction (same token source, same documented interaction spec), but no
  side-by-side visual comparison has actually been done.
- **Polylang languages not configured** — install/detection works, but no EN/AR languages exist in
  Polylang yet (its own wp-admin wizard, not WP-CLI-automatable in the version installed).

## Outstanding action needed from the site owner

- Add `127.0.0.1 perego.local` / `::1 perego.local` to `C:\Windows\System32\drivers\etc\hosts` and run
  `Restart-Service wampapache64` (or restart via the WAMP tray icon) — needs an elevated shell, could
  not be done from this session. The vhost config itself is already in place and syntax-verified.
  Nothing in this feature can be checked in an actual browser until this is done.
- Configure Polylang's two languages (English default, Arabic) via `/wp-admin/admin.php?page=mlang`.

## Next

1. Do the two owner action items above.
2. Set up Jest for `perego-site`/`perego-theme` and cover the three blocks' interactivity (the real gap
   called out above) — this should happen before treating spec 001 as fully closed, not after moving on
   to M2.
3. Run the visual fidelity check against `_design_handoff/.../screenshots/*.png` once the site is
   reachable in a browser.
4. Then `/specify` M2 (Home: hero slider + services tabs) using
   `docs/superpowers/specs/2026-07-11-perego-corex-design.md` as the input design (its environment
   section is superseded by this file; its content mapping/phasing still stands) — following the same
   real Spec Kit flow (spec → plan → tasks → implement, TDD throughout) demonstrated in spec 001, on
   its own `feature/002-*` branch.
