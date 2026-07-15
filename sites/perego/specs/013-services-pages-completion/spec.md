# Spec 013 — Services archive + four service singles

**Branch:** `feature/013-services-pages-completion`
**Mode:** Client Site Mode
**Status:** In progress (audit complete 2026-07-14)
**Depends on:** 010 (Service CPT + meta), 011 (shell), 012 (homepage; the editable-projection pattern).

## Goal

The services archive (`/services/`) and the four service singles (`/services/<slug>`) already render to
the handoff (all sections present, EN/AR, 200). Spec 013 closes the **editability** gap and verifies exact
fidelity + all states, mirroring spec 012: the hardcoded `ServiceContent` copy that drives the service hero
and the archive/overview must become an editable projection of the `perego_service` CPT, with the handoff
copy as the seed and **zero visual regression**.

## Authoritative source

`_design_handoff/.../site/service-*.html` + `services.html` + `css/styles.css`; reference
`perego-reference.scss` → `assets/css/main.css`.

## Audit — editable today vs hardcoded

| Surface | Renderer / source | Editable? |
|---------|-------------------|-----------|
| Service single: `whatwedo` + `process` | `single-perego_service.html` → `post-content` | ✅ block editor |
| Service single: `svc-hero` (title, eyebrow, subline, tabs) | `ServiceHeroRenderer` → **`ServiceContent::COPY`** const | ❌ hardcoded (not the CPT title/meta) |
| Service single: `portfolio`/selected work | `service-selected-work` block | (verify source — CPT projects?) |
| Services archive `/services/` | `services-overview` block → **`ServiceContent`** (likely) | ❌ verify + likely hardcoded |

The service singles are real `perego_service` posts (EN 13–16 / AR 25–28) with `_perego_service_slug`, a
title, `post-content`, and (spec 012) teaser meta — but the hero still reads the const, not the post.

## Functional requirements

- **FR-1 Service hero ← CPT.** The single's hero title/subline (and the tab labels) derive from the
  `perego_service` posts (title + structured meta) with the `ServiceContent` seed as per-field fallback; the
  cross-service tab strip reflects the published Services. Exact markup preserved. EN/AR via Polylang.
- **FR-2 Archive ← CPT.** The `/services/` overview lists the published Services (title, teaser image/label
  from spec 012 meta), not a hardcoded list; exact handoff composition; degrades cleanly with no Services.
- **FR-3 Selected work ← Projects.** Verify the single's "selected work" is sourced from real Project posts
  (or document + fix if hardcoded), matching the handoff portfolio grid.
- **FR-4 Fidelity + states.** Each of the four singles + the archive match the handoff at all widths, EN/AR,
  including hover/focus, reduced motion, and the tab active state.
- **FR-5 Editor workflow proven.** Editing a Service (title/meta/content) updates its single + the archive +
  the homepage teaser.

## Acceptance

- [ ] Service hero + archive are an editable projection of the Service CPT (seed fallback, zero regression).
- [ ] All four singles + archive verified against the handoff, EN + AR (capture evidence).
- [ ] Selected-work sourced from real Projects (or documented + fixed).
- [ ] Real editor workflow proven (edit Service → single + archive + teaser change).
- [ ] Pest + Jest green; clean-code-guard + wp-guard pass.

## Notes

Reuse the spec 012 pattern exactly: CPT/meta projection + per-field seed fallback + idempotent seeder + a
front-end edit round-trip as proof. Keep `ServiceContent::COPY` as the seed/default only. No hardcoded copy
as the *sole* source. No framework edits; document any CoreX gap in `docs/corex-framework-gaps.md`.
