# Spec 017 — Tasks

- [x] T001 **Audit vs handoff.** Generic page (`page.html`), 404 (`404.html`/`not-found` block), terms &
    privacy (`legal.html`/`legal-hero`+`legal-toc`) compared to the handoff. Three of four page types
    already match structurally and edit natively (native page content + auto-built legal TOC + bilingual
    `not-found`). 404 confirmed fully translated EN **and** AR. Recorded in spec.md.
- [x] T002 **Generic page fidelity.** `/sample-page/` renders `post-hero__inner` (centered breadcrumb +
    `post-title`) + `prose` post-content, matching `page.html`. Native page editing — no code change.
- [x] T003 **404 fidelity EN/AR.** `error-page`/`error-grid`/`error-orbs`/`error-code`/`error-title`/
    `error-text`/`error-actions` present; AR renders `dir="rtl"` with Arabic title/text/actions
    ("العودة إلى الرئيسية"/"تواصل معنا"). No code change.
- [x] T004 **Legal fidelity + gap fix.** Terms/privacy `legal-hero`+`legal-toc`(auto H2)+`legal-body`
    match; **closed the missing handoff "Last updated" line** with a new `perego-theme/legal-updated`
    block in the legal-hero, driven by editable `_perego_legal_updated` page meta (modified-date
    fallback), formatted per-locale via `wp_date`. EN "Last updated: July 1, 2026",
    AR "آخر تحديث: يوليو 1, 2026".
- [x] T005 **Editability.** New "Legal page" meta box (`PostMetaBoxes` `page:legal` pseudo-schema, scoped
    to `legal`-template pages) lets an editor set the curated date in wp-admin — genuine editor workflow.
    Meta registered REST-exposed + sanitised + `edit_pages`-gated; save reuses the nonce/cap/autosave
    guards. Seeded 2026-07-01 on pages 38/39/40/41. Legal body + generic page content edit natively.
- [x] T006 **EN/AR + RTL.** Installed the `ar` core language pack so `wp_date` localizes month names
    site-wide (was a missing-pack limitation). Captured `output/playwright/017-terms-{en,ar}.png`; AR
    RTL with Arabic label + month verified live.
- [x] T007 **Done.** Block built (`build/Blocks/legal-updated`); Pest 262/262 (803 assertions);
    clean-code-guard + wp-guard pass (output escaped, meta sanitised/gated, i18n complete, no new
    query/AJAX/REST). Durable memory (PROGRESS/DECISIONS) updated; PR opened.
