# Legal (Terms / Privacy) — visual-acceptance evidence (US2 / T016)

**Reference**: `_design_handoff/Perego-Creative-Studio-Final-Handoff/site/terms.html`, `privacy.html`
**Live**: EN `http://perego.local/terms/`, `/privacy/` · AR `http://perego.local/ar/terms-2/`, `/ar/privacy-2/`
**Template**: `legal.html` (custom page template) + server-rendered `LegalTocRenderer` aside.

## Material differences found and resolved

| # | Section | Handoff | Live (before) | Disposition |
| --- | --- | --- | --- | --- |
| 1 | Page title | Large centered H1 "Terms & Conditions" (`font-size--h1`) | Rendered at **16px** — tiny | **Resolved (site-wide root cause)** — see the font-size-preset finding below |
| 2 | (recurring) Background | Full-bleed dark canvas | The `.legal` `<main>` had no background → a large **white gap** between content and footer on this short page | **Resolved** — added `background: var(--wp--preset--color--bg-deep)` to `.legal`, the same defensive pattern applied to every route |
| 3 | Draft/review notice | (final design has none) | Present — the `reviewNote` intro + TOC-box notice | **Kept intentionally** — this is a real launch blocker marker for placeholder legal copy (FR content-safety); correct to show until legal counsel supplies reviewed text |

## Root-cause finding: WordPress drops the h1/h2/h3 font-size presets (site-wide)

The tiny title was not a legal-only bug. `var(--wp--preset--font-size--h1)` (and `--h2`, `--h3`)
resolved to **empty**, so every heading using them fell back to 16px — affecting `.legal__title`,
services `.section-title` / `.svc-archive__title`, `.panel-title`, and journal/post `.post-card`
elsewhere.

Diagnosis (all verified via `wp eval`, WP 7.0.1):
- `theme.json` is correct: h1/h2/h3 present with clamp sizes, valid JSON, `defaultFontSizes:false`.
- `WP_Theme_JSON_Resolver::get_theme_data()` and `get_merged_data()` **settings** both contain
  h1/h2/h3 with the right values.
- But `wp_get_global_stylesheet()` — the CSS actually enqueued — **omits** the h1/h2/h3 preset
  variables (and core's `x-large`) while emitting core `medium`/`large`. Deterministic: survives
  `clean_cached_data()` + transient purge in a fresh process; no `wp_theme_json_data_theme` filter
  interferes (corex's brand.json is absent, so its filter no-ops).

This is a WordPress core stylesheet-generation quirk in this install, not a client-theme defect.
**Fix (in client scope, resilient):** re-declare `--wp--preset--font-size--h1/h2/h3` in the theme's
`:root` (`main.scss`) with the **same** theme.json clamp values, so headings render at the intended
scale regardless; harmless if a future core/build fix emits the presets. theme.json remains the
conceptual source of truth. A framework-mode investigation of the core emission is worth opening
separately.

## AR content gap (tracked, not a code fix)

The AR `terms`/`privacy` pages render the heading + draft notice but **no numbered section bodies**
(so the AR TOC is empty). The layout is proven correct by the EN route; the AR pages just need their
section content seeded/translated — an owner/translation content task, same category as other tracked
AR-content gaps. Also affected by the site-wide UI-string i18n gap (see [contact.md](./contact.md)).

## Verification

72-check route-health (0 fails, incl. EN/AR desktop+mobile terms + privacy), 12-page a11y (0
serious/critical, incl. legal en), 4/4 interactions, 195 Pest — all green **after** the site-wide
heading change (confirming no overflow/contrast regression from the larger headings).
