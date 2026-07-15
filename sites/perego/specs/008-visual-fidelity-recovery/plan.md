# Implementation Plan: Visual fidelity recovery

## Decision

Preserve the working Perego content model, Polylang architecture, FSE templates, forms, and CoreX adapters.
Rebuild the presentation layer in place: current bespoke components are valid integration points, but their
approximate SCSS and markup no longer honour the reference stylesheet's DOM/class contract.

## Approach

1. Port the handoff CSS and its image assets as an immutable client-theme reference baseline.
2. Add a narrow WordPress/CoreX adapter for proven layout interference only.
3. Bring each server-rendered component and FSE template into the reference class/hierarchy contract.
4. Capture handoff baselines and WordPress actual/diff artifacts before accepting each shared component and route.
5. Keep editor-facing presentation separately scoped so reference frontend CSS does not damage authoring.
