# Visual recovery evidence and interference log

Status: recovery in progress; no route has visual acceptance.

## Evidence captured

- Static baseline: `sites/perego/output/visual-recovery/baseline/home-en-1440.png`
- Live pre-recovery capture: `sites/perego/output/visual-recovery/actual/home-en-1440.png`
- Live shell recovery captures: `output/visual-recovery/actual/home-en-1440-header-recovery.png` and `output/visual-recovery/actual/home-en-1440-shell-recovery.png`
- Live structural recovery captures: `output/visual-recovery/actual/home-en-1440-client-dom-fixed.png`, `output/visual-recovery/actual/home-en-1440-hero-contract.png`, `output/visual-recovery/actual/home-en-1440-about-wrapper-neutralized.png`, `output/visual-recovery/actual/home-en-1440-services-contract.png`, and `output/visual-recovery/actual/home-en-1440-clients-controls-contract.png`

The shell captures prove that importing the handoff stylesheet alone is insufficient: WordPress block markup and existing block styles can still change the resulting layout. They are diagnostic artefacts, not acceptance evidence.

`sites/perego/perego-site/scripts/capture-visual-recovery.mjs` now captures Home EN at 1440px for
the hero, About, Services, Clients, and footer viewport states. It freezes presentation-only motion,
waits for fonts, writes baseline/current/red-pixel-diff PNGs, and records every result as
`unreviewed` in `sites/perego/output/visual-recovery/home-en-1440-manifest.json`. The runner is a
partial T001 implementation, not an acceptance gate: it must expand to the full route/language/state/
viewport matrix and each result requires manual review.

The original desktop static full-page capture is not an acceptance baseline: the handoff's
viewport-driven reveal script leaves below-fold sections hidden when a full-page screenshot is taken
without scrolling. T001 remains open until the capture runner records explicit viewport and scroll
states. The two latest live captures prove only the repaired structural contracts: all individual
client cards remain children of `.indiv-track`, and the hero has the reference container, entry, and
CTA classes.

## Handoff CSS ownership

- `perego-reference.scss` is a faithful copy of the locked `site/css/styles.css` reference, except for four build-relative image URL remaps from `../assets/images/` to `../images/`; the values, selectors, and presentation rules are otherwise preserved.
- `perego-wordpress-adapter.scss` is the only layer allowed to neutralize WordPress/FSE layout interference or attach production-only font handling.
- `perego-editor.scss` is reserved for editor-canvas-only corrections and must not alter the public rendering contract.
- `perego-legacy-pre-recovery.scss` is retained as recovery evidence but is not imported.

## Confirmed interference and remediation

| Area | Interference | Recovery direction |
|---|---|---|
| Header | Legacy `perego-*` markup could not match `.site-header`, `.main-nav`, `.lang-toggle`, or mobile selectors. | Server renderer and view script now emit/reference the handoff contract. Mobile and RTL browser checks remain required. |
| Footer | Legacy `perego-footer*` and CoreX form wrappers bypassed `.site-footer`, `.footer-col`, `.footer-form`, and `.field` rules. | Shared footer renders handoff wrapper classes while retaining CoreX/careers form handlers. Submit-state and flat-footer visual checks remain required. |
| Home hero | The server renderer omitted the static handoff's `.container`, `.hero-enter`, and `.btn.btn--accent` contract, so the reference CSS could not set the intended horizontal alignment or CTA presentation. | Hero renderer now emits the exact classes; browser evidence confirms the contract. Visual parity is still unaccepted pending state/viewport diffs. |
| Clients carousel | A malformed server closing tag moved individual cards two onward outside `.indiv-track`; legacy Swiper assumptions also contradicted the handoff grid tracks. | The renderer now closes the shared container and every individual card correctly; client-owned native-scroll behavior keeps the reference tracks intact. EN/AR counts and visual states remain required. |
| Home About | FSE's required post-content wrapper sat between `.home-about__panels` and the editable `.glass-panel` articles, preventing the handoff's flex gap from applying; the inner wrapper also lacked `.container`. | The template now emits `.container.home-about__inner`; a narrowly scoped adapter makes only the post-content wrapper a transparent flex column. Browser evidence confirms two direct panels and the 30px handoff gap. |
| Services teaser | The dynamic renderer omitted the handoff `.container`, `.link-arrow`, `.reveal`, and staggered-delay classes, leaving the copied reference CSS without its expected layout and motion hooks. | The server renderer now emits the exact structural classes and delays while preserving dynamic service links and localized content. Browser evidence confirms the public DOM contract. |
| Clients controls | The first renderer migration kept text chevrons and omitted handoff track IDs, labels, and animated equalizer hooks. | The renderer now emits the reference SVG controls, `corporateTrack`/`individualTrack` IDs, track ARIA, and `.eq-bar` hooks. Data-backed card galleries and final visual states still require completion. |
| Sticky header | WordPress emitted the header inside a template-part wrapper whose only height was the header itself; CSS `position: sticky` therefore ended at the wrapper rather than persisting down the page. | A scoped adapter flattens only template-part wrappers that directly contain `.site-header`. At the About scroll state, live computed evidence is `position: sticky`, `top: 0`, and a 90px header height. |
| CSS background assets | Handoff stylesheet paths were relative to static `site/css/`; after compiling into `perego-theme/assets/css/`, `../assets/images/*` requested an invalid production path. | The four affected reference URLs now map to the theme's `../images/*` build-relative location. Browser evidence confirms `footer.png` resolves and no page resource is broken. |
| Footer social and forms | Removing legacy block CSS left the old mask-only social spans blank; CoreX's default quick-message labels and lack of placeholders also visibly diverged from the handoff. The Join Us field advertised a different CV constraint from the handoff. | The footer now emits the handoff SVG social markup. The supported CoreX schema supplies the reference labels/placeholders while validation and submission storage remain unchanged; required markers are visually hidden only, not removed from native controls. Join Us now presents the reference upload prompt and enforces the matching 10 MB server/client limit. State captures remain open. |
| FSE layout | `.wp-site-blocks` constrained/flow margins can add max-width and block-spacing around reference sections. | Adapter resets only these structural wrappers; each route still needs a DOM comparison before acceptance. |
| Route blocks | Existing bespoke `perego-*`, `svc-*`, and `portfolio-*` DOM is not automatically compatible with the reference stylesheet. | Rebuild route-by-route from the corresponding static template; do not use CSS aliases as a substitute for a DOM contract. |

## Acceptance rule

A route is accepted only after its EN and AR renders, desktop and mobile states, and required interaction/error/empty states have current screenshots and deterministic diff results against the locked handoff reference. Unit/build checks are necessary but cannot establish visual parity.
