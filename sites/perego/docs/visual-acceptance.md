# Perego — Visual & Behavioural Acceptance Evidence

Automated headless verification of the live site (`http://perego.local`) against the handoff's hard
rules. Regenerate any time with:

```bash
node sites/perego/perego-site/scripts/verify-visual.mjs
```

The script drives headless Chromium (Playwright) across the primary routes × EN/AR × mobile (375) +
desktop (1280) and asserts, per page:

- **no horizontal page scrolling** (`scrollWidth ≤ clientWidth`, 2 px sub-pixel tolerance) — Phase 11
- **no JS console errors**, and **no broken sub-resources** on a 200 page — Phase 13
- **exactly one `<h1>`** — Phase 10 SEO
- **correct `<html lang>` / `dir`** for the language

The Arabic URL for each route is taken from that page's own `hreflang="ar"` alternate (Polylang's
truth), not by prefixing `/ar/` — under Polylang **Free** the AR slug is de-duplicated (e.g. the
contact page's AR translation is `/ar/contact-2/`), so a naïve prefix would test the wrong URL. Raw
run output is written to `output/verify-visual.json` (git-ignored — regenerable evidence).

## Latest result (2026-07-12)

**24 checks, 0 hard failures.**

| Route | EN | AR | AR URL |
| --- | --- | --- | --- |
| Home `/` | ✅ | ✅ | `/ar/` |
| Services archive `/services/` | ✅ | ✅ | `/ar/services/` |
| Work archive `/work/` | ✅ | ⚠️ gap | — (no AR project content yet) |
| Journal `/journal/` | ✅ | ⚠️ gap | — (no AR journal page yet) |
| Contact `/contact/` | ✅ | ✅ | `/ar/contact-2/` |
| 404 (unknown route) | ✅ | ✅ | `/ar/<unknown>/` |

All checked pages: **no horizontal overflow, no JS errors, exactly one `<h1>`, correct `lang`/`dir`**
at both breakpoints in both languages.

### Known content gaps (not defects)

- **AR translations for the Work archive and Journal** are not yet created, so those routes have no
  `hreflang="ar"` alternate. This is content/owner-data work (real projects + journal posts), tracked
  in `PROGRESS.md` item #3. The English routes and the AR home/services/contact/404 are complete.

### Fixed by this pass

- The AR CPT archives `/ar/services/` and `/ar/work/` previously **404'd** because `perego_service`,
  `perego_project`, and `perego_client` were not declared Polylang-translatable. They are now
  registered via the `pll_get_post_types` filter (Free-edition API), and the AR archives resolve 200.

## Not yet automated (needs design baselines / heavier tooling)

- Pixel-level visual regression against the handoff screenshots (Phase 12): baseline capture +
  per-component diff is a larger harness; this pass covers structural/behavioural acceptance.
- Header scrolled state, dropdown/mobile-nav/dialog/lightbox interaction states, and Lighthouse
  performance budgets (Phase 9) — planned follow-ups.
