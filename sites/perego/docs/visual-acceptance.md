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

**72 checks across all 16 mapped handoff templates × EN/AR × mobile + desktop, 0 hard failures.**
(Extended in spec 004 T007 from the earlier 11-route/44-check pass so every route-matrix row has
automated route-health evidence.)

| Route | EN | AR | AR URL |
| --- | --- | --- | --- |
| Home `/` | ✅ | ✅ | `/ar/الرئيسية/` |
| Services archive `/services/` | ✅ | ✅ | `/ar/services/` |
| Single service `/services/video-editing/` | ✅ | ✅ | `/ar/services/video-editing-2/` |
| Single service `/services/motion-graphics/` | ✅ | ✅ | `/ar/services/motion-graphics-2/` |
| Single service `/services/graphic-design/` | ✅ | ✅ | `/ar/services/graphic-design-2/` |
| Single service `/services/website-making/` | ✅ | ✅ | `/ar/services/website-making-2/` |
| Work archive `/work/` | ✅ | ✅ | `/ar/work/` |
| Single project `/work/…/` | ✅ | ✅ | `/ar/work/صفحة-هبوط…/` |
| Journal `/journal/` | ✅ | ✅ | `/ar/المدونة/` |
| Single post `/…/` | ✅ | ✅ | `/ar/من-كواليس…/` |
| Contact `/contact/` | ✅ | ✅ | `/ar/contact-2/` |
| Legal `/terms/` | ✅ | ✅ | `/ar/terms-2/` |
| Legal `/privacy/` | ✅ | ✅ | `/ar/privacy-2/` |
| Representative page `/sample-page/` | ✅ | ⛔ | *no AR translation (content gap)* |
| Search populated `/?s=video` | ✅ | ✅ | `/ar/?s=video` |
| Search empty `/?s=zznotarealquery` | ✅ | ✅ | `/ar/?s=zznotarealquery` |
| Search no-query `/?s=` | ✅ | ✅ | `/ar/?s=` |
| 404 (unknown route) | ✅ | ✅ | `/ar/<unknown>/` |

All checked pages: **no horizontal overflow, no JS errors, exactly one `<h1>`, correct `lang`/`dir`**
at both breakpoints in both languages. The one ⛔ is the representative standard page: `/sample-page/`
has no Polylang AR translation, reported as an informational content gap (owner supplies a real
translated standard page before launch), not a template defect.

### Content status

- **AR demo translations are now seeded and Polylang-linked** for the projects (Work), Journal posts,
  their taxonomy terms, and the Journal/Home pages (`scripts/seed-ar-content.php`) — so every route
  above resolves in both languages. The copy is clearly-marked demo (mirroring the EN placeholders);
  the owner replaces it with real projects/articles before launch (no invented business claims).

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

**Latest result (2026-07-12, re-run after spec 004 T010): 12 pages audited (home, services + single
service, work + single project, journal + single post, contact, legal, search — EN; home + contact —
AR), 0 violations of any impact.** Evidence: `output/verify-a11y.json` (git-ignored).

_Re-run caught a real regression from spec 004's Home visual-fidelity pass: restyling the header
language-toggle's active pill (`.perego-language-toggle__current`) reused the generic
`--wp--preset--color--text` (white) instead of the theme's dedicated `--wp--preset--color--
cta-text-on-accent` token, failing color-contrast against the bright accent background on every
route. Fixed by switching to the same text-on-accent token the `.perego-btn--accent` primitive
already uses correctly — confirmed back to 0 violations before moving on._

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

## Performance & best-practices (Phase 9/13 — Lighthouse)

Mobile audit of the home page with Lighthouse 12.8 (Chromium via `CHROME_PATH`, host-resolver mapped):

```bash
CHROME_PATH="<playwright-chromium>/chrome.exe" node_modules/.bin/lighthouse "http://perego.local/" \
  --form-factor=mobile --output=json --output-path=output/lh-home.json \
  --chrome-flags="--headless=new --host-resolver-rules=MAP perego.local 127.0.0.1 --no-sandbox" --quiet
