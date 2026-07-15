# Spec 014 — Work archive + project single

**Branch:** `feature/014-work-project-pages`
**Mode:** Client Site Mode
**Status:** In progress (audit complete 2026-07-14)
**Depends on:** 010 (Project CPT + meta), 011 (shell), 013 (services; the projection pattern).

## Goal

The Work archive (`/work/`) and the project single already render to the handoff and are **already
CPT-backed and largely editable** (unlike the homepage/services, no hardcoded-const rework is needed here).
Spec 014 verifies exact fidelity + all states and closes the one remaining editability gap: the **project
gallery has no editor UI** (a wp.media picker deferred from spec 010).

## Authoritative source

`_design_handoff/.../site/portfolio.html` + `project.html` + `css/styles.css`; reference
`perego-reference.scss` → `assets/css/main.css`.

## Audit — editable today vs gap

| Surface | Renderer / source | Editable? |
|---------|-------------------|-----------|
| Project single: H1 | `ProjectHeroRenderer` → `get_the_title($project)` | ✅ native post title |
| Project single: client/year/role/deliverables | `ProjectHeroRenderer` → `_perego_{client,year,role,deliverables}` meta | ✅ `PostMetaBoxes` (spec 010) |
| Project single: body | `single-perego_project.html` → `post-content` | ✅ block editor |
| Project single: gallery (lightbox) | `project-gallery-lightbox` → `_perego_gallery_attachment_ids` meta | ⚠️ **displays, but NO editor UI** (scalar meta box excludes it) |
| Project single: prev/next + related | `project-navigation` block → Project CPT | ✅ CPT |
| Work archive `/work/` | Project CPT `WP_Query` + filter | ✅ CPT (verify fidelity) |

So the project pages are already an editable projection of the Project CPT — the gallery UI is the only gap.

## Functional requirements

- **FR-1 Gallery editor UI.** A wp.media picker on the Project editor to select/reorder/remove gallery
  images, storing `_perego_gallery_attachment_ids`. No front-end change; the lightbox already reads the meta.
- **FR-2 Work archive fidelity.** `/work/` matches the handoff (grid/masonry, filters, cards, hover/zoom),
  EN/AR, all widths; cards from real Projects; filter behaviour correct.
- **FR-3 Project single fidelity.** Hero (title + meta grid), body, gallery lightbox (single vs gallery
  triggers, play icons, visible lightbox open/close/focus-trap/Esc), prev/next + related — match the handoff.
- **FR-4 Editor workflow proven.** Editing a Project (title/meta/gallery/body) updates its single + the work
  archive + the services "selected work".

## Acceptance

- [ ] Gallery editable via a real wp.media workflow in the Project editor; lightbox reflects the change.
- [ ] Work archive + project single verified vs the handoff, EN + AR, incl. lightbox + filter states (capture).
- [ ] Real editor workflow proven (edit Project → single + archive + selected-work change).
- [ ] Pest + Jest green; clean-code-guard + wp-guard pass.

## Notes

Most of this spec is verification — the Project CPT already backs everything. The new build is the gallery
media picker (admin wp.media; no wp-scripts build needed — enqueue the core `wp-media` handle + an inline
script; store a comma/JSON list of attachment ids in a hidden field the `save` path sanitises to ints).
Verify the picker via the real admin (Playwright on the Project edit screen), not hidden DOM.
