# Spec 015 — Tasks

- [x] T001 **Audit vs handoff.** Journal archive (`home.html` Query Loop → `blog-grid`/`post-card*`), single
    (`single.html` → `post-hero`/`post-title`/`post-meta`/`post-featured`/`post-content`), and search
    (`search.html` → `search-bar`/`search-results`/no-results) all match the handoff class structure and use
    native FSE blocks over native WP posts (3 EN + 3 AR). Search is per-language (Polylang) — AR-context "موشن"
    → AR results; EN-context Arabic-only term → correct no-results. **Fully editable natively; no projection
    rework needed.** Recorded in spec.md.
- [x] T002 **Journal archive fidelity confirmed.** Capture `output/playwright/015-journal-archive.png` matches
    `archive.html` — breadcrumb, "The Perego Journal" H1, subtitle, and post cards (featured media, category
    CRAFT/STUDIO NOTES/BEHIND THE SCENES, titles) from the native Query Loop.
- [x] T003 **Single article fidelity confirmed.** Live structure matches `single-post.html`: `post-hero`
    (`post-title`, `post-meta__author`, `post-single__readtime`), `post-featured`, `post-content`; verified
    both via live HTML and the real block editor (native post editing).
- [x] T004 **Search fidelity confirmed.** Capture `output/playwright/015-search-no-results.png` matches
    `search.html` — "Search results" H1, "Showing results for … — 0 matches found", search bar, and the
    "No results found / Check your spelling" empty state. Per-language correct: AR-context "موشن" → AR results;
    EN-context Arabic-only term → the no-results state.
- [x] T005 **Native editability confirmed.** Journal = native WP posts (3 EN + 3 AR) — title/content/featured
    image/category/excerpt edit in the block editor and flow to the archive/single/search. No custom seam.
- [x] T006 **Done.** No code changes required (native posts + core search + FSE blocks already handoff-faithful
    and editable) → Pest unchanged at 256/256, guards N/A. Durable memory updated; PR opened.