```

**Latest result (2026-07-12): Performance 94 · Accessibility 100 · SEO 100 · Best-Practices 79.**

The audit drove two real fixes: a **missing meta description** (added `PeregoMeta` — dynamic EN/AR
description + OG/Twitter tags → SEO 92→100) and **sub-size touch targets** on the slider dots/bullets
(expanded the hit area to WCAG 2.2 AA via transparent padding, visible dots unchanged → a11y 97→100).

> **Best-Practices 79 is a local-dev artifact, not a code defect:** the only failing audits are
> `is-on-https` and `redirects-http` — the dev vhost serves plain HTTP. Both pass on production behind
> SSL. Performance opportunities that remain (text compression, HTTP/2, cache-TTL) are Apache/server
> config, not shipped code.

## Visual-difference review procedure (spec 004 T008)

The checks above prove a route is **structurally healthy** (loads, one `<h1>`, no overflow, right
`lang`/`dir`). They are **not** visual acceptance. A route is only design-complete when its live render
has been compared, state by state, against the locked handoff at
`_design_handoff/Perego-Creative-Studio-Final-Handoff/site/` and every material difference is resolved.
Run this procedure per route-matrix row before flipping its status from `Not accepted` to `Accepted`.

**1. Pick the target.** One row + one state from
[`specs/004-design-fidelity/route-matrix.md`](../specs/004-design-fidelity/route-matrix.md) — e.g.
`portfolio.html` → `/work/`, EN, desktop, "all-filter" state.

**2. Capture the deterministic handoff baseline.** Render the matching static handoff page (or the
supplied screenshot when one exists) at the exact viewport in headless Chromium and screenshot the full
page. Use the supplied screenshot verbatim where available; otherwise the static render *is* the baseline.
Freeze motion (`prefers-reduced-motion`, or pause carousels/preloader) so the capture is repeatable.

```bash
# Baseline from the static handoff file (file:// — no server needed):
#   handoff = _design_handoff/Perego-Creative-Studio-Final-Handoff/site/<template>.html
# Live render reaches the WAMP vhost via the same host-resolver mapping the verify scripts use:
#   --host-resolver-rules="MAP perego.local 127.0.0.1"
```

**3. Capture the matching live render.** Same viewport, same language (use the **real** AR URL from the
page's `hreflang="ar"` alternate — never a hand-built `/ar/` guess for translated posts), same seeded
content, same frozen state, fonts fully loaded (`waitUntil: 'networkidle'`).

**4. Compare.** Overlay baseline vs live and inspect, in order: section hierarchy, box dimensions,
spacing/rhythm, typography (family, size, weight, line-height, tracking), color/gradient tokens, borders,
radii, shadows, imagery and its treatment, **RTL mirroring** for AR, responsive reflow, and the
interaction state. A per-pixel diff (e.g. `pixelmatch`) may assist, but browser font rasterization
differences are **not** defects — a difference counts only when it changes layout, token, content, or
behaviour.

**5. Record before editing.** Log the reference file + viewport, both capture paths, and every material
difference with a resolution task/commit id into that route's evidence fields in `route-matrix.md`
(fields 1–6 of "Evidence fields required for each acceptance row"). Do not start editing until the
difference list is written down.

**6. Correct only the documented difference,** using existing `theme.json` tokens — no new styling or
interactions. Re-run the focused checks (`verify-visual.mjs` for the route, plus `verify-a11y.mjs` /
`verify-interactions.mjs` and relevant Pest/Jest where the change touches behaviour). Mark the row
**Accepted** only when every material difference is resolved and EN+AR evidence exists at both
breakpoints; mark it **Blocked on owner material** when only missing approved assets/copy/legal remain.

**Evidence storage.** Keep generated baseline/live/diff PNGs out of committed production assets — write
them under the gitignored `perego-site/output/` (e.g. `output/baselines/`, `output/live/`, `output/diff/`).
Commit only the concise matrices, findings, and the reproducible scripts that regenerate the captures.

## Not yet automated (needs design baselines / heavier tooling)

- A **fully automated** pixel-level visual-regression harness (baseline capture + per-component diff on
  every route/state in CI) is not built yet. The manual/semi-automated equivalent is now defined above
  under "Visual-difference review procedure" and is the accepted gate for spec 004; the automated
  harness remains a later enhancement.
- Dropdown / dialog / gallery-lightbox interaction states and Lighthouse performance budgets
  (Phase 9) — planned follow-ups.
