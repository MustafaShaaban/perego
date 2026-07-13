# Implementation Plan: Project-single completion

**Branch**: `feature/007-project-single-completion` | **Date**: 2026-07-13 | **Spec**: [spec.md](./spec.md)

## Summary

Finish the three deferred project-single handoff surfaces through the existing client-only FSE/block architecture: gallery metadata + gallery block, adjacent project navigation, and dynamic related-project cards.

## Constitution Check

- [x] Client Site Mode only: `sites/perego/`; no CoreX source changes.
- [x] FSE template remains structural; editorial project prose remains in `wp:post-content`.
- [x] Existing renderer and handoff-approved assets are reused rather than introducing a replacement implementation.
- [x] EN/AR behavior uses public Polylang Free APIs only when available, with a deterministic no-plugin fallback.
- [x] All new output is server-rendered, escaped, responsive, and covered by the relevant guards/tests.

## Design

1. Extend the project repository with focused gallery, adjacent, and related-project queries.
2. Register the existing gallery block and add one focused project-navigation block.
3. Seed the handoff's approved stills as non-destructive three-image gallery data for every seeded project.
4. Add the two structural block placements to the project FSE template and token-based styling.
5. Verify EN/AR desktop/mobile/lightbox states and update durable acceptance evidence.
