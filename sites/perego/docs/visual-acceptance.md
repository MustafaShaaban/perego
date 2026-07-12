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

**44 checks across 11 route types × EN/AR × mobile + desktop, 0 hard failures.**

| Route | EN | AR | AR URL |
| --- | --- | --- | --- |
| Home `/` | ✅ | ✅ | `/ar/` |
| Services archive `/services/` | ✅ | ✅ | `/ar/services/` |
| Work archive `/work/` | ✅ | ⚠️ gap | — (no AR project content yet) |
| Journal `/journal/` | ✅ | ⚠️ gap | — (no AR journal page yet) |
| Contact `/contact/` | ✅ | ✅ | `/ar/contact-2/` |
| Single service `/services/website-making/` | ✅ | ✅ | `/ar/services/website-making-2/` |
| Single project `/work/…/` | ✅ | ⚠️ gap | — (no AR project content yet) |
| Single post `/…/` | ✅ | ⚠️ gap | — (no AR post yet) |
| Legal `/terms/` | ✅ | ✅ | `/ar/terms-2/` |
| Search `/?s=video` | ✅ | ✅ | `/ar/?s=video` |
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

## Accessibility (Phase 11 — WCAG 2.2 AA)

Regenerate with:

```bash
node sites/perego/perego-site/scripts/verify-a11y.mjs
```

Injects **axe-core 4.12** into each page and audits against the `wcag2a/2aa`, `wcag21a/21aa`, and
`wcag22aa` rule tags. Serious/critical violations are hard failures.

**Latest result (2026-07-12): 12 pages audited (home, services + single service, work + single
project, journal + single post, contact, legal, search — EN; home + contact — AR), 0 violations of
any impact.** Evidence: `output/verify-a11y.json` (git-ignored).

_Automated coverage only — manual keyboard-operation checks (focus order, mobile-nav trap/restore,
dialog/lightbox semantics, slider announcements) remain a recommended follow-up per Phase 11._

## Interaction states (Phase 12)

Regenerate with:

```bash
node sites/perego/perego-site/scripts/verify-interactions.mjs
```

Drives the live header's Interactivity-API behaviour in Chromium. **Latest result (2026-07-12): 4
checks, 0 failures** — sticky `is-scrolled` on scroll; mobile hamburger opens the panel + locks body
scroll; **Escape closes the panel and restores focus to the hamburger**; the AR language pill is a
real `/ar/` anchor.

> This pass **caught a real keyboard bug**: Escape didn't close the mobile nav because focus stayed on
> the hamburger (a sibling of `<nav>`) while the Escape handler was scoped to the nav. Fixed by moving
> focus into the panel on open (making the focus trap real) — `view.js` + a covering Jest test.

## Not yet automated (needs design baselines / heavier tooling)

- Pixel-level visual regression against the handoff screenshots (Phase 12): baseline capture +
  per-component diff is a larger harness; this pass covers structural/behavioural acceptance.
- Dropdown / dialog / gallery-lightbox interaction states and Lighthouse performance budgets
  (Phase 9) — planned follow-ups.
