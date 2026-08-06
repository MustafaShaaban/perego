# Feature Specification: Lighthouse Client Performance

**Feature Branch**: `fix/026-lighthouse-client-performance`  
**Created**: 2026-07-30  
**Status**: Draft  
**Input**: Fix the actionable Lighthouse findings for the Perego website, follow
CoreX image-delivery best practices including WebP, and do not modify the CoreX
framework.

## Clarifications

### Session 2026-07-30

- Q: Which audit findings are in scope? → A: Only reproducible findings owned by
  the Perego client site. Findings produced by browser extensions, third-party
  services, WordPress core, or CoreX framework assets are documented but are not
  changed.
- Q: May the CoreX framework be modified to improve image delivery? → A: No.
  Perego must consume the existing CoreX media contract from client-owned code.
- Q: Is a visual redesign expected? → A: No. Existing imagery, layout, copy,
  responsive behavior, and bilingual behavior must remain visually equivalent.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Receive efficient homepage media (Priority: P1)

As a visitor, I want the homepage imagery to use appropriately compressed modern
formats so that the page becomes interactive quickly without losing its intended
visual quality.

**Why this priority**: The three largest first-party images account for most of
the measured transfer size and most of Lighthouse's estimated savings.

**Independent Test**: Load the homepage in a clean modern browser and confirm
that supported clients receive WebP for the identified hero and decorative
images while an original-format fallback remains available.

**Acceptance Scenarios**:

1. **Given** a browser that supports WebP, **When** it requests a target homepage
   image, **Then** the browser selects a WebP source.
2. **Given** a browser that does not select WebP, **When** it renders the same
   content, **Then** a valid fallback image is displayed.
3. **Given** the optimized media build, **When** the target assets are compared
   with the current production assets, **Then** their combined transfer size is
   materially lower while visual intent is preserved.

---

### User Story 2 - Experience stable and accessible content (Priority: P1)

As a visitor using any input method or assistive technology, I want images and
interactive client cards to expose correct dimensions and semantics so that the
layout is stable and controls are announced accurately.

**Why this priority**: Missing dimensions and an invalid role are real,
reproducible first-party issues in the supplied audit.

**Independent Test**: Inspect the rendered homepage without JavaScript
extensions and confirm target images have intrinsic dimensions and native
buttons/links retain their native accessible roles.

**Acceptance Scenarios**:

1. **Given** the page header, preloader, and footer, **When** the site logo is
   rendered, **Then** it includes valid intrinsic width and height.
2. **Given** a corporate client card that opens a lightbox, **When** its
   accessibility role is computed, **Then** it remains a button rather than
   being overridden by an incompatible list-item role.
3. **Given** a client card that navigates to a URL, **When** its accessibility
   role is computed, **Then** it remains a link.
4. **Given** decorative or below-the-fold imagery, **When** the page loads,
   **Then** it uses appropriate non-blocking loading behavior without hiding
   meaningful content from assistive technology.

---

### User Story 3 - Benefit from repeat-visit caching and trustworthy audits
(Priority: P2)

As a returning visitor and site maintainer, I want static Perego assets to use a
safe cache policy and audits to distinguish site defects from extension noise so
that repeat visits are faster and remediation remains evidence-based.

**Why this priority**: The supplied report estimates substantial cache savings,
but also contains several findings caused by the browser profile rather than the
website.

**Independent Test**: Request a Perego-owned static asset twice and inspect its
cache response policy, then run Lighthouse in an extension-free browser and
verify that third-party extension code is absent from the result.

**Acceptance Scenarios**:

1. **Given** a Perego-owned static image, font, stylesheet, or script, **When**
   the web server serves it, **Then** it includes an explicit browser cache
   lifetime suitable for a versioned site asset.
2. **Given** an extension-free Lighthouse run, **When** diagnostics are
   generated, **Then** Chrome extension URLs do not appear in site-owned
   remediation work.
3. **Given** a third-party service warning outside Perego's control, **When** the
   result is documented, **Then** it is identified separately rather than
   prompting an unrelated framework or client-code change.

### Edge Cases

- A custom WordPress logo may have different intrinsic dimensions from the
  default Perego logo.
- Image conversion may fail or a modern-format sibling may be absent; the
  fallback must remain valid.
