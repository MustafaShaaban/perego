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

## Next

- Once the hosts/Apache-restart step above is done, verify `http://perego.local/` and `/wp-admin/`
  return HTTP 200 (or a login redirect).
- Bootstrap real GitHub Spec Kit (`/specify → /clarify → /plan → /tasks → /implement`) + the guard
  skills for this site's own feature work, per `COREX-SPECKIT-START.md` at the repo root — this
  replaces the ad hoc planning used to get the environment running.
- Then `/specify` the first real feature (M2 Home: hero slider + services tabs) using
  `docs/superpowers/specs/2026-07-11-perego-corex-design.md` (this directory) as the input design —
  that document's environment-layout section is now superseded by this file, but its content
  mapping/phasing (CPTs, templates, bespoke blocks, M1–M7 milestones) still stands.
