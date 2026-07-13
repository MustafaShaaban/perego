# Implementation Plan: Design Fidelity Recovery

**Branch**: `feature/004-design-fidelity` | **Date**: 2026-07-12 | **Spec**: [spec.md](./spec.md)

## Summary

Bring the already-built Perego routes under one evidence-based visual-acceptance process. The final handoff is rendered and compared before each client-site slice is approved. Correct only demonstrable differences; preserve the current FSE, Polylang Free, token, and editor-first architecture.

## Technical Context

**Language/Version**: PHP 8.3+, WordPress 7.0+, JavaScript build tooling  
**Primary Dependencies**: Perego client plugin/theme, WordPress FSE, Polylang Free, Playwright, Axe  
**Testing**: Pest, Jest, Playwright route/interaction/accessibility verification  
**Target Platform**: Perego WordPress site, English LTR and Arabic RTL  
**Performance Goals**: Preserve existing local Lighthouse home evidence; avoid new global assets  
**Constraints**: No framework edits; no visual redesign; use `theme.json` tokens; assets and copy must derive from the approved handoff or owner-supplied production material.

## Constitution Check

- [x] I. Theme remains presentation-only; business behavior stays in `perego-site/`.
- [x] II. Existing client plugin continues to boot independently.
- [x] III. No controller/service architecture change is planned without a separate spec.
- [x] IV. Existing injected dependencies remain the pattern.
- [x] V. Visual corrections consume existing runtime tokens; no raw replacement design system.
- [x] VI. Block assets remain declared per block.
- [x] VII. Existing form and route security behavior must remain verified.
- [x] VIII. Every acceptance slice includes Arabic RTL evidence.
- [x] IX. Polylang remains behind the existing language abstraction.
- [x] X. This plan and its task register govern all new fidelity work.
- [x] Guard Gate and Definition of Done apply to every implementation slice.

## Evidence Strategy

1. Build a route/state matrix from the 16 static handoff templates and existing FSE templates.
2. Capture deterministic handoff baselines where a supplied screenshot does not exist.
3. Capture the matching WordPress route at the same viewport, language, and state.
4. Record material differences before editing; resolve them in a route-scoped slice.
5. Re-run visual, interaction, a11y, RTL, and relevant unit tests before marking the slice accepted.

## Project Structure

```text
sites/perego/
├── specs/004-design-fidelity/     # This feature's spec, plan, tasks, matrix, and validation guide
├── docs/visual-acceptance.md      # Durable summary of approved evidence
├── perego-site/                   # Client behavior, blocks, tests, verification scripts
└── perego-theme/                  # FSE templates, parts, token-driven presentation
```

## Complexity Tracking

No constitution exceptions are planned. Missing owner-supplied assets or legal/business copy are release blockers, not justification for an invented substitute.