- The site may run on a host that does not honor directory-level Apache
  configuration; the deployment handoff must identify the equivalent host or
  CDN cache policy.
- Right-to-left and left-to-right layouts must render the same media correctly.
- JavaScript-disabled rendering must retain visible fallback images and usable
  native links.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The client media build MUST generate optimized WebP siblings for
  supported raster source images without modifying CoreX framework code.
- **FR-002**: The client media build MUST keep valid original-format fallbacks
  and MUST NOT increase their file size merely by re-encoding them.
- **FR-003**: Perego-owned PHP renderers MUST use the existing CoreX media
  contract where it is available and appropriate.
- **FR-004**: Static full-site-editing templates that cannot execute PHP MUST
  emit equivalent modern-source and fallback markup.
- **FR-005**: Target rendered images MUST include accurate intrinsic width and
  height attributes.
- **FR-006**: Above-the-fold imagery MUST load eagerly where required for the
  initial viewport; below-the-fold or decorative imagery MUST avoid unnecessary
  eager loading.
- **FR-007**: Native interactive elements MUST retain valid native
  accessibility semantics.
- **FR-008**: Carousel grouping semantics MUST remain understandable to
  assistive technology without applying incompatible roles to buttons or links.
- **FR-009**: Perego-owned static assets MUST receive a documented browser cache
  policy that permits safe asset refreshes.
- **FR-010**: Asset changes MUST include an explicit cache-busting version
  update where URLs rely on a site version.
- **FR-011**: The implementation MUST preserve existing visual appearance,
  responsive layouts, bilingual behavior, keyboard interaction, and content.
- **FR-012**: Verification MUST use an extension-free browser profile and MUST
  separate first-party findings from browser-extension and third-party
  findings.
- **FR-013**: The implementation MUST remain confined to `sites/perego/` and
  MUST NOT edit CoreX framework, runtime WordPress, or generated distribution
  sources.
- **FR-014**: The media pipeline MUST fail clearly when a source image cannot be
  decoded or an expected output cannot be written.

### Key Entities

- **Source Image**: The canonical client-owned raster asset used as media-build
  input.
- **Optimized Variant**: A generated modern-format or fallback image with known
  dimensions, byte size, and intended loading behavior.
- **Media Rendering Contract**: The relationship between the modern source,
  fallback source, intrinsic dimensions, alternative text, and loading
  attributes.
- **Audit Finding**: A Lighthouse observation classified by ownership as
  Perego-owned, framework-owned, WordPress-owned, third-party, or
  browser-extension noise.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: The combined transferred bytes for `hero-bg`, `wave-ribbon`, and
  `wavy-corners` decrease by at least 60% in a modern browser compared with the
  supplied audit baseline.
- **SC-002**: A clean mobile Lighthouse run scores at least 97 for Performance,
  100 for Accessibility, 95 for Best Practices, and 100 for SEO, subject to
  explicitly documented third-party network availability.
- **SC-003**: The clean audit reports no invalid ARIA role for a Perego-owned
  element and no missing intrinsic dimensions for the default header,
  preloader, or footer logo.
- **SC-004**: Supported browsers select WebP for every targeted homepage raster
  asset while all fallback image URLs return successfully.
- **SC-005**: Automated PHP, JavaScript, and browser tests covering the affected
  renderers and homepage behavior pass in both English and Arabic routes.
- **SC-006**: Visual checks at 375, 768, and 1440 CSS pixels show no new overflow,
  clipping, broken imagery, or unintended layout change.
- **SC-007**: A Perego-owned static asset response includes a non-zero explicit
  cache lifetime, or the deployment handoff records a verified host/CDN rule
  required to supply it.

## Assumptions

- The supplied audit is a valid production snapshot, but extension-origin URLs
  in it are not evidence of a Perego defect.
- WebP is the modern format supported by the existing CoreX media contract; the
  fallback remains PNG or JPEG as appropriate.
- Directory-level Apache configuration is acceptable for the current local WAMP
  host, with equivalent CDN/host configuration documented for production.
- No database or content migration is required.

## Out of Scope

- Changes to CoreX framework plugins, packages, theme sources, or public APIs.
- Changes to WordPress core, runtime `wp/wp-content/`, or generated `dist/`
  files.
