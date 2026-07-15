# Spec 015 — Journal, single article, search

**Branch:** `feature/015-journal-search-pages`
**Mode:** Client Site Mode
**Status:** In progress (audit complete 2026-07-14)
**Depends on:** 011 (shell). Journal = **native WordPress posts** (no CPT).

## Goal

The Journal archive, single article, and search already render to the handoff using **native FSE blocks**
(Query Loop / post-title / post-excerpt / post-featured-image / post-author) over native WP posts — which
are **fully editable in the block editor** with no projection layer needed. Spec 015 verifies exact fidelity
and the search/empty states, EN + AR, and fixes any gap found.

## Authoritative source

`_design_handoff/.../site/archive.html` (journal grid), `single-post.html` (article), `search.html`;
reference `perego-reference.scss` → `assets/css/main.css`.

## Audit — editable today

| Surface | Template / source | Editable? |
|---------|-------------------|-----------|
| Journal archive `/journal/` | `home.html` — Query Loop (`blog-grid` / `post-card*`) over native posts | ✅ native (post CRUD + editor) |
| Single article | `single.html` — `post-hero` (title/meta/author/read-time), `post-featured`, `post-content`, nav | ✅ native block editor |
| Search | `search.html` — `search-bar`, `search-results`, `post-card`, no-results state | ✅ core search, per-language |

Confirmed live: journal grid + single + search all match the handoff class structure. Search is
**per-language** (Polylang): AR-context search for "موشن" returns the AR posts; an EN-context search for an
Arabic-only term correctly returns the no-results state. Both EN and AR journal posts exist (3 + 3).

## Functional requirements

- **FR-1 Journal archive fidelity.** `/journal/` matches `archive.html` (grid, post cards: media, category,
  meta, title, excerpt, read-time), EN/AR, all widths; pagination if present.
- **FR-2 Single article fidelity.** Hero (title, author, date, read-time), featured image, prose body,
  prev/next or related if in the handoff — match `single-post.html`. EN/AR.
- **FR-3 Search fidelity + states.** The search bar, results grid, result count/heading, and the **no-results
  empty state** match `search.html`; per-language results correct.
- **FR-4 Editable natively.** Posts (title/content/featured image/category/excerpt) edit in the block editor
  and update the archive/single/search.

## Acceptance

- [ ] Journal archive + single + search verified vs the handoff, EN + AR, incl. the no-results state (capture).
- [ ] Search returns correct per-language results; empty state correct.
- [ ] Native post edit reflected on the archive/single. (Native WP — no custom seam.)
- [ ] Pest + Jest green; clean-code-guard + wp-guard pass (only if code changes are needed).

## Notes

Expected to be **mostly verification** — the journal is native WP posts + core search rendered through FSE
blocks, already editable and handoff-faithful. Fix only real fidelity gaps found in the capture pass; do not
add a projection layer where native editing already suffices.
