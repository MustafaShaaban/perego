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
- [ ] T003 **Work archive fidelity.** `/work/` vs handoff `portfolio.html` — grid/masonry, filters, cards,
    hover/zoom — EN/AR, all widths. Capture.
- [ ] T004 **Project single fidelity + lightbox.** Hero/meta grid, body, gallery lightbox (single vs gallery
    triggers, play icons, visible open/close/focus-trap/Esc), prev/next + related — vs handoff. EN/AR. Capture.
- [ ] T005 Prove the editor workflow: edit a Project (title/meta/gallery) → single + archive + services
    selected-work all change. Evidence (incl. the real wp.media picker in wp-admin).
- [ ] T006 Full suite + guards; update durable memory; open PR.
