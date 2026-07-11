# Perego — Progress

> Live status. First action each session: read this, then continue from **Next**.

## Environment bootstrap (2026-07-11)

- **Independent WordPress install** at `C:\wamp64\www\perego\wp` (gitignored dev runtime — never committed):
  WordPress 7.0.1, DB `perego` (prefix `perego_wp_`), MySQL `root` / no password / `localhost`.
  Vhost `http://perego.local`, admin `admin` / `changeme` (dev-only — **change this password**).
- **Scaffolded** via `wp corex make:site Perego --starter` run against the existing `corex.local` install
  (see `docs/superpowers/plans/2026-07-11-perego-environment-bootstrap.md` Task 1 for why: the scaffolder is
  pure and only needs *some* WordPress with `corex-core` active to invoke `wp corex make:site`, not Perego's
  own install). Generated `perego-site/` (plugin, `PeregoSite\` namespace) + `perego-theme/` (FSE theme) +
  this governance set, plus the `--starter` vertical slice (`Models/Example.php` etc. — remove per
  `perego-site/REMOVE-EXAMPLE.md` once no longer needed as a reference).
- **Wired via `scripts/setup-wordpress.ps1`** (junctions, matching how `corex`'s own checkout maps itself into
  its own `wp/`): required foundation (`corex-core`, `corex-blocks`, `corex-config`, `corex-forms`) +
  recommended add-ons (`corex-ui`, `corex-kit-company`, `corex-media`) junctioned in from
  `C:\wamp64\www\corex\{plugins,addons}`; `perego-site`/`perego-theme` junctioned in from this repo's own
  root. All active; `perego-theme` is the active theme.
- **Verified**: `wp corex doctor` → all GOOD except "Brand tokens: RECOMMENDED — no brand.json found" (expected;
  that's the next milestone's job). No PHP fatals (`wp eval "echo 'boot-ok';"` confirmed).
- **Known gotcha fixed**: `wp config create --extra-php` piped via a PowerShell here-string corrupts
  `wp-config.php` with a stray BOM mid-file on Windows PowerShell 5.1 (`Call to undefined function define()`).
  The script now creates the config plain, then patches in the `WP_DEBUG*` defines via
  `[System.IO.File]::WriteAllText` with an explicit BOM-less `UTF8Encoding`. Documented in the script's
  comments so it isn't rediscovered on a future re-run.
- **Outstanding (needs an elevated shell — not something this session could do):** add
  `127.0.0.1 perego.local` / `::1 perego.local` to `C:\Windows\System32\drivers\etc\hosts`, and
  `Restart-Service wampapache64`. The vhost block itself is already appended to
  `C:\wamp64\bin\apache\apache2.4.59\conf\extra\httpd-vhosts.conf` and verified with `httpd -t` → `Syntax OK`.

## Next
- Once the hosts/Apache-restart step above is done, verify `http://perego.local/` and `/wp-admin/` return
  HTTP 200 (or a login redirect), then start **Plan 2 (Foundation build)**: `design-tokens.json` → `theme.json`,
  `parts/header.html` / `parts/footer.html`, Polylang wiring, the `perego/preloader` block. See
  `docs/superpowers/specs/2026-07-11-perego-corex-design.md` for the full milestone list (M2–M7).
