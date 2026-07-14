# Spec 012 — Tasks

- [x] T001 **Homepage audit vs handoff (structure + editability).** All four handoff sections present with
    matching ids/classes (hero#hero, home-about#about, services-teaser#services, clients#clients). About =
    editable `post-content`; Clients = editable Client-CPT `WP_Query`. **Gap:** hero slides/CTA and
    services-teaser title/"See all"/4 cards are hardcoded in `HomeContent::COPY` (EN+AR) — visually correct
    but not WordPress-editable, and the teaser ignores the Service CPT that spec 010 gave real meta. Recorded
    in spec.md audit table.
- [ ] T002 Decide + document the editable seam per field (block attribute vs options vs CPT) in DECISIONS.md;
    keep `HomeContent::COPY` as the seed/default only. Mirror spec 009's block-first, seeded-then-editable
    pattern.
- [ ] T003 **Services teaser ← Service CPT.** Source the four cards from published Services (title, slug,
    image) so editing a Service updates the teaser; teaser title + "See all" editable; preserve exact markup
    and order; fall back to the seed when no Services exist. EN/AR via Polylang.
- [ ] T004 **Hero editable.** Make slides (heading/body/CTA) + CTA editable through WordPress (block attrs or
    an options/site-content seam), seeded from the current handoff copy; carousel/autoplay/pause/dots/reduced
    -motion behaviour unchanged. EN/AR.
- [ ] T005 EN/AR correctness: no English on `/ar/`; both locales editable and rendering the right copy.
- [ ] T006 Visual no-regression: EN + AR homepage pixel-identical to the spec 011 baseline capture.
- [ ] T007 Prove the editor workflow: edit hero + a Service in real WP → front end changes. Evidence.
- [ ] T008 Full suite + guards; update durable memory; open PR.
