# Spec 018 — Final visual acceptance, cleanup, release

**Branch:** `feature/018-final-acceptance-release`
**Mode:** Client Site Mode
**Status:** Complete (2026-07-15) — acceptance pass + safe cleanup; one documented residual.
**Depends on:** all prior specs (009–017).

## Goal

Close the program: a final route × language visual acceptance, remove orphan DB data / dead assets, and
record the release/deploy requirements. No new features — verification, cleanup, and honest sign-off.

## Acceptance pass

- **Route health (EN + AR):** `/`, `/services/`, `/work/`, `/journal/`, `/contact/`, `/terms/`,
  `/privacy/` → 200; AR equivalents reachable (Polylang canonical/lang-context); a real unknown path and
  `/ar/…` unknown → 200-styled **404** (`not-found` block, translated). No 500s.
- **Visual (EN + AR):** homepage captured `output/playwright/018-home-{en,ar}.png` — hero slider,
  About/mission, services teaser (RTL stagger mirrored), client carousels, and the full footer (both
  forms, showing the spec-016 AR placeholder fix) all render with **no console errors and no horizontal
  overflow**. Per-spec captures (011–017) cover the remaining routes.
- **CSS/assets:** the front end enqueues only the compiled theme `main.css` + framework
  `corex-runtime.css` — no duplicate stylesheet enqueues.

## Cleanup (reversible; DB backup taken first)

Timestamped backup `wp/db-backup-20260715-032449.sql` (gitignored) before any change.

- **Orphan default-WP pages trashed:** `Sample Page` (id 2) and the default `privacy-policy` **draft**
  (id 3). Before trashing id 3, repointed `wp_page_for_privacy_policy` → the **real** EN privacy page
  (id 40). Both verified unreferenced (not front/posts/privacy page, no nav-menu items, no PLL
  translations beyond themselves). Trash is reversible; force-delete deferred.
- **Verified already-clean:** obsolete `perego_section` CPT fully migrated (0 posts, spec 009); 0 orphan
  postmeta rows; default "Hello world!" post already in trash.
- **Seeders kept:** `scripts/seed-*.php` / `migrate-*.php` are reproducibility/provenance infrastructure;
  none deleted (no dead-seed evidence strong enough to justify losing re-provisioning history).

## Release / deploy requirements

- `npm run build` (perego-site) must run on deploy — the `perego-theme/legal-updated` block (spec 017)
  and all view scripts are built into `build/Blocks/` (gitignored).
- **`wp language core install ar`** must run on deploy so `wp_date` localizes Arabic month names
  (spec 017; recorded in `DECISIONS.md`).

## Documented residual (out of this spec's scope — recommended next focused task)

- **AR primary-nav localization:** header nav hrefs use `home_url()`, so on AR pages the nav links point
  at the EN base URLs (e.g. `/services`, `/work`) rather than the AR permalinks. Systemic Polylang
  nav-routing gap first logged in spec 011; the language *switcher* itself works and AR archives are
  reachable. Fix = `pll_home_url()` + translated permalinks for every nav item in `SiteHeaderRenderer`;
  best done as a dedicated i18n-routing task, not bundled into acceptance.
- **Sample/seed content:** "Sample Creator" clients + "Example stat" are editable Client-CPT seed data
  for the owner to replace — not code defects.

## Acceptance checklist

- [x] Route health EN/AR + translated 404.
- [x] Visual acceptance home EN/AR (no overflow/console errors); prior routes covered 011–017.
- [x] Orphan DB data removed safely (backup + verification + reversible trash).
- [x] CSS/asset enqueue clean.
- [x] Deploy requirements recorded (build + `ar` language pack).
- [x] Residual documented (AR nav i18n) with a remediation plan.
