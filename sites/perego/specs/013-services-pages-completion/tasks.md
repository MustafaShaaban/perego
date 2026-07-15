# Spec 013 — Tasks

- [x] T001 **Audit vs handoff (structure + editability).** Archive `/services/` and all four singles render
    200, all handoff sections present (`svc-hero`, `whatwedo`, `process`, `portfolio`), EN/AR. `whatwedo` +
    `process` are editable `post-content`. **Gap:** `svc-hero` (title/eyebrow/subline/tabs) comes from the
    hardcoded `ServiceContent::COPY` const, not the `perego_service` post; the `/services/` overview likely
    the same. Selected-work source to verify (Projects vs hardcoded). Recorded in spec.md audit table.
- [x] T002 **Seam confirmed.** Service H1 ← the Service post's own title (native editing); tab labels (single +
    archive) ← each Service's `_perego_teaser_label` (spec-012 meta); both with `ServiceContent` seed fallback.
    Eyebrow/CTA labels stay translatable chrome. Selected work already sources real Projects. New shared
    `Content\ServiceCatalog` (`postsBySlug`/`labelsBySlug`) is the one place the teaser, hero tabs, and archive
    project the Service CPT (removed the teaser's private duplicate — DRY).
- [x] T003 **Service hero ← CPT — done + proven.** `ServiceHeroRenderer` takes the current post title (H1)
    and a per-slug editable tab-label map, falling back to `ServiceContent` per field. Callback passes the raw
    `post_title` (so the single `esc_html` matches the old byte-for-byte, `&amp;` not `&#038;`) and
    `ServiceCatalog::labelsBySlug`. **Verified live:** EN `/services/video-editing` H1 "Video Editing &amp;
    Post-Production" + AR "مونتاج الفيديو وما بعد الإنتاج" byte-identical; edit round-trip (post 13 title →
    service-page H1 → restored). Override unit test added.
- [x] T004 **Archive ← CPT — done.** `services-overview` hero tabs use the editable teaser labels (seed
    fallback); byte-identical live. **Fixed a latent bug:** the archive tab CTA passed `?service=<name>` where
    the contact chooser whitelists the `<slug>` — now `?service=video-editing` (matches the service-single
    hero). Test strengthened.
- [x] T005 **Selected work ← Projects — verified.** The archive's "Selected work" (via `WP_Query` +
    `ProjectRepository`) and the single's `service-selected-work` (service→category → Project query) both
    source real published Projects. No change needed.
- [x] T006 **Fidelity confirmed.** Capture `output/playwright/013-service-single-hero.png` — the single's
    svc-hero matches the handoff (OUR SERVICES eyebrow, H1 from the post title, four tabs with active state +
    START YOUR PROJECT CTA). whatwedo/process/portfolio unchanged from prior specs; archive tabs byte-identical
    live. AR verified byte-identical via live HTML. Interaction/reduced-motion carried by the unchanged CSS/JS.
- [x] T007 **Editor workflow proven** — editing Service post 13's title changed its service page H1 (T003) and
    (spec 012) its homepage teaser card; the archive/single tabs track each Service's teaser label. Real WP
    round-trips, restored.
- [x] T008 **Full suite + guards + PR.** Pest 251/251 (784 assertions); no JS touched → Jest unaffected.
    clean-code-guard + wp-guard pass. Durable memory updated; PR opened.
