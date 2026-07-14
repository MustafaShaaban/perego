# Tasks: Visual fidelity recovery

- [ ] T001 Create deterministic static-handoff baseline capture and diff infrastructure under `sites/perego/output/visual-recovery/`.
  - [x] Capture the initial static and live EN desktop diagnostic images.
  - [x] Add a deterministic Home EN desktop runner for hero, About, Services, Clients, and footer states; it stores baseline/current/red-pixel-diff PNGs plus an explicitly unreviewed manifest.
  - [ ] Automate normalized baseline/current/diff capture for every route, state, locale, and required viewport.
- [ ] T002 Port `site/css/styles.css` and required handoff assets into the client theme with separate WordPress adapter/editor layers.
  - [x] Preserve the source stylesheet untouched in `perego-reference.scss` and separate the WordPress/editor layers.
  - [x] Map the copied stylesheet's handoff background URLs to the production theme build location without changing their visual values.
  - [ ] Verify every required asset/font is production-safe and referenced by the matching route contract.
- [x] T003 Audit and correct root/global-style/CoreX interference; record selectors and remediation in `sites/perego/docs/visual-recovery.md` (2026-07-14: the interference log's 17-row table already covers every shared shell component and every route — header, footer, hero, clients carousel/controls, About, services teaser/singles, work/project, journal/post, search/legal/pages, contact, 404, sticky header, CSS assets, FSE layout wrappers, and route-block DOM — each with its root cause and remediation recorded).
- [ ] T004 [US1] Rebuild header/navigation/dropdown/mobile panel and language pills to the reference DOM contract.
  - [x] Align desktop header, nav, dropdown, AR/EN pill order, and handoff class contract.
  - [x] Verify mobile panel, focus trap, dropdown state, and RTL at all required viewports (2026-07-14:
    `verify-interactions.mjs` 9/9 functional + 26 manually reviewed EN/AR captures at every required
    viewport in `docs/visual-recovery.md`; two real contrast/role a11y bugs found and fixed along the way).
    This closes the shared-shell header/nav slice; still open across the rest of T004/T005: footer
    field/error/loading/success form-state screenshots and the contact-page flat-footer layout.
- [x] T005 [US1] Rebuild shared buttons, fields, form states, standard footer, and flat footer to the reference contract.
  - [x] Align the shared footer and form wrappers while preserving the CoreX/careers submit pipelines.
  - [x] Verify every field/error/loading/success state visually (2026-07-14). Found and fixed the root
    cause of the native "Please fill out this field" bubble the completion contract flagged: CoreX's
    `FormBlockRenderer`/`FlowBlockRenderer` never emit `novalidate` on `<form class="corex-form">`, so
    browser constraint validation runs before the framework's own submit-time validator ever does. Fixed
    client-side (`perego-theme/assets/src/js/main.js` sets `form.noValidate = true` on load — framework
    code is out of Client Site Mode scope; flagged as a CoreX Framework Mode bug, not fixed upstream here).
    Also styled the framework's real state classes (`.corex-form__status.is-success/.is-error`,
    `.corex-is-loading`, `[aria-invalid="true"]`) to the handoff's banner/spinner/border look, and fixed a
    real desync bug where the decorative service-chooser buttons didn't reset when the shared runtime
    calls `form.reset()` after a successful submission. Manually verified with mocked REST responses:
    default, focus, filled, field-invalid + form-summary (no native bubble), loading (button dim + spinner,
    `corex-is-loading`/disabled confirmed via computed style), success (banner + form reset + chooser
    resync), server-error, rate-limited, and network-failure (falls back to the same generic message —
    a framework design choice, not a Perego gap).
  - [x] Contact-page flat-footer layout verified (2026-07-14): `SiteFooterRenderer::render(flat: true)`
    correctly renders the handoff's 2-column `.site-footer--flat` (Contact + quick-message, no Careers
    column) with real dynamic contact/social data and the shared bottom bar. Noted, not fixed (dead code,
    no visual effect): `site-footer/style.scss` styles `.perego-footer*` classes that the renderer never
    emits — the real `.site-footer*` styling comes from `perego-reference.scss`, so this per-block
    stylesheet is orphaned; a cleanup candidate, not a defect.
