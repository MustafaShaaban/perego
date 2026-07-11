# Decision: Repair In Place vs. Clean Client Restart

> Required by PEREGO_IMPLEMENTATION_PROMPT.md Phase 2. Dated 2026-07-11.

## Decision: **REPAIR IN PLACE**

The existing `sites/perego/` client layer already follows CoreX + FSE conventions
correctly. A clean `make:site` restart would discard verified, correctly-architected
work with no architectural benefit.

## Evidence against each "restart trigger" from the prompt

| Prompt restart trigger | Present here? | Evidence |
|---|---|---|
| Perego code mixed into CoreX `plugins/`/`addons/`/`packages/`/root `theme/` | **No** | `grep -rl -i perego` on those dirs → 0 matches. |
| Source edited inside runtime `wp/wp-content` | **No** | Client source is in `sites/perego/`; `wp/` is gitignored runtime. |
| Static handoff HTML copied into PHP page templates | **No** | Templates are FSE `*.html` block markup; renderers emit block markup, not pasted prototype HTML. |
| Visible content hard-coded in PHP/HTML/JS/CSS | **Partial** | Prose comes from locale providers (`HomeContent`/`PortfolioContent`), not literals scattered in templates. This is a *dynamic* source but not yet *editor-canvas* — a repairable gap, not a wrong foundation. |
| Marketing text edited via sidebar/options/meta boxes | **No** | No `InspectorControls`/`TextControl` prose inputs. |
| Header/footer as PHP partials instead of FSE parts | **No** | `site-header`/`site-footer` are FSE blocks in `parts/header.html`/`parts/footer.html`. |
| Services/projects/etc. as arrays/options not editor content | **Partial** | Projects are a real CPT (`perego_project`). Services CPT exists. Journey/process still to be built as canvas blocks. |
| Multilingual only flips `lang`/`dir`/CSS/localStorage | **Partial** | A `PolylangLanguageDriver` abstraction exists and Polylang is installed; but real EN/AR entity linking + Language Switcher is not yet wired (the header still uses a cookie toggle). A repairable gap. |
| Forms bypass CoreX Forms/submissions/email | **N/A yet** | Forms not built yet; will use CoreX Forms. |
| Implementation cannot reproduce handoff without major overrides | **No** | Tokens mapped into `theme.json`; blocks reproduce handoff structure; visual fidelity already checked for Home. |

## Conclusion

Two **repairable gaps** exist (editor-canvas prose; real Polylang linking), but neither
indicates a wrong architecture — both are additive remediations on a sound FSE +
container + CPT + provider foundation. Restarting would throw away M1/M2/M3-US1 verified
work for no gain and violate "preserve useful work."

**Action:** repair in place. Remediate the two gaps as tracked milestones, complete the
in-flight M3 slices, then build the remaining surfaces (clients, journal, legal, search,
404, forms, emails) on the same foundation.
