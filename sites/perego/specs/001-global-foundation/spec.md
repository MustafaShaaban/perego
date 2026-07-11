# Feature Specification: Global Foundation (tokens, header/footer shell, bilingual, preloader)

**Feature Branch**: `001-global-foundation`

**Created**: 2026-07-11

**Status**: Draft

**Input**: User description: "Every page of the Perego site shares one branded shell: the dark-violet/
magenta identity from the design handoff must be applied consistently everywhere (not per-page), the
site must work correctly in both English (LTR) and Arabic (RTL), and returning visitors on the
homepage should see the studio's branded preload moment on their first visit of a session — all
built as FSE blocks/parts, not hardcoded page markup, so an editor can still rearrange or restyle
without touching code."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A consistent, on-brand shell on every page (Priority: P1) 🎯 MVP

A visitor lands on any Perego page — home, a service page, the journal, contact — and sees the same
dark-violet/magenta identity, the same header (logo, navigation, "Start a Project" CTA) and footer
(contact info, quick-message and careers forms, legal links) framing the content, styled from one
consistent set of design tokens rather than page-by-page CSS.

**Why this priority**: without a shared shell and token system, every subsequent page (M2 onward)
would each reinvent colors/spacing/header/footer, guaranteeing visual drift from the approved design
and duplicated work.

**Independent Test**: activate the theme with no other feature built yet; every template renders the
same header and footer, and inspecting any color/spacing/radius in the browser resolves to a design
token (no hardcoded value), matching `design-tokens.json`.

**Acceptance Scenarios**:

1. **Given** any page on the site, **When** it loads, **Then** the header (logo, primary nav, CTA,
   language toggle, mobile hamburger ≤1024px) and footer (3-column: contact / quick-message form /
   careers form + bottom bar) render identically in structure across pages.
2. **Given** the page is scrolled more than 20px, **When** the header is observed, **Then** it gains a
   blurred/backdrop + hairline-border "scrolled" state.
3. **Given** the viewport is ≤1024px, **When** the hamburger is activated, **Then** a slide-in nav
   panel opens with a backdrop, traps focus, closes on Esc/backdrop-click/link-click, and restores
   focus to the hamburger on close.
4. **Given** the "Services" nav item on desktop, **When** hovered or focused, **Then** its dropdown of
   the 4 service pages opens (`:hover`/`:focus-within`); **Given** the same on a ≤1024px viewport,
   **When** tapped, **Then** it expands as an accordion instead of navigating.
5. **Given** any color, spacing, radius, shadow, or motion-duration value in the rendered CSS, **When**
   inspected, **Then** it resolves to a theme design token (no literal hex/px/ms hardcoded in block
   styles).

---

### User Story 2 - The site works correctly in English and Arabic (Priority: P1) 🎯 MVP

A visitor switches the language toggle between English and Arabic. The entire layout mirrors
correctly (RTL), the Arabic type stack (Cairo) is used instead of the Latin stack (Open Sans), and the
choice is remembered on their next visit.

**Why this priority**: bilingual EN/AR is a core, non-negotiable requirement (constitution Principle
VIII) — every subsequent page depends on this working before it is built, not patched in afterward.

**Independent Test**: toggle the language switcher; `<html lang>`/`dir` flip, the font stack swaps,
the layout mirrors with no visual breakage, and reloading the page in a new tab keeps the chosen
language.

**Acceptance Scenarios**:

1. **Given** the language toggle, **When** activated, **Then** `<html lang>` and `dir` update
   (`en`/`ltr` ↔ `ar`/`rtl`) and the visible language persists across a full page reload.
2. **Given** an Arabic session, **When** any page renders, **Then** the Cairo/Tajawal font stack is
   used in place of Open Sans, and the layout mirrors using logical CSS properties (no separate
   "RTL stylesheet" hacks).
3. **Given** a returning visitor who previously chose Arabic, **When** they open a new tab to the
   site, **Then** their language choice is remembered and applied without needing to toggle again.

---

### User Story 3 - A branded first-visit preload moment on the homepage (Priority: P3)

A first-time visitor in a session lands on the homepage and briefly sees the studio's branded
preloader before the page settles, rather than a flash of unstyled content; a returning visitor
within the same session, or anyone with reduced-motion preferences enabled, does not see it again.

**Why this priority**: a nice-to-have brand moment, not blocking for any other page or feature — pages
other than the homepage never show it, and its absence doesn't break any user task.

**Independent Test**: clear session storage, load the homepage; the preloader shows and clears itself
within ~2.5s even if an asset never finishes loading. Reload the homepage again in the same session;
it does not show. Enable `prefers-reduced-motion`; it never shows.

**Acceptance Scenarios**:

1. **Given** a first visit to the homepage in a new session, **When** the page loads, **Then** the
   preloader displays and clears itself around 0.9s after first paint, without waiting for every
   asset to finish loading.
2. **Given** the preloader has already shown once in the current session, **When** the homepage (or
   any other page) is loaded again, **Then** it does not show again.
3. **Given** `prefers-reduced-motion` is enabled, **When** the homepage loads, **Then** the preloader
   never displays at all.
4. **Given** an asset or script failure during load, **When** 2.5s elapses, **Then** the preloader is
   hidden regardless (a hard safety timeout), so a failure can never permanently block the page.

---

### Edge Cases

