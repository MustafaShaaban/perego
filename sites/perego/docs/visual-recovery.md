# Visual recovery evidence and interference log

Status: recovery in progress; no route has visual acceptance.

## Evidence captured

- Static baseline: `sites/perego/output/visual-recovery/baseline/home-en-1440.png`
- Live pre-recovery capture: `sites/perego/output/visual-recovery/actual/home-en-1440.png`
- Live shell recovery captures: `output/visual-recovery/actual/home-en-1440-header-recovery.png` and `output/visual-recovery/actual/home-en-1440-shell-recovery.png`

The shell captures prove that importing the handoff stylesheet alone is insufficient: WordPress block markup and existing block styles can still change the resulting layout. They are diagnostic artefacts, not acceptance evidence.

## Handoff CSS ownership

- `perego-reference.scss` is an untouched copy of the locked `site/css/styles.css` reference.
- `perego-wordpress-adapter.scss` is the only layer allowed to neutralize WordPress/FSE layout interference or attach production-only font handling.
- `perego-editor.scss` is reserved for editor-canvas-only corrections and must not alter the public rendering contract.
- `perego-legacy-pre-recovery.scss` is retained as recovery evidence but is not imported.

## Confirmed interference and remediation

| Area | Interference | Recovery direction |
|---|---|---|
| Header | Legacy `perego-*` markup could not match `.site-header`, `.main-nav`, `.lang-toggle`, or mobile selectors. | Server renderer and view script now emit/reference the handoff contract. Mobile and RTL browser checks remain required. |
| Footer | Legacy `perego-footer*` and CoreX form wrappers bypassed `.site-footer`, `.footer-col`, `.footer-form`, and `.field` rules. | Shared footer renders handoff wrapper classes while retaining CoreX/careers form handlers. Submit-state and flat-footer visual checks remain required. |
| FSE layout | `.wp-site-blocks` constrained/flow margins can add max-width and block-spacing around reference sections. | Adapter resets only these structural wrappers; each route still needs a DOM comparison before acceptance. |
| Route blocks | Existing bespoke `perego-*`, `svc-*`, and `portfolio-*` DOM is not automatically compatible with the reference stylesheet. | Rebuild route-by-route from the corresponding static template; do not use CSS aliases as a substitute for a DOM contract. |

## Acceptance rule

A route is accepted only after its EN and AR renders, desktop and mobile states, and required interaction/error/empty states have current screenshots and deterministic diff results against the locked handoff reference. Unit/build checks are necessary but cannot establish visual parity.
