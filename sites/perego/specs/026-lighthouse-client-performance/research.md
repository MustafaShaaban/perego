# Research: Lighthouse Client Performance

## Decision 1: Classify findings by ownership before changing code

**Decision**: Fix only findings attributable to Perego-owned URLs or rendered
markup.

**Rationale**: The supplied report was collected from a browser profile with
Chrome extensions. Its unused JavaScript, deprecation, and back-forward cache
entries are dominated by MetaMask, Grammarly, and other extension URLs. The
Cloudflare beacon failure and Google tag behavior are third-party observations.
Changing Perego or CoreX code for those entries would not remove their cause.

**Alternatives considered**:

- Remove CoreX or WordPress assets based on the report: rejected because the
  measured potential savings are small and ownership/correctness is not proven.
- Treat the supplied Best Practices score as fully first-party: rejected because
  the audit evidence itself contains extension-origin failures.

## Decision 2: Consume the existing CoreX image contract

**Decision**: PHP renderers use `Corex\Assets\Image::picture()` with the
`perego-theme` asset base. Static FSE templates emit equivalent `<picture>`
markup because they cannot execute PHP.

**Rationale**: The existing CoreX helper checks for a `.webp` sibling, emits a
WebP source and original-format fallback, supports width/height, and defaults to
lazy/async rendering. This satisfies the framework's public contract without a
framework edit.

**Alternatives considered**:

- Add features to CoreX: rejected by the user's explicit boundary.
- Add a second client-wide PHP image abstraction: rejected as duplication.
- Convert all images to CSS backgrounds: rejected because meaningful images
  would lose appropriate semantics and intrinsic dimensions.

## Decision 3: Use deterministic, compression-aware build outputs

**Decision**: Preserve canonical sources, optimize PNG/JPEG fallbacks explicitly,
and generate WebP with a documented quality/effort policy. The build verifies
that generated fallback files do not become larger than their sources.

**Rationale**: The current bare `sharp(input).toFile(output)` path expanded the
audited PNGs substantially. Explicit format settings produce predictable,
reviewable output while WebP handles modern delivery.

**Alternatives considered**:

- Keep output-only images as manual artifacts: rejected because results cannot
  be reproduced reliably.
- Delete fallback formats: rejected because the rendering contract requires a
  valid fallback.
- Resize every source globally: rejected because high-density displays and
  future placements may legitimately use the current intrinsic dimensions.

## Decision 4: Preserve native interactive semantics

**Decision**: Make the carousel track a labelled group and remove `listitem`
overrides from buttons and links.

**Rationale**: `role="listitem"` is incompatible with native interactive
controls and caused the agent accessibility audit failure. A group preserves a
named collection without invalidating the actionable elements.

**Alternatives considered**:

- Wrap every control in a list item: rejected because it changes the carousel's
  direct-child DOM contract and would require unnecessary CSS/JavaScript work.
- Suppress the accessibility audit: rejected because the finding is real.

## Decision 5: Use finite caching for stable filenames

**Decision**: Add a client theme Apache cache policy with a finite lifetime for
images, fonts, CSS, and JavaScript, plus a client theme version bump.

**Rationale**: The audited assets currently have no effective browser cache
lifetime. A finite lifetime improves repeat visits without falsely marking
stable, non-hashed image filenames as immutable. Production hosts that do not
honor directory-level Apache rules need an equivalent CDN/host rule.

**Alternatives considered**:

- One-year immutable caching for every asset: rejected because image filenames
  are stable and un-hashed.
- Edit the WAMP virtual host or runtime root `.htaccess`: rejected because those
  are deployment/runtime configuration outside the client source boundary.
- Add PHP headers for static files: rejected because PHP does not serve those
  requests.

## Decision 6: Validate in a clean browser profile

**Decision**: Run Lighthouse and responsive browser checks in an
extension-free automation profile.

**Rationale**: This makes the result attributable to the site and avoids
re-reporting extension code as a website defect.

**Alternatives considered**:

- Reuse the user's normal Chrome profile: rejected for audit verification
  because installed extensions contaminate diagnostics.

## Evidence Baseline

- Supplied scores: Performance 97, Accessibility 100, Best Practices 73, SEO
  100.
- Image-delivery estimated savings: approximately 1,647 KiB.
- Total transferred bytes: approximately 3,583 KiB.
- First-party targets:
  - `hero-bg.png`
  - `wave-ribbon.png`
  - `wavy-corners.png`
  - default `logo-full.png` instances missing intrinsic dimensions
- First-party semantic target: corporate carousel buttons/links overriding
  their native roles with `listitem`.