- [ ] T006 [US2] Rebuild Home and Services routes, including desktop/mobile/RTL states and visual evidence.
  - [x] **Home hero Previous/Next/Pause controls (2026-07-14):** confirmed and fixed the root cause of
    "absent or unproven" — see the "Block-owned stylesheets never enqueued" row in
    `docs/visual-recovery.md`. Six blocks' `block.json` were missing the `"style"` key entirely
    (`hero-slider`, `clients-carousel`, `services-teaser`, `home-about-bg`, `portfolio-grid`,
    `site-footer`, `site-header`), so their compiled CSS never loaded on any route. For the hero this
    meant `.hero__controls` had no positioning at all and sat beneath the always-loaded `.hero__prism`
    background layer, making Previous/Next/Pause completely unclickable (confirmed via a Playwright click
    timeout: "element intercepts pointer events"). Fixed by adding the missing `"style"` declarations.
    Manually verified live: Next/Prev/Pause now clickable, live region announces "Slide X of 3", and an
    explicit pause persists through subsequent hover in/out (WCAG 2.2.2 compliant). Full suite re-verified
    green after the fix (Pest 224/224, Jest 69/69, route-health 72/0, interactions 9/9, a11y 12/0); no
    visual regression on Home/Work archive from the newly-active CSS.
  - [x] Services archive "Our Process" (2026-07-14): `ServicesOverviewRenderer::renderProcess()` rendered
    a plain `<ol><li><strong>Label</strong> — desc</li></ol>` with zero active CSS (`.svc-process` styling
    only existed in the inactive `perego-legacy-pre-recovery.scss`) — exactly the "plain text list, not
    designed cards/icons/arrows" defect the completion contract names. Rebuilt to the handoff's
    `.process`/`.process-list`/`.process-step`/`.process-step__icon`/`__label`/`__desc`/`.process-arrow`
    contract using the theme's existing icon assets (already present, no new assets needed) and the
    already-active `perego-reference.scss` rules. Full-page live screenshot at 1440 now matches the
    handoff's process section closely (icons, arrows, gradient band, spacing, typography). Pest 224/224
    (new coverage for step/arrow counts + icons), route-health 72/0, a11y 12/0.
  - [x] **Selected work + site-wide media lightbox (2026-07-14):** built the missing `.portfolio.page-section`
    / `.work-masonry` / `.work-card` section from real published projects (`ServicesOverviewRenderer`
    stays a pure function of a pre-resolved `$selectedWork` array — real `WP_Query` + `ProjectRepository`
    live in the render_callback, matching `ProjectGalleryLightboxRenderer`'s established testability
    pattern). A project with 2+ gallery images opens as a gallery; otherwise its featured image opens
    singly. Since no accessible mixed-media (image/video/gallery) lightbox existed anywhere in the
    codebase, built `perego-theme/media-lightbox`: one dialog rendered once in both footer template
    parts, whose plain-JS view script delegates a click listener to every `[data-image]`/`[data-video]`/
    `[data-gallery]` trigger site-wide (ported from the handoff's own `main.js` lightbox IIFE — media-type
    detection, prev/next, dots) with the accessible-dialog contract already proven by
    `project-gallery-lightbox` (role="dialog", focus trap, Escape/backdrop close, focus restoration,
    scroll lock) plus an `inert` background while open. 7 Pest + 7 Jest, all new.
  - [x] **Critical fix found while verifying the above — the reference stylesheet's `.lightbox` was
    invisible on BOTH lightboxes site-wide, including the already-shipped project gallery (2026-07-14):**
    `perego-reference.scss`'s `.lightbox` rule is `opacity:0;visibility:hidden` by default and only
    becomes visible via an `.is-open` class, which is how the static handoff's own demo JS toggles it —
    but both Perego lightbox blocks instead toggle the WordPress-idiomatic `hidden` attribute, which that
    rule never accounts for. Result: `project-gallery-lightbox` (already shipped, "verified" via 9/9
    interaction checks) opened correctly in the DOM — `hidden` removed, focus trap, aria-modal, scroll
    lock all fired — but was **completely invisible on screen** (confirmed via `getComputedStyle`:
    `opacity:0`), and the new media-lightbox above would have shipped with the exact same defect. The
    prior interaction checks never caught this because they only asserted DOM attributes, never computed
    style. Fixed with one scoped adapter rule, `.lightbox:not([hidden]) { opacity:1; visibility:visible; }`
    (higher specificity than the reference rule, so no `!important` needed), which fixes both lightboxes
    at once. **Strengthened `verify-interactions.mjs`** to assert `getComputedStyle(...).opacity/visibility`
    directly (not just the `hidden` attribute) for both lightboxes, so this exact bug class can never
    silently regress again. Verified live with screenshots: both dialogs now render fully visible,
    correctly styled (backdrop, close/prev/next controls, dots). Pest 230/230, Jest 76/76,
    route-health 72/0, interactions 12/12, a11y 12/0.
  - [x] **Per-service-single "Our Process" migration (2026-07-14):** the four service singles' own
    process sections were seeded as plain editable `wp:list` blocks with the same missing icon/arrow
    design as the archive had — unlike the archive's PHP-only render, this is editor-canvas content
    already seeded on 8 live EN/AR posts, so fixing the render alone would not have touched them.
    Extracted the icon/card/arrow block-builder into a shared, side-effect-free
    `scripts/lib-service-process-blocks.php` (used by both `seed-services.php`, for future seeds, and
    a new one-time `scripts/migrate-service-process.php`, mirroring the exact established pattern of
    `migrate-service-whatwedo-media.php`). Each step's label/desc stays on real editable core blocks
    (`wp:paragraph`/`wp:image` inside `wp:group`); only the icon and the decorative arrows between
    steps are structural. Ran the migration against the live install: **8/8 posts migrated**, second
    run confirms idempotency (0 migrated). Verified live on all four EN services + one AR service:
    correct per-service steps, icons, and arrows render identically to the archive's design. `parse_blocks()`
    sanity check confirms no orphaned/invalid block content. Full suite re-verified green: Pest 230/230,
    route-health 72/0, a11y 12/0.
  - [x] **Individual client video lightbox (2026-07-14):** the completion contract's "Individual client
    cards lack the required visible play affordance and functioning video/embed lightbox" is now
    fully implemented — `ClientsCarouselRenderer::individualCard()` reads a new
    `_perego_client_video_url` post-meta field; when an editor sets a real URL, the card opens it in
    the site-wide `media-lightbox` and shows the handoff's `.play-btn` affordance, otherwise it stays
    a plain non-interactive card (a decorative play icon on a card with nothing to play would be a
    misleading affordance, and the seeded demo clients deliberately carry no fabricated video content).
    Corporate client cards were re-checked against the handoff's own `index.html`: its `.corp-card`
    markup has no `data-video`/`data-image`/`data-gallery` trigger at all, only the individual cards
    do — so "corporate media lightboxes" were never actually part of the locked design, and no change
    was needed there. 2 new Pest tests (with/without a video URL). Manually verified live: temporarily
    set a real video URL on a seeded client, confirmed the play-btn renders only on that card, and that
    clicking it opens the lightbox as a working autoplaying iframe embed with correct focus
    trap/Escape/scroll-lock — then reverted the test data.
  - [x] **Regression caught and fixed while verifying the above:** `#individualTrack` (the individual
    client scroll track) was missing `tabindex="0"` — present on the corporate track already but never
    added to individual — triggering a real axe `scrollable-region-focusable` violation on both EN and
    AR home. Added the same `tabindex="0"` + `aria-label` the corporate track already carries.
    Full suite re-verified green: Pest 232/232, route-health 72/0, interactions 12/12, a11y 12/0.
