# Spec 018 — Tasks

- [x] T001 **DB backup.** Timestamped `wp/db-backup-20260715-032449.sql` (gitignored, mysqldump via the
    WAMP MariaDB bin) before any destructive change.
- [x] T002 **Orphan DB audit.** `perego_section` fully migrated (0 posts); 0 orphan postmeta; "Hello
    world!" already trashed. Two default-WP orphans found: `Sample Page` (id 2), default `privacy-policy`
    draft (id 3). Verified both unreferenced (front/posts/privacy options, nav menus, PLL translations).
- [x] T003 **Orphan cleanup (reversible).** Repointed `wp_page_for_privacy_policy` 3 → real EN privacy
    page 40; trashed ids 2 and 3. Reversible (trash); force-delete deferred.
- [x] T004 **CSS/asset audit.** Front end enqueues only compiled `main.css` + `corex-runtime.css` — no
    duplicate stylesheet enqueues. Seeders kept (reproducibility infra; no dead-seed evidence).
- [x] T005 **Route health EN/AR.** `/`, services, work, journal, contact, terms, privacy → 200; AR
    reachable; unknown paths (EN + AR) → translated 404. No 500s.
- [x] T006 **Visual acceptance EN/AR.** Homepage `output/playwright/018-home-{en,ar}.png` — full render,
    RTL correct, **no console errors, no horizontal overflow**. Prior routes covered by 011–017 captures.
- [x] T007 **Release requirements recorded.** `npm run build` + `wp language core install ar` are deploy
    steps (DECISIONS.md / spec.md).
- [x] T008 **Residual documented.** AR primary-nav localization (`home_url()` → EN base on AR pages) —
    systemic Polylang nav-routing gap; remediation = `pll_home_url()` + translated permalinks in
    `SiteHeaderRenderer`, as a dedicated i18n-routing task. Sample/seed content is editable, not a defect.
- [x] T009 **Sign-off.** Durable memory updated (PROGRESS/DECISIONS/roadmap); spec 018 PR opened. Program
    specs 009–018 complete.
