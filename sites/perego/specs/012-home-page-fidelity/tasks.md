# Spec 012 — Tasks

- [x] T001 **Homepage audit vs handoff (structure + editability).** All four handoff sections present with
    matching ids/classes (hero#hero, home-about#about, services-teaser#services, clients#clients). About =
    editable `post-content`; Clients = editable Client-CPT `WP_Query`. **Gap:** hero slides/CTA and
    services-teaser title/"See all"/4 cards are hardcoded in `HomeContent::COPY` (EN+AR) — visually correct
    but not WordPress-editable, and the teaser ignores the Service CPT that spec 010 gave real meta. Recorded
    in spec.md audit table.
- [x] T002 **Editable-seam decision recorded** (DECISIONS.md 2026-07-14). Teaser cards ← `perego_service`
    CPT via new teaser-presentation meta (`_perego_teaser_label`/`_perego_teaser_image_id`/`_perego_teaser_alt`,
    seeded from `HomeContent`+handoff assets, per-field seed fallback → zero visual regression); hero ← block
    attributes on the hero-slider block (seeded from `HomeContent`, needs wp-scripts build). `HomeContent::COPY`
    demoted to seed-only. Confirmed runtime: `peregos.local` does not resolve (000) — real host is
    `perego.local` (200); ngrok mirror reachable.
- [x] T003 **Services teaser ← Service CPT — done + editability proven.** Registered teaser-presentation meta
    on `perego_service` (`_perego_teaser_label`/`_perego_teaser_image_id`/`_perego_teaser_alt`; REST + sanitize
    + auth). `ServicesTeaserRenderer` now queries the published Services for the current Polylang locale
    (`lang` arg), indexes by `_perego_service_slug`, and overlays each card's label/image/alt from the meta,
    falling back PER FIELD to the `HomeContent` seed (card image stays the theme `card-*.png` asset unless an
    owner sets an attachment id). Added the 3 fields to `Admin/PostMetaBoxes` (editor UI). Idempotent seeder
    `scripts/seed-service-teaser-meta.php` (dry-run gate, DB backup `db-backup-20260714-220905.sql`) seeded
    label+alt onto all 8 posts (16 writes; re-run = 0). **Verified:** live EN + AR teaser byte-identical
    (Video Editing/2D Motion Graphics/… and مونتاج الفيديو/…); real editor round-trip — set post 13 label to
    "Video Craft" → homepage card showed "Video Craft" → restored. Pest 245/245.
- [ ] T004 **Hero editable.** Make slides (heading/body/CTA) + CTA editable through WordPress (block attrs or
    an options/site-content seam), seeded from the current handoff copy; carousel/autoplay/pause/dots/reduced
    -motion behaviour unchanged. EN/AR.
- [ ] T005 EN/AR correctness: no English on `/ar/`; both locales editable and rendering the right copy.
- [ ] T006 Visual no-regression: EN + AR homepage pixel-identical to the spec 011 baseline capture.
- [ ] T007 Prove the editor workflow: edit hero + a Service in real WP → front end changes. Evidence.
- [ ] T008 Full suite + guards; update durable memory; open PR.
