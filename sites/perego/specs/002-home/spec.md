# Spec 002 — Home (M2): hero slider + services teaser

**Status**: in progress · **Branch**: `feature/002-home` · **Depends on**: spec 001 (global shell,
tokens, language mechanism, Interactivity API conventions).

## Purpose

Build the homepage's two bespoke, dynamic FSE blocks and compose the homepage from them, faithful to
the design handoff prototype (`_design_handoff/.../site/index.html` + `styles.css` + `main.js`) and its
content (`content/{en,ar}.json → home`). No redesign — same tokens, layout, copy, motion, a11y.

Reconciliation note: the M2 design doc names the second block `perego/services-tabs (2×2 glass tiles,
bound to the service CPT)`. The actual handoff home page has **no tabs** — its services section is the
`.services-teaser` card grid (2×2 image cards linking to the four service pages, staggered offsets,
hover lift). Fidelity to the prototype governs, so this block is built as `perego/services-teaser`. The
`service` CPT does not exist until M3; the teaser is content-driven now and its card targets/labels are
rebindable to the CPT in M3 without markup change.

## User stories

### US1 — Hero headline slider (P1) 🎯 MVP
As a visitor landing on the homepage, I see a full-bleed hero with a rotating set of headline slides so
the studio's message lands with motion and impact.

**Acceptance** (from INTERACTIONS.md "Hero slider" + ACCESSIBILITY_HANDOFF build notes):
1. Three slides from `home.hero.slides`; slide 0 visible on load, its title an `<h1>`, the rest `hidden`.
2. Auto-advances every 6.5s (token `--perego-motion-hero-slide-delay`); wraps around.
3. Dot tablist (`role="tablist"`, one `role="tab"` per slide, `aria-selected` reflects the active dot).
4. Pauses on pointer hover and when the tab is hidden (`visibilitychange`); resumes on leave/visible.
5. **Never auto-advances under `prefers-reduced-motion: reduce`** (respects the token motion gate).
6. Build additions over the static prototype: prev/next controls, a pause/play toggle, an `aria-live`
   region announcing the current slide, and stop-on-interaction (manual dot/prev/next/pause halts the
   timer; pause/play toggles it back).
7. A "Say Hello!" CTA (`home.hero.cta`) links to the contact page.
8. Entirely token-driven — no literal color/size/motion values in the block CSS.

### US2 — Services teaser (P1) 🎯 MVP
As a visitor, I see a "Services we can help you with" section with four service cards so I can jump into
any of the studio's four services.

**Acceptance** (from COMPONENTS.md "Service cards" + the prototype):
1. Section heading `home.servicesTeaser.title` + a "See All Services" arrow link (`home.servicesTeaser.seeAll`)
   to the services archive.
2. Exactly four cards, in the fixed order Video Editing · 2D Motion Graphics · Graphic Design · Website
   Making, each linking to its service single (`/services/<slug>`), with the two-line uppercase label,
   overlay, and the staggered vertical offsets + hover lift the prototype defines (all via tokens/CSS).
3. Card images use `loading="lazy"` and meaningful `alt`; labels come from `services.*.name`.
4. Token-driven; RTL-correct via logical properties (labels `text-align: start`, offsets symmetric).

### US3 — Homepage composition (P2)
The `front-page.html` template composes header → preloader → hero-slider → (about + teaser) → footer, so
the homepage renders the new blocks in the handoff's order. The About panels reuse core group/heading/
paragraph blocks (no bespoke block needed). Clients section is deferred to M4 (needs the `client` CPT).

## Out of scope (deferred)
- `client` CPT + `perego/client-carousel` (M4), the corporate/individual clients section on home.
- `service` CPT binding (M3) — teaser is content-driven until then.
- Hero background imagery is decorative; the asset pipeline/real images land in M7 polish.

## Non-functional
- WCAG 2.2 AA: hero live region, focus-visible controls, reduced-motion gate, tablist semantics.
- i18n: all copy through `esc_html__`/content; RTL via logical properties.
- Tests: Pest (render) for both renderers; Jest (Interactivity API) for the hero view.js state machine.
- Guard Gate: wp-guard + clean-code-guard on PHP, test-guard on tests, before the diff ships.
