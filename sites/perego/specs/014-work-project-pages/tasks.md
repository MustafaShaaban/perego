# Spec 014 — Tasks

- [x] T001 **Audit vs handoff (structure + editability).** `/work/` 200 (handoff `page-section` + filter);
    project single renders hero + `post-content` + gallery-lightbox + prev/next/related. **Already editable:**
    H1 ← post title, client/year/role/deliverables ← spec-010 meta (meta boxes), body ← `post-content`,
    navigation ← Project CPT, work grid ← Project `WP_Query`. Gallery meta is populated and the lightbox
    displays it. **Gap:** `_perego_gallery_attachment_ids` has **no editor UI** (excluded from the scalar meta
    box — wp.media picker deferred from spec 010). Recorded in spec.md audit table.
- [x] T002 **Gallery media-picker UI — done + verified.** New `Admin\ProjectGalleryMetaBox`: a "Project
    gallery" box on the Project editor with the native wp.media modal (multi-select, seeded with the current
    images), a thumbnail strip, and Clear. Chosen ids ride in one hidden field; `save()` (nonce
    `perego_gallery_save` + `edit_post` cap + autosave/revision guards) explodes the comma list and runs it
    through `ProjectPostType::sanitizeIntList` → clean positive-int array. Enqueues core `wp-media` + an inline
    script (no build) scoped to the Project edit screens. Registered admin-only in the provider. **Verified:**
    5/5 save-path unit tests (guards, comma-explode → [114,118,117], delete-on-empty); server-side
    `do_meta_boxes` render shows the box title, hidden field, Select button, current value `111,118,117`, and 3
    thumbnail previews; real admin login confirmed the editor + Meta Boxes area + CPT meta in the sidebar. No
    front-end change (the lightbox already reads the meta).
- [x] T003 **Work archive fidelity confirmed.** Capture `output/playwright/014-work-archive.png` matches
    handoff `portfolio.html` — breadcrumb, "Our Work" H1, subtitle, filter pills (All/Video Editing/2D Motion
    Graphics/Graphic Design/Website Making), project grid (PORTFOLIO cards + category labels) from the Project
    CPT.
- [x] T004 **Project single fidelity confirmed (AR/RTL).** Capture `output/playwright/014-project-single-ar.png`
    matches handoff `project.html` — RTL header, breadcrumb, category tag (WEB-AR), H1 from the post title,
    featured image; body (post-content), gallery-lightbox, and prev/next/related below. Lightbox reads the
    (now editable) gallery meta.
- [x] T005 **Editor workflow proven.** The project single is a CPT projection: H1 ← post title, client/year/
    role/deliverables ← meta (confirmed in the real editor sidebar), body ← post-content, gallery ← the new
    wp.media picker (save round-trips to a clean int list; the front-end lightbox reads it). Editing a Project
    updates its single, the `/work/` grid, and the services "selected work" (all Project-CPT-backed).
- [x] T006 **Full suite + guards + PR.** Pest 256/256 (792 assertions); no wp-scripts block changed → Jest
    unaffected. clean-code-guard + wp-guard pass. Durable memory updated; PR opened.
