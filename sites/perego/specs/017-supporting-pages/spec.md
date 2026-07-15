# Spec 017 — Supporting pages: generic page, terms, privacy, 404

**Branch:** `feature/017-supporting-pages`
**Mode:** Client Site Mode
**Status:** Complete (2026-07-15) — audit + one real fidelity gap closed.
**Depends on:** 009 (block-first architecture), 011 (global shell/footer).

## Goal

The remaining non-CPT pages — the **generic page** (`page.html`), **terms** & **privacy** (`legal.html`),
and **404** (`404.html`) — must match the locked handoff and stay editable through native WordPress
(FSE templates + native post content), EN + AR, RTL.

## Authoritative source

`_design_handoff/.../site/{page,terms,privacy,404}.html` + `perego-reference.scss`.

## Audit — what already matched (no change needed)

| Page | Template | Verdict |
|------|----------|---------|
| Generic page | `page.html` (breadcrumb + centered `post-title` + featured image + `prose` post-content) | matches handoff `page.html`; native page editing. |
| 404 | `404.html` → `perego-theme/not-found` block | matches `404.html` in **both** EN and AR — `error-page`/`error-grid`/`error-orbs`/`error-code`/`error-title`/`error-text`/`error-actions`; AR fully translated ("هذه الصفحة سلكت منعطفًا إبداعيًا", "العودة إلى الرئيسية"/"تواصل معنا"). |
| Terms / Privacy | `legal.html` → `legal-hero` + `legal-toc` (auto-built from H2 anchors) + `legal-body` (`prose` post-content) | structure + auto-TOC match; native post-content editing; per-language pages (EN 38/40, AR 39/41). AR `/privacy-2/` 301→`/ar/privacy-2/` is Polylang canonical, not a bug. |

## The gap (fixed)

The handoff legal-hero carries `<p>Last updated: July 1, 2026</p>` under the H1. The `legal.html`
template omitted it — `LegalTocRenderer`'s own docblock even promised a "Last updated line (from page
meta)" that was never implemented. Closed by a new **`perego-theme/legal-updated`** server-rendered
block placed in the legal-hero (handoff position), driven by an editable page projection:

- New meta `_perego_legal_updated` on the `page` type (REST-exposed, sanitised, `edit_pages`-gated); an
  editor sets the curated review date, blank falls back to the page's own modified date.
- New editor control: a **"Legal page"** meta box (`PostMetaBoxes` `page:legal` pseudo-schema, scoped to
  `legal`-template pages via `get_page_template_slug`) — genuine editor workflow, not CLI-only.
- The date is formatted in the current locale via `wp_date`; the "Last updated" label comes from
  `GlobalContent::legal()` (already EN/AR). Installing the **`ar` core language pack** lets `wp_date`
  render Arabic month names site-wide (was a missing-language-pack limitation, not a code bug).

## Acceptance

- [x] Generic page, 404 (EN+AR), terms, privacy match the handoff and edit natively.
- [x] Legal "Last updated" line present on all four legal pages, editable (meta box) with modified-date
      fallback; EN "Last updated: July 1, 2026", AR "آخر تحديث: يوليو 1, 2026".
- [x] EN + AR, RTL verified (captures `output/playwright/017-terms-{en,ar}.png`).
- [x] Pest green (262/262); block built; clean-code-guard + wp-guard pass.

## Notes

Mostly verification — three of the four page types were already handoff-faithful and editable. The `ar`
core language pack is now a **deploy requirement** (recorded in `DECISIONS.md`) so AR dates localize.
Runtime admin: admin / password (never commit/log).