- Removing or rewriting browser extensions, Google Tag Manager, Cloudflare
  Insights, or other third-party services.
- A homepage redesign, content rewrite, or change to the approved brand assets.
- Broad removal of framework or WordPress CSS/JavaScript based solely on
  Lighthouse's small unused-code estimates.

---

## Addendum (2026-08-05) — SC-003 was reported met and was not

**Commit:** `24eb73fc`. Found by the CoreX v0.41.0 update branch, fixed here because it originated here.

`verify-a11y` reported **2 serious `role-img-alt` violations — home EN and AR** — against a baseline
of 0. This spec's own record claimed the opposite: *"clears unsized-image and invalid-ARIA findings"*
and *"Accessibility 100"*.

Both claims came from Lighthouse, which scores a sample of rules on one page. `verify-a11y` runs
axe-core over the full WCAG 2.0/2.1/2.2 A+AA tag set across 12 routes. **SC-003 was verified by the
weaker instrument**, and the stronger one — already in this repository, already part of the
project's definition of done — was not run before the claim was written down.

### What went wrong in the code

Commit `8ef527cc` ("the renderers serve WebP with intrinsic dimensions") also changed the carousel
track from `role="list"` to `role="group"`. That made the cards' `role="listitem"` invalid, which is
a real problem and was correctly noticed. The `<button>` and `<a>` card variants had `listitem`
**removed**, which was right and is why they were never implicated. The inert `<div>` variant was
given `role="img"` instead, and that was wrong twice over:

1. **It is nameless.** `cardTags()` emitted `role="img"` for both card types, but only
   `corporateCard()` passes an `aria-label`. `individualCard()` calls
   `cardTags($client, 'indiv-card')` with no extra attributes, so the individual card got a
   `role="img"` with nothing to announce. That is the violation axe reported.
2. **The name was the smaller half.** `role="img"` declares the element a single graphic, so the
   `<h3>` title, the subtitle and the body copy *inside* an individual card stop being reachable by
   assistive technology at all. Adding an `aria-label` would have satisfied axe while leaving the
   card's actual content hidden — a fix that verifies as a fix and leaves the user worse off.

An ARIA role was added to satisfy a rule about a *different* role, on an element whose content
needed no role at all.

### The fix

The inert individual card takes **no role**. Its content is real text; a plain `<div>` lets it
speak, which is how the lightbox and link variants have always behaved.

The corporate tile **keeps** `role="img"`, and the asymmetry is the substance rather than an
inconsistency: it is a bare `alt=""` logo, so it genuinely is one graphic, and it already carries
the client name as `aria-label`. `role="img"` therefore moved out of the shared inert branch in
`cardTags()` and became a caller's declaration (`?string $inertRole`). It is deliberately not folded
into `$extraAttributes`, because those also reach the `<button>` and `<a>` branches, where a role
would overwrite the interactive semantics that make them work.

### Verification, measured both ways

Two tests pin both halves — one asserts the individual card has no role and its `<h3>` survives, the
other asserts the corporate tile keeps a **named** `role="img"`. Perego Pest 599 → **601**.

Causality was measured rather than assumed. Same command, same 12 routes, only the renderer differing:

| `sites/perego/perego-site/src/Blocks/ClientsCarouselRenderer.php` | `verify-a11y` |
|---|---|
| at `HEAD~1` (pre-fix) | `hardFailures: 2` — `role-img-alt` (serious), home EN + AR |
| at `HEAD` (fixed) | `hardFailures: 0`, 12 pages |

Live markup confirms `<div class="indiv-card">` and
`<div class="corp-card" role="img" aria-label="…">`.

### Two things to carry forward

**Lighthouse is not the a11y gate; `verify-a11y` is.** A Lighthouse Accessibility 100 does not
license an SC-003-style claim. Run `node sites/perego/perego-site/scripts/verify-a11y.mjs` before
writing one down.

**Its evidence file is easy to read stale.** The script's docblock says it writes
`output/verify-a11y.json`, and that path is relative to the **current working directory** — run from
the repo root it writes `./output/verify-a11y.json`, not
`sites/perego/perego-site/output/verify-a11y.json`. Two older copies of that filename exist under
`perego-site/`, both months stale, and reading either one reports a clean run that never happened.
Check the mtime.
