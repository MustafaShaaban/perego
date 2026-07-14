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
- [x] T004 **Hero editable — done + proven.** The front page is a real per-language page (EN 42 / AR 97), so
    the hero is an editable projection of it: new `Content\HeroContent` registers three slide title/text pairs
    + CTA as `page` meta (`_perego_hero_s{1,2,3}_{title,text}`, `_perego_hero_cta`; REST + sanitize +
    `edit_pages` auth) and resolves them over the `HomeContent` seed PER FIELD. `HeroSliderRenderer` reads the
    queried front page's meta (locale-correct: EN 42 / AR 97). Front-page-scoped hero meta box added to
    `PostMetaBoxes`. Idempotent seeder `scripts/seed-hero-content.php` seeded both pages (14 writes; re-run 0).
    Carousel/autoplay/dots/reduced-motion (view.js) untouched. **Verified:** live EN (What We Believe / Ideas,
    In Motion / Built To Be Seen) + AR (بماذا نؤمن … قل مرحبًا!) byte-identical; edit round-trip — set page 42
    slide-1 title → homepage `<h1>` changed → restored. Pest 249/249.
- [x] T005 **EN/AR correctness** — verified live: `/ar/` renders Arabic hero + teaser from AR meta (no English
    leak); both locales editable via their own page/Service meta (EN 42/13–16, AR 97/25–28).
- [ ] T006 Visual no-regression: EN + AR homepage pixel-identical to the spec 011 baseline capture. (Structural
    parity confirmed via live HTML for hero + teaser; capture pending.)
- [x] T007 **Editor workflow proven** (real WordPress meta round-trips on the live site): editing a Service's
    teaser label and the front page's hero title both changed the homepage, then restored. Evidence in the
    T003/T004 notes above.
- [ ] T008 Full suite + guards; update durable memory; open PR.