- What happens if JavaScript fails to load entirely? The header/footer/nav must still render and be
  usable (progressive enhancement) — the mobile menu and language toggle degrade to plain links/no-op
  rather than breaking the page; the preloader (User Story 3) must never appear without JS, since it is
  entirely JS-controlled and no CSS-only fallback would be able to remove it (a no-JS visitor never
  sees a stuck overlay, because the overlay is only inserted by JS in the first place).
- What happens on a viewport exactly at the 1024px mobile-nav breakpoint? Behavior matches the
  ≤1024px (mobile) case, per the design tokens' documented breakpoint semantics.
- What happens if a visitor has no stored language preference (first-ever visit)? Default to English
  (LTR), the site's primary language.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The theme MUST expose header and footer as reusable FSE template parts consumed by
  every page template, not per-template markup.
- **FR-002**: The header MUST render: logo, primary navigation (Home, About Us, Services with a
  dropdown to the 4 service pages, Work, Journal, Clients, Contact Us), a "Start a Project" CTA
  (hidden ≤1024px), a language toggle, and a hamburger control (≤1024px only).
- **FR-003**: The header MUST gain a visually distinct "scrolled" state once the page scrolls past
  20px, and lose it when scrolled back above that threshold.
- **FR-004**: On viewports ≤1024px, activating the hamburger MUST open a slide-in navigation panel
  with a backdrop; it MUST trap keyboard focus while open, close on Escape / backdrop click / a nav
  link click, lock background scroll while open, and restore focus to the hamburger control on close.
- **FR-005**: The footer MUST render a 3-column layout (contact details, a quick-message form entry
  point, a careers form entry point) plus a bottom bar (copyright, legal links); actual form submission
  wiring is out of scope for this feature (see Assumptions).
- **FR-006**: All design tokens (color, gradient, typography scale, spacing, radius, border, shadow,
  glass, motion durations/easing, breakpoints, z-index scale) from `design-tokens.json` MUST be
  represented in the theme's `theme.json` and/or global stylesheet as the single source of truth; no
  block or page style may hardcode a value that exists as a token.
- **FR-007**: All layout CSS MUST use logical properties (`margin-inline-start`, `inset-inline-end`,
  etc.) so RTL mirrors without a separate stylesheet.
- **FR-008**: The site MUST support English (LTR, Open Sans) and Arabic (RTL, Cairo/Tajawal) via a
  language toggle that updates `<html lang>`/`dir`, swaps the font stack, and persists the visitor's
  choice across sessions in the browser.
- **FR-009**: The homepage MUST show a branded preloader on a visitor's first page view of a session
  only, clearing ~0.9s after first paint independent of full asset load, with a hard 2.5s safety
  timeout; it MUST NOT appear on any other template, on a repeat view within the same session, or
  when `prefers-reduced-motion` is set.
- **FR-010**: Every interactive behavior in this feature (sticky header, mobile nav, dropdown/
  accordion, language toggle, preloader) MUST be implemented as WordPress Interactivity API
  directives scoped to its own block — no global/ad hoc JavaScript.

### Key Entities

- **Design tokens**: the single source of truth (`design-tokens.json` → `theme.json` +
  `--wp--custom--perego--*` custom properties) for color, typography, spacing, radius, shadow, glass,
  motion, breakpoints, and z-index — consumed, never redefined, by every block/template in this and
  future features.
- **Language preference**: a visitor-level, browser-persisted choice (English/Arabic) that drives
  `lang`/`dir` and the active font stack across the whole site.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Every page template on the site renders the same header/footer structure with zero
  visual drift between templates (verified by comparing rendered pages against the handoff's
  reference screenshots).
- **SC-002**: 100% of color/spacing/radius/shadow/motion values in the rendered CSS trace to a named
  design token — zero hardcoded literals found on inspection.
- **SC-003**: A visitor can switch language and have the entire layout — text direction, font, nav
  order — mirror correctly with no broken or overlapping elements, in under 1 second.
- **SC-004**: A first-time homepage visit shows and clears the preload moment within 2.5 seconds in
  100% of cases, including simulated asset failure.
- **SC-005**: Keyboard-only navigation can open the mobile menu, move through every link inside it,
  and close it without a mouse, with focus never escaping to content behind it while open.

## Assumptions

- Real form submission handling (contact quick-message, careers upload) is **out of scope** here —
  this feature renders the footer's form entry points structurally; wiring them to `corex-forms` is
  M4 (Contact + Clients) per the approved design (`docs/superpowers/specs/2026-07-11-perego-corex-design.md`).
- Actual page content (hero, service tiles, portfolio, etc.) is **out of scope** here — this feature
  is the shell + tokens + i18n + preloader only; content-bearing sections are M2 onward.
- Arabic content strings are **not** translated as part of this feature (no page content exists yet);
  only the mechanism (font swap, `dir` mirroring, persisted choice) is built and verified against
  placeholder/UI-chrome strings (nav labels, CTA, toggle itself).
- Polylang (or an equivalent, per the framework's own documented preference, behind an abstraction per
  constitution Principle IX) is the mechanism for the language mechanism; if unavailable in this
  environment, a minimal same-effect toggle (cookie/localStorage-driven `lang`/`dir` swap) is an
  acceptable interim, documented as a follow-up in `DECISIONS.md`.
- "Brand tokens" (CoreX's `brand.json` overlay mechanism) does not apply — `perego-theme` carries its
  own complete `theme.json`, per `DECISIONS.md`.
