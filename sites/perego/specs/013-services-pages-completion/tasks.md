# Spec 013 — Tasks

- [x] T001 **Audit vs handoff (structure + editability).** Archive `/services/` and all four singles render
    200, all handoff sections present (`svc-hero`, `whatwedo`, `process`, `portfolio`), EN/AR. `whatwedo` +
    `process` are editable `post-content`. **Gap:** `svc-hero` (title/eyebrow/subline/tabs) comes from the
    hardcoded `ServiceContent::COPY` const, not the `perego_service` post; the `/services/` overview likely
    the same. Selected-work source to verify (Projects vs hardcoded). Recorded in spec.md audit table.
- [ ] T002 Confirm the exact editable seam per surface (CPT title/meta vs seed) + selected-work source; log in
    DECISIONS.md. Reuse the spec 012 projection + per-field seed-fallback pattern.
- [ ] T003 **Service hero ← CPT.** `ServiceHeroRenderer` derives title/subline/tabs from the published
    Services (title + meta) with `ServiceContent` seed fallback; exact markup; EN/AR. Seeder if new meta.
- [ ] T004 **Archive ← CPT.** `services-overview` lists published Services (title + spec-012 teaser meta);
    exact handoff composition; graceful empty state.
- [ ] T005 **Selected work ← Projects.** Verify/fix the single's selected-work to source real Project posts,
    matching the handoff portfolio grid.
- [ ] T006 Fidelity + states: four singles + archive vs handoff at all widths, EN/AR, hover/focus/reduced
    motion/tab active. Capture evidence.
- [ ] T007 Prove the editor workflow: edit a Service → its single + archive + homepage teaser all change.
- [ ] T008 Full suite + guards; update durable memory; open PR.
