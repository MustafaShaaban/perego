# Spec 011 — Tasks

- [x] T001 **Preloader rebuilt to the locked handoff.** The renderer emitted only a bare
    `.perego-preloader` "Loading…" placeholder; the authoritative reference stylesheet already carried the
    full `.preloader*` design (stage, 3 rings, glow, logo, bar, wordmark, keyframes, `is-hidden`). Rewrote
    `PreloaderRenderer` to emit the exact handoff structure (`class="preloader"` + rings/glow/logo/bar/
    wordmark `بيريجو · PEREGO`, logo from the theme URI), kept the Interactivity wiring
    (`data-wp-class--is-hidden` → session/soft-0.9s/hard-2.5s/reduced-motion logic in view.js, unchanged).
    Removed the orphaned `preloader/style.scss` + its `style` key/import (one visual authority = reference).
    Verified live: homepage renders all handoff parts; `main.css` (enqueued) styles them. Pest 243/243,
    Jest preloader 6/6. Updated the render test to the handoff structure.
- [ ] T002 Header/desktop-nav/services-dropdown fidelity audit vs handoff (logo, spacing, active state).
- [ ] T003 Sticky/scrolled header state exact.
- [ ] T004 Mobile menu (slide-in, focus trap, scroll lock, Esc/backdrop/link close, services accordion).
- [ ] T005 Language switcher (real nav, current marker, RTL flip) — verify against handoff.
- [ ] T006 Standard footer fidelity (contact col, quick-message, careers, links, social, contact values).
- [ ] T007 Contact flat footer (reduced composition, no quick-message column).
- [ ] T008 Remove any hard-coded `/about`; replace generic social URLs with manageable values (owner handles
    pending — keep as documented placeholders, not fabricated real accounts).
- [ ] T009 FSE editability of header/footer (the 009 seam): editable template-part blocks + preview.
- [ ] T010 EN/AR × all required widths screenshot comparison; focus states; reduced motion. Evidence.
- [ ] T011 Full suite + guards; update durable memory; open PR.