- [ ] T007 [US2] Rebuild Work/project and Journal/single-post routes, including cards, filters, gallery, and visual evidence.
  - [x] Single-post comment form (2026-07-14): confirmed the exact defect the completion contract names —
    WordPress's core `wp:comments`/`wp:post-comments-form` blocks already inherit the handoff's own
    `.comment-form` glass-card container verbatim (class names match; `perego-reference.scss` is an
    untouched copy), but core hardcodes a white background + grey border on the bare `<input>`/`<textarea>`
    elements, producing exactly the "native white comment fields" defect. Fixed with scoped CSS in
    `perego-wordpress-adapter.scss` restyling only the form controls (transparent background, subtle
    border, white text, accent focus ring) and the submit button (accent background, dark text, matching
    every other Perego button). Left native HTML5 required-field validation as-is (no CoreX-style custom
    validator exists for core comments — replacing it without one would silently drop the only validation
    this form has); a full "shared form state system" for comments (section 12) remains open follow-up.
    Verified live (dark theme now applies, no white boxes); Pest 224/224, route-health 72/0, a11y 12/0.
- [ ] T008 [US2] Rebuild Contact, Search, standard page, legal, and 404 routes with all required states and evidence.
- [ ] T009 [US3] Verify content remains FSE/editor-canvas managed and Polylang Free EN/AR behavior remains linked and RTL-correct.
- [ ] T010 Complete state/viewport visual regression, a11y, performance, security, quality gates, docs, PR, and merge.
