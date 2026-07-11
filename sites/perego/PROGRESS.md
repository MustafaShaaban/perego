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
- **No pixel/behavioral fidelity check** against the design handoff's screenshots yet — structure and
  tokens are faithful by construction (same token source, same documented interaction spec), but no
  side-by-side visual comparison has actually been done.
- **Polylang languages not configured** — install/detection works, but no EN/AR languages exist in
  Polylang yet (its own wp-admin wizard, not WP-CLI-automatable in the version installed).

**Closed since (2026-07-11, same day)**: Jest is now set up for `perego-site`
(`perego-site/jest.config.js` + a local `@wordpress/interactivity` test double — see DECISIONS.md) with
25 tests covering all three blocks' interactivity: sticky-scroll, mobile menu (open/close, focus trap,
scroll lock, backdrop/link-click close), the mobile tap-accordion, the language toggle (RTL flip, cookie
persistence, no-op guard), and the preloader (reduced-motion, session-gate, soft/hard-hide timers,
storage-failure fallback). All green. Self-applied `test-guard` caught and removed one redundant
implementation-detail test. Known rough edge: the framework's root `jest.config.js` doesn't exclude
`sites/`, so `npm run test:js` from the repo root now also discovers (and fails to resolve) these files —
flagged as a framework-side follow-up in DECISIONS.md rather than fixed here (out of bounds for Client
Site Mode). Run this site's JS tests via `npx jest --config sites/perego/perego-site/jest.config.js
--rootDir sites/perego/perego-site`.

## Outstanding action needed from the site owner

- Add `127.0.0.1 perego.local` / `::1 perego.local` to `C:\Windows\System32\drivers\etc\hosts` and run
  `Restart-Service wampapache64` (or restart via the WAMP tray icon) — needs an elevated shell, could
  not be done from this session. The vhost config itself is already in place and syntax-verified.
  Nothing in this feature can be checked in an actual browser until this is done.
- Configure Polylang's two languages (English default, Arabic) via `/wp-admin/admin.php?page=mlang`.

## spec 002 — Home / M2 (2026-07-11)

Branch `feature/002-home` (off `feature/001`). Full spec/plan/tasks at `specs/002-home/`. Two new
dynamic FSE blocks + the asset build pipeline that was missing all along.

**Shipped and verified live** (real browser, EN + AR/RTL + mobile):
- `perego-theme/hero-slider` — rotating headline slides, dot tablist, prev/next + pause/play, aria-live
  announcer; auto-advance 6.5s, hover + visibility pause, reduced-motion gate, stop-on-interaction
  (WCAG 2.2.2). Interactivity API store. 9 Pest + 12 Jest.
- `perego-theme/services-teaser` — four staggered service cards + See-All arrow link, pure-CSS hover.
  7 Pest. Rebindable to the `service` CPT in M3.
- `PeregoSite\Content\HomeContent` — locale-aware EN/AR copy (en fallback). 5 Pest.
- `front-page.html` composes header → preloader → hero-slider → About (core glass panels) →
  services-teaser → footer.
- **Asset build pipeline** (DECISIONS.md — "M2 asset build pipeline"): the M1+M2 block SCSS/JS and the
  theme `main.scss`/`main.js` are now actually compiled (`wp-scripts --experimental-modules` for the
  Interactivity API modules; `sass` for the theme). This was the true cause of M1's "styling unverified"
  gap — nothing was compiled, so nothing loaded. **Now fixed; M1's blocks are styled + interactive too.**
- Full suite green: 53 Pest + 37 Jest. Guard Gate clean. Visual fidelity checked against the handoff
  screenshots; 3 CSS bugs found + fixed (see spec 002 tasks.md).

**Build commands** (run before serving/deploying — output is gitignored):
`cd sites/perego/perego-site && npm run build` · `cd sites/perego/perego-theme && npm run build`.

## Next

1. ~~Set up Jest for `perego-site`~~ — **done 2026-07-11**.
2. ~~Visual fidelity check (M1/M2 homepage)~~ — **done 2026-07-11** via Playwright + host-resolver-rules
   (no hosts-file edit needed; the vhost is reachable by mapping `perego.local`→127.0.0.1). Spec 001's
   T040/T041 homepage portion is satisfied by the M2 check.
3. **M3 (Services + Portfolio)** is the current milestone: `service` CPT + 4 singles; `project` CPT +
   category taxonomy + `perego/portfolio-grid` + project single + `perego/project-gallery-lightbox`.
4. Owner action items still open (not blocking): add `perego.local` to the hosts file + Polylang
   language config (both need an elevated shell / wp-admin).

<!-- superseded next block -->
### (superseded) earlier Next
1. Do the two owner action items above.
2. Set up Jest — done.
3. Visual fidelity check — done.
4. Then `/specify` M2 (Home: hero slider + services tabs) using
   `docs/superpowers/specs/2026-07-11-perego-corex-design.md` as the input design (its environment
   section is superseded by this file; its content mapping/phasing still stands) — following the same
   real Spec Kit flow (spec → plan → tasks → implement, TDD throughout) demonstrated in spec 001, on
   its own `feature/002-*` branch.
