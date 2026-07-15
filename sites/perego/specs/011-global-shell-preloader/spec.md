# Spec 011 — Global Shell and Preloader

**Branch:** `feature/011-global-shell-preloader`
**Mode:** Client Site Mode
**Status:** In progress (preloader rebuilt 2026-07-14)
**Depends on:** 009 (block-first global content). **Owns:** the footer/header/CTA/404 Site-Editor canvas seam left by 009.

## Goal

Bring the global shell to exact handoff fidelity across EN/AR and all widths/states, and make the global
areas genuinely editable via FSE: preloader, header, desktop nav, services dropdown, mobile menu, language
switcher, active/sticky states, homepage anchors, standard footer, contact flat footer, links, social,
contact values, focus states, scroll locking, reduced motion.

## Authoritative source

`_design_handoff/Perego-Creative-Studio-Final-Handoff/site/` HTML + `css/styles.css`. The theme's
`perego-reference.scss` (compiled to `assets/css/main.css`, enqueued site-wide) is the derived authority.

## Functional requirements

- **FR-1 Preloader:** match the handoff exactly (logo, rings, glow, bar, wordmark, background, timing);
  appear on first session visit; never stuck; hard timeout fallback; no layout shift; reduced-motion safe;
  removes cleanly; don't block the admin bar for editors.
- **FR-2 Header:** logo, desktop nav, services dropdown, active route state, sticky/scrolled state — exact.
- **FR-3 Mobile menu:** slide-in, focus trap, scroll lock, Esc/backdrop/link close, services accordion.
- **FR-4 Language switcher:** real navigation to the AR/EN URL, current-locale marker, RTL flip.
- **FR-5 Footer (standard):** contact column, quick-message form, careers/join, approved links, exact design.
- **FR-6 Footer (contact flat):** the reduced composition, no quick-message column when excluded.
- **FR-7 Links/values:** no hard-coded `/about`; no generic social URLs (owner handles pending); manageable.
- **FR-8 FSE:** the global areas preview + edit in the Site Editor (the 009 seam).
- **FR-9 EN/AR + focus + reduced motion** verified across all required widths.

## Acceptance

- [ ] Exact screenshot comparison of the shell at all required widths, EN + AR.
- [ ] Preloader fully verified (appear/animate/timeout/reduced-motion/clean-removal).
- [ ] No hard-coded `/about`; no generic social-homepage URLs; links manageable.
- [ ] FSE preview works for header/footer.

## Notes

Much of the shell was built in spec 008 (header/footer/preloader interactions). This spec closes the
fidelity gaps against the handoff and the FSE-editability seam. Interaction correctness (sticky, dropdown,
mobile, Escape/focus) was already verified in spec 008's `verify-interactions.mjs`.
