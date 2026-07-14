# Perego — Progress

> Live status. First action each session: read this, then continue from **Next**.

## RESUME HERE

- **Date/time:** 2026-07-14 (~14:30 UTC)
- **Branch:** `feature/011-global-shell-preloader` (stacked on 010←009←008). **PRs open:** #17 (spec 009, base 008), #18 (spec 010, base 009).
- **Latest pushed commit:** `203af28` (spec 011 done docs). **Spec 011 DONE → PR #19**. Now on
  `feature/012-home-page-fidelity` (branched off 011); spec 012 audit committed, implementation pending.
- **Active spec:** **012** (homepage fidelity + editability) — **DONE (T001–T008), PR pending push confirm.**
  The homepage was visually complete; spec 012 closed the **editability** gap. About + Clients already edited
  (post-content / Client CPT). Now **hero + services teaser are genuinely editable too**, with zero visual
  regression (EN + AR):
  - **Services teaser ← Service CPT:** teaser meta (`_perego_teaser_label`/`_image_id`/`_alt`) on
    `perego_service`, overlaid per field on the `HomeContent` seed; editor UI in `PostMetaBoxes`; seeder
    `scripts/seed-service-teaser-meta.php`.
  - **Hero ← front-page page meta:** `Content\HeroContent` registers 3 slide title/text pairs + CTA on the
    `page` type; `HeroSliderRenderer` reads the queried front page (EN 42 / AR 97) with per-field seed
    fallback; front-page-scoped hero meta box; seeder `scripts/seed-hero-content.php`.
  - **Proven** via live edit round-trips (teaser label + hero title changed the homepage, then restored) and a
    pixel-identical full-page capture (`output/playwright/012-en-homepage-noregression.png`). Pest 249/249.
    Both seeders idempotent; DB backup `db-backup-20260714-220905.sql` (gitignored). Guards pass.
- **Active spec:** **013** (Services archive + four service singles) — **DONE (T001–T008), PR pending push
  confirm.** Service pages were visually complete; spec 013 closed editability: the service-single **H1 ← the
  Service post's own title** (native editing), and the single + archive **hero tabs ← each Service's editable
  `_perego_teaser_label`** (spec-012 meta), via a new shared `Content\ServiceCatalog` with per-field
  `ServiceContent` seed fallback → byte-identical EN/AR. Selected work (archive + single) already ← real
  Projects. Fixed a latent bug: the archive tab CTA passed `?service=<name>` (contact chooser needs the slug)
  → now `?service=<slug>`. whatwedo/process editable via `post-content`. Proven via a live title edit
  round-trip; Pest 251/251; guards pass. Evidence `output/playwright/013-service-single-hero.png`.
- **Active spec:** **014** (Work archive + project single) — **DONE (T001–T006), PR pending push confirm.**
  The project pages were already an editable CPT projection (H1 ← post title, client/year/role/deliverables ←
  spec-010 meta boxes, body ← post-content, work grid + prev/next/related ← Project CPT). Spec 014 added the
  one deferred gap — the **project gallery wp.media picker** (`Admin\ProjectGalleryMetaBox`: native modal,
  thumbnail strip, nonce/cap-guarded save → clean int list; 5/5 save tests + server-side render verified) —
  and verified work-archive + project-single fidelity EN/AR (captures in `output/playwright/014-*`). Pest
  256/256; guards pass.
- **Active spec:** **015** (Journal, single article, search) — **DONE (T001–T006), PR pending push confirm.**
  Verification spec: the journal archive (Query Loop `blog-grid`/`post-card`), single article
  (`post-hero`/`post-title`/`post-meta`/`post-featured`/`post-content`), and search (`search-bar`/results/
  no-results) are **native WP posts + core search via FSE blocks** — already handoff-faithful (captures in
  `output/playwright/015-*`) and **natively editable** (3 EN + 3 AR posts). Search is per-language (Polylang),
  no-results state matches the handoff. **No code change needed** → Pest unchanged 256/256.
- **Next spec:** **016** (Contact, forms, form states, email routing) — branch `feature/016-contact-forms-email`
  off 015. This is the big one: three forms (contact/brief, quick-message, careers/join) editable + routed +
  validated + stored via CoreX Forms/Submissions; Email Studio routing; form states (default/invalid/
  submitting/success/server-error); real submissions. Depends on spec 010 (CoreX runtime). Verify Forms &
  Flows/Submissions/Data Models visible + functional.
- **Then 017–018** (supporting pages/terms/privacy/generic/404; final acceptance + cleanup: remove remaining
  duplicate CSS/dead code/obsolete seeds/orphan DB data).
- **Continue after 012:** specs 013–018 in order (services pages, work/project, journal/search, contact/forms
  /email, supporting pages, final acceptance).
- **Active spec:** `011` (global shell + preloader) — **T001–T011 DONE (PR #19).** T002 header/nav/dropdown matched to
  handoff (About→`/#about`, Services→`/#services`, short dropdown labels, anchor `isActive` fix); T003 sticky
  state byte-identical to handoff; T004 mobile menu (spec-008 verified); T005 lang switcher verified live
  (AR RTL, Arabic labels, real switch URL); T006 standard footer confirmed; **T007 contact flat footer bug
  fixed** (was keeping quick-message + dropping careers — inverted; now contact+careers, no quick-message);
  T008 hard-coded `/about` removed; T009 FSE `templateParts` declared (header/footer editable in Site
  Editor); **i18n regression fixed** (short dropdown source strings orphaned AR — re-added msgids, recompiled
  `.mo`, verified AR dropdown Arabic again); T010 EN/AR visual acceptance captured (4 Playwright shots in
  gitignored `output/playwright/`: EN header, EN 3-col footer, AR RTL header, contact 2-col flat footer).
  **Next: T011** = open spec 011 PR (base = spec 010 branch). **009 + 010 DONE (PRs #17/#18).**
- **Documented follow-up (not blocking):** primary-nav hrefs use `home_url()`, so on AR pages every nav link
  points at the EN base — systemic Polylang nav-localization gap (needs `pll_home_url()` + translated
  permalinks for all items); out of shell-fidelity scope, best as a focused i18n-routing task. The language
  *switcher* itself works. Logged in spec 011 tasks T010.
- **[historical] spec 010 summary:** largely complete.
  - **Done:** T001–T004 (audit; ACF not a dep; forms inventory), T006 (submissions pipeline works — id 150), **T009** (register_post_meta for Project/Service/Client + `PostMetaBoxes` editor UI with guarded save; Pest 243/243).
  - **Fixed release-blocker:** CoreX admin React bundle `corex-config/build/admin/index.js` was 404 (unbuilt) → built locally (gitignored). **Deploy must build CoreX admin assets** (GAP-2).
  - **Blocked:** T007/T008 — framework **GAP-1** (no public CPT-backed DataModels seam). See `docs/corex-framework-gaps.md`.
  - **Remaining unblocked:** T005 (interactive Forms&Flows screen check), T009 gallery wp.media UI, T010 (live browser proof of end-to-end record creation), then open spec 010 PR.
- **Next spec after 010:** **011** (global shell + preloader) — also owns the footer/header/CTA/404 Site-Editor canvas rebuild (the 009 editability seam).
- **Verification run:** Pest 243/243, Jest 76/76; home/work/services 200; all 3 CPT meta registered (show_in_rest) + meta boxes registered; migration idempotent; DB backup `db-backup-20260714-134449.sql` (rollback).
- **Live URL status:** canonical `http://perego.local/` (local WAMP, siteurl=perego.local, same DB as wp-cli). ngrok mirror 200 (tunnel; proven same install). Prefer perego.local; never stop if ngrok offline; do not change WP home/siteurl.
- **DB migration status (spec 009):** APPLIED — 14 `perego_section` records removed, 0 orphans, footer editorial intact via `footer-careers` block. Idempotent.
- **Autonomous mode:** continue specs 009–018 in dependency order; stop only for a genuine external blocker (finish other unblocked work + update durable memory + commit/push first).
- **Genuine external blockers surfaced:** GAP-1 (CoreX Data Models CPT seam — framework team) and GAP-2 (deploy must build CoreX admin assets) — both need owner/framework action; recorded in `docs/corex-framework-gaps.md`.

---


## Session summary (2026-07-14) — T003–T007 substantially closed; full 8-viewport × EN/AR matrix run; T008 Contact bug found and fixed

Continuing the visual-fidelity recovery per `PEREGO_FINAL_COMPLETION_PROMPT.md`. All work this
session verified and pushed to `feature/008-visual-fidelity-recovery`. Full suite green:
**Pest 242/242, Jest 76/76, interactions 12/12, a11y 12/12** (a11y started the session at 14
serious/critical violations). Ran the full `capture-visual-recovery.mjs` matrix for the first time
this session — 20 routes/states × 8 required widths × EN/AR = 362 captures, **0 with horizontal
overflow**; the only 16 "unavailable" records are the 404/sample-page routes in AR, which cannot
publish an hreflang alternate by design (not a defect).

**Closed**: T003 (interference audit), T005 (forms/footer states). T004/T006/T007 have every
sub-item done (see `tasks.md`) but keep their top-level box open pending a full manual review pass
over all 362 captured screenshots, per the file's own acceptance rule.

**Real bugs found and fixed this session** (not cosmetic), most recent first:
- **AR Contact page was completely unstyled at every width** — only discovered once the full
  8-viewport matrix was actually captured and reviewed (earlier spot-checks at 1440/375 alone missed
  it). Root cause: `page-contact.html` depended on implicit `page-{slug}.html` template-hierarchy
  matching instead of an explicit `_wp_page_template` assignment; Polylang's translated-page slug
  (`contact` → `contact-2`) never matches that filename, so AR silently fell back to the generic
  `page.html` template. Fixed by registering `page-contact` in `theme.json`'s `customTemplates` and
  explicitly assigning it on both language pages (Decision 21). Also fixed a second bug it was
  hiding: 3 of 4 service-chooser labels were untranslated (hardcoded English + no matching `.po`
  entry) — now sourced from `HomeContent::services()`'s existing locale-aware short names.
- Two pre-existing a11y regressions (ARIA role mismatch, contrast failures) + an RTL logical-CSS gap.
- A CoreX **framework** bug causing native browser validation bubbles on every CoreX form (worked
  around client-side; logged as Decision 19 for a CoreX Framework Mode task).
- A service-chooser UI desync after `form.reset()`.
- Services archive's "Our Process" was a plain unstyled `<ol>` — rebuilt as the handoff's icon/card/
  arrow design; same fix migrated to the four per-service-single process sections.
- Native white WordPress comment-form fields.
- **Six blocks' `block.json` were missing the `"style"` key entirely**, silently orphaning their
  compiled CSS on every route — this is why the homepage hero's Previous/Next/Pause controls were
  completely unclickable, not just "unproven."
- **Both site lightboxes were completely invisible** despite opening correctly in the DOM — the
  already-shipped `project-gallery-lightbox`'s "9/9 passing" interaction checks had only ever
  asserted the `hidden` DOM attribute, never actual computed visibility. Fixed with one scoped CSS
  rule; strengthened `verify-interactions.mjs` to assert computed style directly.
- Built the previously-nonexistent shared `perego-theme/media-lightbox` and used it for the Services
  archive's and every service single's "Selected work" sections, and for individual client-card
  video playback.
- Work archive's missing closing CTA section; Journal/single-post reading-time + meta-dot separators
  bug (broke inside Query Loop); search result-card placeholder had zero CSS.

**Full detail, root causes, and evidence**: `docs/visual-recovery.md`, `DECISIONS.md` (Decisions
19–21), and `specs/008-visual-fidelity-recovery/tasks.md`.

**Honestly still open** (not fabricated as done): a full manual review pass over all 362 captured
screenshots (automated capture found 0 overflow, but manual visual review has only sampled a subset);
T009's WP block-editor "no recovery warning" check is blocked on a genuine credential issue (documented
dev password no longer works — flagged for owner input); T010 (performance/security/SEO gates, final
regression pass, merge) not started. See `specs/008-visual-fidelity-recovery/tasks.md` for the exact
task-by-task state.

## Authoritative visual-recovery status (2026-07-13)

**The earlier visual-completion claims are invalidated.** Route-health, accessibility, interaction, unit,
and resource checks prove functional quality only; they do **not** prove visual parity with the locked final
handoff. The current frontend is a generic WordPress approximation with an incompatible CSS/DOM contract.
Spec 008 is rebuilding the Perego client rendering layer from the authoritative handoff stylesheet and
markup. No route may be called design-complete until deterministic baseline, actual, diff, manual review,
responsive EN/AR, and state evidence exists.

Latest recovery increment (2026-07-13): the 404 route now restores the locked handoff's isolated DOM,
inline CSS, and decorative pointer interaction through the Perego FSE block boundary. The English 1440
default runner reports 0 changed pixels; this is evidence only, not acceptance. Arabic, responsive,
interaction-state, and manual visual review remain required by Spec 008 T001.

Latest route-contract increment (2026-07-13): Work and project now use the locked archive/single
landmark, hero, filter, grid, card, category, and featured-media classes through their existing
server-rendered client blocks. English desktop evidence reduced the Work diff from roughly 989,618 to
233,288 pixels and the project diff from roughly 616,244 to 479,258. Neither route is accepted; gallery,
navigation, pagination, media mapping, AR, responsive, state, and manual-review evidence remain open.

Latest Journal increment (2026-07-13): the journal archive and single-post templates now use the locked
archive/article container, post hero, featured-media, prose, grid, and pagination classes while preserving
the dynamic Query Loop and native post content. English desktop evidence reduced Journal from roughly
507,037 to 461,995 pixels and the representative post from roughly 915,120 to 529,609. Neither route is
accepted; card data, tags, comments, related posts, AR, responsive, state, and manual-review evidence remain open.

Latest Contact increment (2026-07-13): narrowed two over-broad FSE layout resets that were overriding the
locked inner-page hero overlap, then adapted the live CoreX project-brief presentation without changing its
submission schema, nonce, validation, honeypot, or REST endpoint. The contact form now preserves the locked
budget prompt, optional labels, select treatment, live character count, concise chooser labels, and hidden
honeypot/error placeholders. English 1440 default evidence reduced the Contact diff from roughly 1,245,667
to 353,189 pixels. It remains unaccepted: remaining typography/content deltas, Arabic, responsive
widths, interaction/form states, and manual review are still required.

Latest evidence-tooling increment (2026-07-13): the deterministic recovery runner now records each live
capture's viewport/scroll width and horizontal-overflow result, preserves unrelated manifest records during
a scoped route retest, and supports `PEREGO_CAPTURE_ROUTES`. The Contact EN/AR default sweep across all
eight required widths reports no live horizontal overflow. This is diagnostic evidence only; it does not
close T001 or accept Contact without full route/state coverage and manual review.

Latest route-contract increment (2026-07-13): Search now keeps its real WordPress query while emitting the
locked `page-section`/`post-hero__inner`/`search-bar`/`blog-grid`/`post-card`/pagination hierarchy; legal
and standard-page templates now emit the locked section, container, hero, TOC, and prose hierarchy without
moving editor-canvas content into PHP. The Search baseline now uses the handoff's `motion` query rather than
an unrelated live query. The legal TOC now matches the handoff rather than adding a separate public review
notice. EN 1440 diagnostics report 255,615 changed pixels for Search, 191,878 Terms, 185,187 Privacy, and
111,709 standard page, all without horizontal overflow. None is accepted: AR, responsive,
state, and manual-review evidence remain open.

Latest header interaction increment (2026-07-13): the mobile menu's backdrop was intercepting Services
taps because the sticky header's stacking context placed its fixed panel beneath the sibling backdrop; the
generic close-on-link listener also immediately closed the Services accordion. A scoped open-menu stacking
repair and trigger exclusion now preserve the locked handoff structure. The live interaction verifier uses
the handoff selectors and passes six checks: sticky scroll, desktop dropdown hover, mobile panel, mobile
Services accordion, Escape/focus restoration, and the real AR URL. This is functional evidence only; state
screenshots/diffs, RTL, and all responsive widths remain unaccepted.

Latest state-evidence increment (2026-07-13): the visual runner now captures the handoff header dropdown,
mobile panel, and mobile Services accordion as explicit states at their applicable widths. Representative
EN baseline/current/diff artifacts now exist for dropdown at 1280px and both mobile states at 375px; all
three current renders have no horizontal overflow. They remain unreviewed diagnostics: the full EN/AR
width matrix, manual review, and the remaining required interaction/form states are still open.

Latest project-lightbox increment (2026-07-13): the representative project's three approved, seeded gallery
attachments are present and the live gallery now has verified modal open/scroll lock plus Escape/focus
restoration. Its renderer and the nine-check interaction suite pass. The project gallery remains visually
unaccepted despite its thumbnail DOM now using the locked `portfolio`/`work-masonry`/`work-card` handoff
contract. Adjacent links and related projects now also use the locked pagination and separate related-section
surfaces inside the main landmark. Its EN 1440 gallery diagnostic fell from 814,983 to 276,937 changed pixels with no horizontal
overflow; AR, responsive, state, and manual review remain open.

**Preservation-phase verification (2026-07-14):** before committing the above uncommitted work, ran the
full suite and manually reviewed the diff: Pest 223/223, Jest 68/68, both builds clean, route-health 72/0,
interactions 9/9, and manual comparison of the Search and Project-gallery captures against the locked
handoff confirmed the DOM/class contract genuinely matches (residual pixel diff is header-chrome and
font-rendering noise, not a structural defect). **The a11y gate initially failed with 14 serious/critical
violations across 12 pages** — verified pre-existing at HEAD (`134f685`), not introduced by the uncommitted
diff, by re-running the audit against a stashed-clean checkout. Two real, distinct defects, both fixed:
(1) `ClientsCarouselRenderer`'s corporate track used `role="group"` while its cards carried `role="listitem"`
(an ARIA `listitem` requires a `list`/`listitem`-supporting parent role — the individual track already used
`role="list"` correctly); changed the corporate track to `role="list"` to match. (2) Two reference-CSS
contrast failures: `.lang-toggle__btn.is-active` sets white text on the `--accent` pill background (2.9:1;
every other accent-background control in the reference already pairs it with a dark ink) and
`.page-crumb span` dims to 50% opacity indiscriminately, which the current-page `aria-current="page"` label
inherits too (down to ~2.3:1 on `--bg-deep`) even though only the `/` separators were meant to be dimmed.
Fixed both with two small, scoped rules in `perego-wordpress-adapter.scss` (the adapter layer, not the
untouched reference stylesheet). Also fixed a real RTL/logical-CSS gap found in the same file's new
`.contact-form-wrap select` rule: the dropdown-arrow `background-position: right 4px center` and physical
`padding` had no `[dir="rtl"]` mirror, so the arrow would sit on the wrong side in Arabic; added the mirror
and switched the padding to `padding-block`/`padding-inline`. **Full suite after fixes: Pest 223, Jest 68,
route-health 72/0, interactions 9/9, a11y 12/0 (0 serious/critical) — all green.** One pre-existing, in-scope
gap noted but not fixed here (out of this diff's touched files): `SearchResultsRenderer::resultCard()`'s
`post-card__media-placeholder` has no backing CSS rule in the reference stylesheet, so search results for
posts without a featured image (e.g. service posts) render a blank media box instead of the handoff's
gradient placeholder — tracked for T008's Search completion pass, not fixed in this preservation commit.

The existing non-engineering owner material remains required for launch: approved client identities/logos,
production project and journal content/media, counsel-reviewed English legal copy, and professional Arabic
legal translation. The idempotent seed workflow is documented and proven ready; see
`LAUNCH-CHECKLIST.md` and `specs/004-design-fidelity/content-manifest.md`.

### Post-closure regression fix: local handoff fonts (Spec 006)

The current 72-route browser gate surfaced one shared broken resource on every non-404 route: the remote
Google Fonts request. Spec 006 replaced it with local Open Sans (300/400/600/700) and Cairo
(400/600/700) WOFF2 assets in `perego-theme/assets/fonts/`, preserving the handoff font families while
removing both remote preconnect hints. Current evidence: Pest 218/615 assertions, Jest 67, route-health
72/0, a11y 12/0 serious-critical, and interactions 4/4. This closes the last executable regression.
The local-font fix is committed as `0db8cab` and proposed as Perego PR #13.

## Integration status (2026-07-13)

Specs 004 (design fidelity) and 005 (UI-string i18n) are **merged and integrated**:
- PR #11 (`feature/005-i18n-strings` → `feature/004-design-fidelity`) merged — `7ba9f9a`.
- PR #10 (`feature/004-design-fidelity` → `feature/002-home`) merged — `00ebec2`.
- Integrated `feature/002-home` re-verified green after the merges: Pest **218**, Jest 67,
  route-health 72/0, a11y 12/0, interactions 4/0, debug.log clean; `make-pot` warning-free.
- Post-merge hardening on `feature/002-home` (all in PR #12): hero-slider translators comment
  (i18n lint-clean); **seed-script idempotency proven** (static guard-audit + zero-growth re-run of
  14 seeds — `evidence/seed-idempotency.md`); **`PlaceholderPageIndexing`** keeps WP's default
  Sample Page `noindex` + out of the sitemap, closing LAUNCH-CHECKLIST §4 (DECISIONS #18).
- **PR #12** (`feature/002-home` → `feature/001-global-foundation`, the origin default/trunk) is
  **open, CLEAN/MERGEABLE**, zero divergence (002 strictly ahead of 001). It awaits the owner's
  merge: the two-party-review control blocks the agent from self-merging a PR it authored.

Remaining is now exclusively non-engineering: the owner's merge of PR #12 (self-approval control blocks
the agent), T020 real content (idempotent seeds proven ready), and professional Arabic legal translation
(deliberately not fabricated). Every executable engineering item in `LAUNCH-CHECKLIST.md` is closed.

## Authoritative recovery status (2026-07-12)

**This section supersedes every older “Latest”, “Next”, and “superseded” block below.** The older
chronological notes are retained as evidence only and must not be used to decide what to build next.

The current delivery branch is `feature/004-design-fidelity`, based on `feature/002-home` at `579565a`.
The final, binding visual reference is `_design_handoff/Perego-Creative-Studio-Final-Handoff/site/`.
No visual reinterpretation is authorized: every correction must make Perego match that handoff.

### What is implemented

- FSE shell, home, services, work/projects, clients, journal, legal, search, 404, contact, EN/AR routing,
  major forms, SEO, and baseline accessibility/route-health checks are implemented in the client source.
- The latest recorded route-health run covers **72** EN/AR desktop/mobile checks across all 16 mapped
  handoff templates with no hard failures (spec 004 T007 extended the earlier 44-check pass). One
  informational content gap: the representative standard page `/sample-page/` has no Arabic translation.
- Single-post fidelity closure (2026-07-13): added the locale-aware `perego-theme/post-reading-time`
  block ("N min read" / "N دقيقة للقراءة"), the last handoff meta element that was still deferred. Gates
  after the change: Pest **212**, Jest **67**, route-health **72/0**, a11y **12/0**, interactions **4/0**,
  debug.log clean; live EN + AR single posts verified. See DECISIONS #17.

### What is not accepted or launch-ready

- Pixel-level, state-by-state visual fidelity is design-complete for Home only; the other 15 handoff
  templates have not yet been through the same evidence gate (Phase 4, not started).
- Placeholder/demo media and content remain in public-facing surfaces (Home's client carousel; other
  routes as their turn comes); legal content remains draft/review-only.
- Gallery/lightbox completion for project singles, editor-canvas remediation for portfolio prose, manual
  keyboard coverage, and production-like mail/HTTPS verification remain outstanding.
- Specs 001-003 contain stale task state and do not represent the later delivery history accurately.

### Next

1. ~~Execute T004-T008 in `specs/004-design-fidelity/tasks.md`: build the route matrix and deterministic comparison process.~~
   **Done (2026-07-12).** T004/T005 (route-matrix + quickstart), T006 (env/build verification recorded in
   quickstart), T007 (verify-visual extended to all 16 templates — 72 checks/0 failures), T008
   (visual-difference review procedure in `docs/visual-acceptance.md`) all complete. Evidence gate is ready.
2. ~~Phase 3 (US1) — finalize Home as the first complete visual slice.~~ **Done (2026-07-12), T009-T013
   all closed.** All 13 logged visual differences resolved (hero/about/service-card images, clients
   background + card layout, footer contact/social, header logo/CTA/language-toggle, site-wide font
   loading, AR-clients query bug — full detail in `specs/004-design-fidelity/evidence/home.md`), the
   About Us/Our mission prose migrated to real editor-canvas content on the front page (T012 —
   `perego-theme/home-about-bg` block + `wp:post-content`, seeded via `scripts/seed-home-about.php`,
   verified against Polylang's own static-front-page translation source before building on it), and
   a11y/interaction/route-health all re-verified green after two regressions this session's own changes
   introduced and its own gates caught (a mobile CTA-overflow specificity bug, a color-contrast failure).
   Final state: **72-check route-health + 12-page a11y (0 violations) + 4/4 interaction + 186 Pest + 67
   Jest, all green.** Home is design-complete; **blocked on owner material** for launch only by its
   placeholder client-carousel content (Sample Corporate/Individual Client names — FR-006).

3. **Phase 4 (US2) — content-route slices. T014, T015, T016 DONE (2026-07-12).**
   - **T014 services** (`fc52992`): hero image, whatwedo split+media, site-wide `.btn`→`.perego-btn` bug.
   - **T015 work** (`687d4a9`, `c764444`): archive breadcrumb/demo-note, AR-filter canonical-slug bug,
     project-single hero image + background-gap; gallery/lightbox + related-projects deferred.
   - **T016 journal/single-post/search/404/contact/legal/page** (`58d5998`, `30d3bf4`, `058d4ce`):
     journal + single-post (unstyled cards + empty author byline `post_author=0`), contact (the CoreX
     Forms brief **and** the site-wide footer form were completely unstyled — block-only stylesheet
     never loads for server-rendered forms; ported onto `.corex-form__*` + 2-col grid + honeypot/submit
     fixes), legal (a **site-wide** WP 7.0.1 quirk dropped the h1/h2/h3 font-size presets → 16px headings
     everywhere; resilient `:root` re-declaration; + white-gap), search (plain list → `.post-card` grid +
     breadcrumb + pagination), 404 + page verified. Evidence: `evidence/{journal,single-post,contact,
     legal,search-404-page}.md`. **All gates green: 72 route-health / 12-page a11y / 4 interactions / 195 Pest.**

   **➡ RESUME HERE.** Remaining Phase 4: **T017** (migrate portfolio prose + any provider prose to
   editor-canvas content) and **T018** (per-route EN/AR acceptance recording — largely captured in the
   `evidence/*.md` + `route-matrix.md` already). Then Phase 5 (T019-T021 launch content) and Phase 6
   (T022-T025 release evidence, guards, specs 001-003 reconcile, PR).

   **⚠ TWO CROSS-CUTTING ITEMS SURFACED IN T016 — need owner attention:**
   - **(launch blocker) UI-string i18n is unwired.** On every AR route the header nav, form field
     labels, and button text render in **English** — no `.po`/`.mo` and no `pll_register_string` for the
     `perego-site`/theme domains, and the nav labels in `SiteHeaderRenderer` are hardcoded English (not
     even `__()`-wrapped). Pre-existing (present on the accepted AR home). The handoff is English-only so
     it's not a deviation *from the handoff*, but it blocks a real bilingual launch. Needs its own i18n
     slice (wrap strings + author translations, or Polylang string translations) — not a per-route fix.
   - **(content) AR legal section bodies + `sample-page` demo copy** are unseeded/WP-default placeholder;
     Phase-5 launch-content cleanup.

4. **Phase 4 complete; Phase 5 docs done; Phase 6 verification done (2026-07-12).**
   - **T017** (portfolio/provider prose → canvas): verified satisfied by the body of work — case
     studies, service narratives, and home About are all `wp:post-content`; residual provider strings
     are archive chrome + a deliberate demo marker (see `tasks.md`).
   - **T018** (per-route acceptance recording): done — every route-matrix row carries status + evidence.
   - **T019 + T021** (content manifest + launch-blocker audit): done — [specs/004-design-fidelity/content-manifest.md](specs/004-design-fidelity/content-manifest.md).
   - **T020** (replace with owner content): **BLOCKED on owner material** (real clients/photography/legal
     copy/articles); the idempotent seed workflow is proven and ready.
   - **T022** (full suite): **Pest 195 · Jest 67 · route-health 72/0 · a11y 12/0 (0 serious-critical) ·
     interactions 4/4** — all green.
   - **T023** (guards): new/changed PHP self-checked against wp-guard (escaping, i18n, no raw request
     output, ABSPATH, no SQL) and clean-code-guard (reused `.post-card`/`.perego-btn`, removed dead CSS).

   **T024 — spec 001–003 reconciliation (final status).** The "open" boxes in the older specs are stale;
   spec 004's delivery closed them:
   - **002-home**: 15/15 — complete.
   - **003-services-portfolio T015–T017** (service CPT, service single, services archive): **delivered** —
     services routes are design-complete (evidence/services.md); `ServicePostType` + templates ship.
   - **003 T013** (project single template): **delivered** (`single-perego_project.html` = project-hero +
     `wp:post-content`); **T012/T014** (gallery-lightbox block + gallery meta): **deliberately deferred** —
     `ProjectGalleryLightboxRenderer` exists in source but unregistered (evidence/work.md).
   - **001-global-foundation T029/T036** (Jest): **delivered** — 67 Jest tests pass (site-header/
     language-toggle/preloader/hero/carousel/gallery/portfolio). **T040** (live header/footer/preloader
     comparison): **delivered** via evidence/home.md. **T041** ("branch not finished"): **superseded** by
     the 004 branch, which is the active delivery source of truth.

   **Remaining before launch**: (a) ~~the UI-string i18n slice~~ **DONE — spec 005**, (b) T020 owner
   content. Spec 004 Phases 1–4 + the Phase-5 docs + Phase-6 verification shipped as PR #10.

5. **Spec 005 — UI-string i18n (EN/AR gettext). DONE (2026-07-13), branch `feature/005-i18n-strings`.**
   Closed the top launch blocker with the constitution-aligned approach (gettext `.po`/`.mo`, no Polylang
   dependency): loaded the `perego-site` textdomain, `__()`-wrapped the hardcoded header nav labels
   (const → `navItems()` method), authored `perego-site-ar.mo` for every public UI string (nav/forms/
   buttons/aria/slider), and localized the framework (`corex`) form submit/status strings via a client
   `gettext_corex` filter (`I18n/FrameworkFormStrings`, unit-tested). **AR routes now render Arabic UI
   chrome; EN unchanged. Pest 200 · Jest 67 · route-health 72/0 · a11y 12/0 · interactions 4/4.** See
   `specs/005-i18n-strings/`. **Only launch item left: T020 owner content** (real clients/photography/
   legal copy/articles) — the idempotent seed workflow is ready.

## Latest (2026-07-12) — M6 clients + Phase 7 forms (partial)

Shipped, each tested + guarded + pushed to `origin/feature/002-home`:

- **M6 clients carousel** (`7f1c441`): `perego_client` CPT + `perego_client_type` taxonomy;
  server-rendered `perego-theme/clients-carousel` block (Corporate + Individual, EN/AR, cards
  reachable without JS, modular Swiper enhancement); idempotent demo seed (8 marked-demo clients).
  Tests: ClientsContent/ClientPostType/ClientsCarouselRenderer (Pest) + Swiper view (Jest).
- **Phase 7 form 1 — footer quick-message** (`4d9f281`): `PeregoSite\Forms\QuickMessageForm`
  registered into the shared CoreX Forms registry via `\Corex\Boot::app()->container()` (client
  composition, not a framework edit); embedded in the footer's quick-message column through the
  registered `corex/form` block. Live-verified: nonce + honeypot + fields + aria-live status.
- **Phase 7 form 2 — Start-a-Project brief + /contact page** (`e56f438`, `d980a68`):
  `ProjectBriefForm` (handoff fields verbatim + limits: name/email/phone/company/budget/subject/
  message + a service chooser queried from the service CPT, keyed by canonical slug, honouring
  `?service=` with a spoof-proof whitelist). Editor-managed `/contact` page (EN + AR linked) hosts
  it; the service-hero CTA now passes the canonical slug. Live-verified end-to-end preselection.

Suites: **136 Pest + 58 Jest green**. `corex-email`, `corex-captcha`, `corex-careers` are NOT
active (only core + forms/ui/kit/media).

### Decision needed before the rest of Phase 7 (join-us CV + branded emails)

- **Join-us / CV form** needs a file-upload field. `corex-forms` has no `file` field type (adding
  one is CoreX **Framework Mode** work, out of Client Site Mode). `corex-careers` *does* ship a
  secure `UploadValidator` + `ApplicationService`, but is job-centric and inactive. Path choice +
  activating `corex-careers` (plugin-footprint change) is an owner decision.
- **Six branded EN/AR emails** need `corex-email` activated + wired to the `RoutedMailer`/
  `MailTemplateCatalog` seam (until then the engine uses the `wp_mail` fallback to admin).



## Environment bootstrap (2026-07-11)

- **This repo IS a CoreX framework checkout** (`origin` = `MustafaShaaban/perego`, `upstream` =
  `MustafaShaaban/corex`, merged at framework `v0.33.0` via `git merge upstream/main
  --allow-unrelated-histories`). The client site lives at `sites/perego/` (this directory), matching
  CoreX's own documented `sites/<client>/` convention — there is no separate framework checkout
  elsewhere on disk for this project.
- **WordPress install** at `C:\wamp64\www\perego\wp` (gitignored dev runtime — never committed):
  WordPress 7.0.1, DB `perego` (prefix `perego_wp_`), MySQL `root` / no password / `localhost`.
  Vhost `http://perego.local`, admin `admin` / `changeme` (dev-only — **change this password**).
  Set up via the framework's own unmodified `scripts/setup-wordpress.ps1` (repo root), run with
  `-SiteUrl http://perego.local -Title "Perego Creative Studio" -DbName perego -DbPrefix perego_wp_`.
- **Scaffolded** via `wp corex make:site Perego --path=sites/perego --starter`. Generated `perego-site/`
  (plugin, `PeregoSite\` namespace) + `perego-theme/` (FSE theme) + this governance set, plus the
  `--starter` vertical slice (`perego-site/src/Models/Example.php` etc. — remove per
  `perego-site/REMOVE-EXAMPLE.md` once no longer needed as a reference).
- **Active**: foundation (`corex-core`, `corex-blocks`, `corex-config`, `corex-forms`) + recommended
  add-ons (`corex-ui`, `corex-kit-company`, `corex-media`), junctioned in from the repo root's own
  `plugins/`/`addons/`; `perego-site` (plugin) + `perego-theme` (active theme), junctioned in from this
  directory. `wp corex doctor` → all GOOD except "Brand tokens: RECOMMENDED — no brand.json found"
  (expected — `perego-theme` has its own complete `theme.json`, not the parent `corex` theme's
  brand-overlay mechanism; not applicable here). No PHP fatals.
- **Two framework bugs found + worked around (not fixed upstream — out of scope for a client site;
  flag to the CoreX team separately)**:
  1. `scripts/setup-wordpress.ps1` pipes a here-string into `wp config create --extra-php`, which on
     Windows PowerShell 5.1 injects a UTF-8 BOM mid-file, corrupting `wp-config.php`
     (`Call to undefined function define()`, every subsequent `wp` call fails). Worked around by
     patching the generated `wp-config.php` directly (strip the BOM, rewrite BOM-less UTF-8) — a
     runtime artifact, not framework source, so this doesn't touch `scripts/setup-wordpress.ps1` itself.
  2. The same script's plugin activation (`wp plugin activate @pluginSlugs`) uses
     `Get-ChildItem`'s alphabetical order, activating `corex-blocks`/`corex-config` before
     `corex-core`, which fails their "Requires Plugins" dependency check on a *fresh* database.
     Worked around by activating manually in dependency order
     (`corex-core corex-blocks corex-config corex-forms`).
- **Outstanding (needs an elevated shell — not something this session could do):** add
  `127.0.0.1 perego.local` / `::1 perego.local` to `C:\Windows\System32\drivers\etc\hosts`, and
  `Restart-Service wampapache64`. The vhost block is already appended to
  `C:\wamp64\bin\apache\apache2.4.59\conf\extra\httpd-vhosts.conf` and verified with `httpd -t` →
  `Syntax OK`.

## spec 001 — Global Foundation (2026-07-11)

Branch `feature/001-global-foundation`. Full spec/plan/tasks at `specs/001-global-foundation/`. Built
via real TDD (Pest, red-green-refactor) against the live WordPress install, not just written and hoped
to work.

**Shipped and verified live** (rendered through `wp eval do_blocks(...)` against the actual install,
not only unit-tested):
- `perego-theme/site-header` + `perego-theme/site-footer` — the shared shell every template uses.
  Full desktop+mobile markup, Interactivity API wiring (sticky header, mobile slide-in panel with
  focus trap + scroll lock + Esc/backdrop/link-click close + focus restore, mobile Services
  tap-accordion, desktop dropdown via pure CSS).
- Bilingual mechanism: `LanguageDriver` interface, `PolylangLanguageDriver` + `FallbackLanguageDriver`
  (constitution IX — Polylang is never a hard dependency), resolved via `LanguageService`. Polylang is
  actually installed + active (v3.8.5) and confirmed to be the driver `LanguageService` resolves live —
  it just has no languages configured yet (needs its own wp-admin wizard, see DECISIONS.md).
- `perego-theme/preloader` — session-gated, reduced-motion-safe, soft-hide ~0.9s / hard-hide 2.5s.
  Wired only into `front-page.html`.
- 32 Pest tests, all green. `wp corex doctor` green throughout (only the expected "Brand tokens"
  advisory, which doesn't apply here). No PHP fatals introduced at any point.
- Found + worked around two more pre-existing framework quirks along the way (both logged in
  DECISIONS.md): every generated `PeregoSite\*` class's `defined('ABSPATH') || exit;` guard silently
  kills a headless test process with zero output unless the test bootstrap defines `ABSPATH` first
  (same fix as the framework's own `tests/bootstrap.php`); `wp corex make:site`'s `--path` collides
  with WP-CLI's own reserved `--path` flag, so it must be invoked from a context with no separate
  `--path` on the command line (see spec 001's plan.md Task 1 for the exact working invocation).

**Real gaps — not done, not hidden**:
- **No pixel/behavioral fidelity check** against the design handoff's screenshots yet — structure and
  tokens are faithful by construction (same token source, same documented interaction spec), but no
  side-by-side visual comparison has actually been done.
- **Polylang languages not configured** — install/detection works, but no EN/AR languages exist in
  Polylang yet (its own wp-admin wizard, not WP-CLI-automatable in the version installed).

**Closed since (2026-07-11, same day)**: Jest is now set up for `perego-site`
(`perego-site/jest.config.js` + a local `@wordpress/interactivity` test double — see DECISIONS.md) with
25 tests covering all three blocks' interactivity: sticky-scroll, mobile menu (open/close, focus trap,
scroll lock, backdrop/link-click close), the mobile tap-accordion, the language toggle (RTL flip, cookie
persistence, no-op guard), and the preloader (reduced-motion, session-gate, soft/hard-hide timers,
storage-failure fallback). All green. Self-applied `test-guard` caught and removed one redundant
implementation-detail test. Known rough edge: the framework's root `jest.config.js` doesn't exclude
`sites/`, so `npm run test:js` from the repo root now also discovers (and fails to resolve) these files —
flagged as a framework-side follow-up in DECISIONS.md rather than fixed here (out of bounds for Client
Site Mode). Run this site's JS tests via `npx jest --config sites/perego/perego-site/jest.config.js
--rootDir sites/perego/perego-site`.

## Outstanding action needed from the site owner

- Add `127.0.0.1 perego.local` / `::1 perego.local` to `C:\Windows\System32\drivers\etc\hosts` and run
  `Restart-Service wampapache64` (or restart via the WAMP tray icon) — needs an elevated shell, could
  not be done from this session. The vhost config itself is already in place and syntax-verified.
  Nothing in this feature can be checked in an actual browser until this is done.
- Configure Polylang's two languages (English default, Arabic) via `/wp-admin/admin.php?page=mlang`.

## spec 002 — Home / M2 (2026-07-11)

Branch `feature/002-home` (off `feature/001`). Full spec/plan/tasks at `specs/002-home/`. Two new
dynamic FSE blocks + the asset build pipeline that was missing all along.

**Shipped and verified live** (real browser, EN + AR/RTL + mobile):
- `perego-theme/hero-slider` — rotating headline slides, dot tablist, prev/next + pause/play, aria-live
  announcer; auto-advance 6.5s, hover + visibility pause, reduced-motion gate, stop-on-interaction
  (WCAG 2.2.2). Interactivity API store. 9 Pest + 12 Jest.
- `perego-theme/services-teaser` — four staggered service cards + See-All arrow link, pure-CSS hover.
  7 Pest. Rebindable to the `service` CPT in M3.
- `PeregoSite\Content\HomeContent` — locale-aware EN/AR copy (en fallback). 5 Pest.
- `front-page.html` composes header → preloader → hero-slider → About (core glass panels) →
  services-teaser → footer.
- **Asset build pipeline** (DECISIONS.md — "M2 asset build pipeline"): the M1+M2 block SCSS/JS and the
  theme `main.scss`/`main.js` are now actually compiled (`wp-scripts --experimental-modules` for the
  Interactivity API modules; `sass` for the theme). This was the true cause of M1's "styling unverified"
  gap — nothing was compiled, so nothing loaded. **Now fixed; M1's blocks are styled + interactive too.**
- Full suite green: 53 Pest + 37 Jest. Guard Gate clean. Visual fidelity checked against the handoff
  screenshots; 3 CSS bugs found + fixed (see spec 002 tasks.md).

**Build commands** (run before serving/deploying — output is gitignored):
`cd sites/perego/perego-site && npm run build` · `cd sites/perego/perego-theme && npm run build`.

## spec 003 — Services + Portfolio / M3 (2026-07-11, IN PROGRESS)

Branch: created on the M2 line. Full spec/tasks at `specs/003-services-portfolio/`.

**US1 (project CPT + portfolio grid) — DONE + verified live, but NOT yet committed** (a shell/tooling
outage — the auto-mode Bash/PowerShell classifier went down mid-session — hit exactly at commit time):
- `ProjectPostType` (CPT + `perego_project_category` taxonomy, 4 terms), `PortfolioContent` (EN/AR),
  `PortfolioGridRenderer`, `ProjectRepository` (WP_Query→cards), `portfolio-grid` block (filter via
  Interactivity API), `archive-perego_project.html`, `scripts/seed-projects.php` (9 placeholder
  projects seeded). Global `box-sizing` reset added (fixed a ~144px overflow strip on every page).
- Verified: `/work/` 200, 9 cards + 5 filters render, assets enqueue, EN + AR/RTL + mobile
  screenshots reviewed. Full suite was green at 70 Pest + 44 Jest before the outage.
- Env fixes applied (runtime, `wp/` gitignored — see DECISIONS.md): pretty permalinks via `wp eval`
  (NOT `wp rewrite structure`, MSYS path-conversion gotcha) + created `wp/.htaccess`.

**US2 (project gallery lightbox) — SOURCE BUILT, UNVERIFIED**: `ProjectGalleryLightboxRenderer` +
`project-gallery-lightbox` block (dialog, focus trap, counter, prev/next via Interactivity API) +
Pest + Jest tests written. NOT yet: wired into the provider, a `single-perego_project.html` template
to host it, gallery meta, or any test run / build / live check.

**US3 (service CPT + singles + archive) — PARTIAL**: `ServicePostType` + test written. NOT yet:
`ServiceContent` (EN/AR), service single renderer/template, services archive, provider wiring.

**➡ RESUME HERE when the shell is back**: (1) run full Pest + Jest — fix any failures in the new
lightbox/service tests; (2) `npm run build` in perego-site; (3) commit the verified US1 slice first,
then wire + verify US2/US3; (4) push. All source is saved in the working tree — nothing is lost, it
just needs verification + commit. `build/` and `wp/` stay gitignored.

## Autonomous implementation run (2026-07-11, PEREGO_IMPLEMENTATION_PROMPT.md)

Branch `feature/002-home`. Recovery checkpoint `recovery/2026-07-11-pre-impl` (at `af9e4fe`)
created + pushed to origin before any structural work. Remotes verified: `origin`=Perego,
`upstream`=CoreX (push disabled). CoreX baseline re-verified at runtime — `v0.33.0`/`71639e7`
is latest stable and already merged; `upstream/main` `ff61bf0` also already an ancestor of HEAD,
nothing new to sync (full record in `docs/corex-baseline.md`).

**Shipped + verified this run:**
- Phase 0–2 deliverables: `docs/repository-audit.md`, `docs/corex-baseline.md`,
  `docs/decision-repair-vs-restart.md` (decision: **repair in place** — architecture is sound).
- **M3 US3 (Services) — service singles DONE + verified live**: `perego_service` CPT registered;
  `ServiceContent` EN/AR provider; `perego-theme/service-hero` block (eyebrow + current-service H1 +
  four-service tabs, faithful `svc-hero`/`svc-tabs` CSS, RTL + reduced-motion); `single-perego_service.html`
  renders header → service-hero → **editable post-content** (What we do / Our Process authored as block
  content, per the editor-canvas rule) → footer; `scripts/seed-services.php` idempotently seeds the four
  services with real handoff copy. Verified: `/services/<slug>` HTTP 200, one H1, 4 tabs (active +
  aria-current), zero PHP notices, seed idempotent. Fixed the previously-risky `ServicePostType` test.
- Suite: **90 Pest + 54 Jest green**. wp-guard clean. Commits `114a0b4`, `c902fd5`, `5017611` pushed.

**Phase 7 (Join-us / CV form) — DONE + built + guarded (2026-07-12):** the footer "Join us" form ships as
the server-rendered `perego-theme/join-form` block posting to a new secure endpoint. Pieces:
`PeregoCareersController` (`POST perego/v1/careers/apply`) — anonymous, honeypot + per-IP rate-limited, CV
validated by `wp_check_filetype_and_ext` + `finfo` sniff under the pdf/doc/docx ≤ 5 MB policy, stored as a
**private** attachment, recorded best-effort against a standing "Open Application" `corex_job` when
`corex-careers` is active, and answered with branded EN/AR applicant + admin emails via the existing
`PeregoMailer`. `JoinFormRenderer` (label-bound inputs, required markers, `aria-live` status, honeypot,
no-JS-usable markup), `join-form` block (`index.js`/`view.js` upload lifecycle + `style.scss`), EN/AR copy in
`GlobalContent::join()`, wired into `SiteFooterRenderer` (via `do_blocks`, guarded when unregistered) + the
service provider (REST route + block). `scripts/seed-careers.php` idempotently seeds the standing job.
**Fixed during the guard pass:** `join-form/index.js` was missing `import './style.scss'`, so `style-index.css`
never built and the form would have rendered unstyled — added the import (matches every other block), rebuilt,
CSS + RTL now emit. **Suite: 156 Pest + 65 Jest green** (incl. `JoinFormRenderTest` + `join-form/view.test.js`);
`npm run build` clean; wp-guard + clean-code-guard clean. **Resolves DECISIONS Decision 7.** _(Live HTTP smoke +
real email delivery remain env-gated — needs Apache + a mail transport.)_

**Phase 5 (global sections) — DONE + verified live (2026-07-12):** the editor-managed, Polylang-linked
`perego_section` CPT + `perego-theme/global-section` block now source the site's repeated global copy
(header, standard/contact footers, global CTA, contact details, 404 editorial) from canvas-authored,
translatable records inside the language-neutral FSE parts. Pieces: `GlobalSectionPostType`
(private-but-editor-visible, `show_in_rest`, `_perego_section_role` meta; declared translatable via the
Free `pll_get_post_types` filter), a pure `GlobalSectionResolver` (role+locale → post id with a strict
**no-language-mixing** rule + deterministic lowest-id singleton pick), `GlobalSectionRenderer` (renders the
current-language record's blocks; admin-visible "missing translation" notice; nothing for visitors when a
translation is absent), the `global-section` block (role selector in the sidebar — structural only — server
render), and `scripts/seed-global-sections.php` (idempotent EN/AR seed from the approved handoff copy, links
translations, never overwrites editor changes). **Bug caught + fixed via a guard test:** the intended
`perego_global_section` name is 21 chars and WordPress silently rejects post-type names over 20, so the CPT
never registered — renamed to `perego_section` (14). **Verified live on http://perego.local:** CPT registers,
`is_translated_post_type: yes`, 12 records seeded (6 roles × EN/AR), header pair linked `{"en":72,"ar":73}`,
and the block renders EN "Start a Project" vs AR "ابدأ مشروعك" for the same role. **169 Pest + 65 Jest green**
(13 new: resolver 4, CPT 5, renderer 4); build clean; wp-guard + clean-code-guard clean. **Decision 8 logged.**
_(Wiring the block into the header/footer template parts + migrating the remaining GlobalContent chrome strings
is the natural follow-up.)_

**Editor-canvas remediation note (audit §6):** service singles now use editor-canvas post-content for
prose. The Home/portfolio surfaces still render prose from PHP providers (`HomeContent`,
`PortfolioContent`) — dynamic + bilingual but not yet canvas-editable. Tracked as a remediation
milestone; the service-single pattern is the template to follow when retrofitting them.

## Next (autonomous run — honest remaining scope)

Large scope remains from the implementation prompt; work continues in verified, committed increments.
Immediate queue:
1. ~~**M3 US3 archive fidelity** — language-aware `services-overview` block~~ — **done** (`e2f766b`):
   `/services/` renders the Our-Services intro + 4 cards + process + CTA, one H1, EN/AR. **M3 US3
   complete** (CPT + EN/AR content + singles + archive).
2. ~~**M3 US2** — case-study project singles~~ — **done** (`3f573a1`): `project-hero` block
   (breadcrumb + category + title H1 + client/year/role/deliverables meta) + `single-perego_project.html`
   + editable 5-section narrative seeded (demo copy, no invented metrics). **M3 complete.** Still
   deferred: gallery lightbox (needs seeded media — asset pipeline) + AR project translations (ship
   with real owner data).
### Completed later in the same run (M4/M5/SEO)
- **404** (`8e2c969`): `not-found` block + `404.html`, language-aware, HTTP 404, one H1.
- **Search** (`421a7d9`): `search-results` block + `search.html`, real WP search, server-rendered,
  empty/no-query states, EN/AR.
- **Legal** (`7edd507`): `legal-toc` block (TOC generated from the page's own H2 anchors) + `legal`
  custom template + Terms/Privacy pages (EN full drafts + AR linked, counsel-review note).
- **Journal** (`6850376`): `journal-header` block + `home.html` (Query Loop) + `single.html`
  (comments) + static-front-page/`/journal` wiring + 3 demo posts. Homepage still renders the hero.
- **SEO** (`01fd9f0`, `af56600`): `noindex,follow` on search/404, default `page.html`, and JSON-LD
  (Organization/WebSite/Service/Article/CreativeWork/BreadcrumbList — no fabricated claims).
- Suite now **112 Pest + 54 Jest**, all green; wp-guard clean on every increment.
- **Agent readiness + hreflang (Phase 10, 2026-07-12):** `PeregoAgentReadiness` serves a real `/llms.txt`
  (`text/plain`, `nosniff`) built from the site name/tagline + real routes + EN/AR language URLs — satisfying
  the `llms_txt` signal CoreX's own `ReadinessScorer` checks (no duplicate dashboard) — and emits the
  `x-default` hreflang that completes Polylang's EN/AR alternates. Verified live: `/llms.txt` 200 text/plain;
  head now carries EN + AR + **x-default**. Pure builders unit-tested (3 new). Decision 11.
- **Headless verification pass + AR-archive fix (Phases 11/13, 2026-07-12):** new
  `scripts/verify-visual.mjs` drives Chromium over the primary routes × EN/AR × mobile+desktop,
  asserting no horizontal overflow, no JS errors / broken resources, one `<h1>`, and correct
  `lang`/`dir` (AR URLs resolved from each page's own `hreflang="ar"` alternate — Polylang Free
  de-duplicates AR slugs, e.g. `/ar/contact-2/`). **It found a real bug:** `/ar/services/` + `/ar/work/`
  404'd because `perego_service`/`perego_project`/`perego_client` were **not** Polylang-translatable —
  fixed via a generalized `pll_get_post_types` filter (`registerTranslatablePostTypes`); after a rewrite
  flush both AR archives now 200. **Result: 24 checks, 0 hard failures.** Remaining as honest content
  gaps (not defects): AR Work + Journal translations (need real project/post content, item #3). Evidence:
  `docs/visual-acceptance.md` (+ git-ignored `output/verify-visual.json`). Decision 12.
- **A11y (Phase 11, 2026-07-12):** `scripts/verify-a11y.mjs` injects axe-core 4.12 and audits WCAG
  2.0/2.1/2.2 A+AA across 7 EN/AR pages — **0 violations of any impact**. Manual keyboard checks remain
  a follow-up. Recorded in `docs/visual-acceptance.md`.
- **Cleanup (Phase 14, 2026-07-12):** removed the `--starter` example scaffolding per REMOVE-EXAMPLE.md
  (Example model/repo/service/controller/renderer/options/block + test + the doc) and unwired it from the
  provider. Site still boots (home + `/ar/` 200), the `perego/v1/example` route is gone, 176 Pest + 65 Jest
  green, build clean.
- **Interaction states + keyboard-bug fix (Phase 12, 2026-07-12):** `scripts/verify-interactions.mjs`
  drives the live header — sticky-on-scroll, mobile hamburger open + scroll-lock, Escape-close + focus
  restore, AR pill = real `/ar/` anchor. It **caught a real keyboard bug**: Escape didn't close the
  mobile nav because focus stayed on the hamburger while the Escape handler is scoped to the `<nav>`.
  Fixed by moving focus into the panel on open (the focus trap is now real) — `view.js` + a covering Jest
  test. **4/4 interaction checks pass; 176 Pest + 66 Jest green.**

### Still remaining (implementable — continues from here)
3. **Polylang Free EN/AR** — languages configured + services/legal linked (done). **Language switcher: DONE +
   verified live (2026-07-12)** — the header switcher is now **real navigation** (Decision 10): server-rendered
   anchors to `LanguageDriver::urlFor()`, current locale a non-link `aria-current` marker, no JS-only toggle.
   Added `managesLanguageViaUrl()` to the driver contract (Polylang true / fallback false); the fallback
   `currentLocale()` now honors `?lang=` so no-JS switching works there too; the view.js now only persists the
   cookie in fallback mode (never a stale-cookie override under Polylang). **Verified live:** `/` 200 →
   `<a href="http://perego.local/ar/" hreflang="ar">AR</a>`; `/ar/` 200 renders `<html dir="rtl" lang="ar">`
   with AR as the current marker. **174 Pest + 65 Jest green** (rewrite rules already flushed — no manual step
   needed this run). _AR **content** translation-linking: services/legal done; **projects + journal now
   done too** (2026-07-12) via `scripts/seed-ar-content.php` — idempotent, Polylang-linked AR demo
   translations of the 9 projects, 3 journal posts, their taxonomy terms, and the Journal/Home pages.
   After a rewrite flush, `/ar/work/`, AR project singles, `/ar/المدونة/`, and AR post singles all
   resolve; **verify-visual is now 44 checks, 0 failures, 0 gaps** (every route in both languages). Copy
   is clearly-marked demo; owner swaps in real projects/articles before launch. (Clients AR: follow-up.)_
- **Lighthouse audit + SEO/a11y fixes (Phase 9/10/11/13, 2026-07-12):** mobile Lighthouse on the home
  page → **Performance 94 · Accessibility 100 · SEO 100 · Best-Practices 79**. Drove two real fixes:
  `PeregoMeta` adds a dynamic EN/AR meta description + OG/Twitter tags (SEO 92→100, 5 unit tests), and
  the slider dots/bullets gained WCAG 2.2 AA touch targets via transparent padding (a11y 97→100, visible
  design unchanged). Best-Practices 79 = the HTTPS/redirect audits only (local-dev HTTP; passes on
  production SSL). Command + scores in `docs/visual-acceptance.md`.
4. ~~**Global sections** — `perego_global_section` CPT + `perego/global-section` block for header/footer/
   404 repeated copy rendered language-aware inside the neutral FSE parts.~~ — **done (2026-07-12), see below.**
5. Then: clients CPT + carousels (Swiper), journal, legal + TOC, search, 404, forms (footer/brief/join-us)
   + CoreX Email templates, SEO/schema, a11y + responsive audit, image pipeline, batched visual regression.

### Earlier milestone log

1. ~~Set up Jest for `perego-site`~~ — **done 2026-07-11**.
2. ~~Visual fidelity check (M1/M2 homepage)~~ — **done 2026-07-11** via Playwright + host-resolver-rules
   (no hosts-file edit needed; the vhost is reachable by mapping `perego.local`→127.0.0.1). Spec 001's
   T040/T041 homepage portion is satisfied by the M2 check.
3. **M3 (Services + Portfolio)** is the current milestone: `service` CPT + 4 singles; `project` CPT +
   category taxonomy + `perego/portfolio-grid` + project single + `perego/project-gallery-lightbox`.
4. Owner action items still open (not blocking): add `perego.local` to the hosts file + Polylang
   language config (both need an elevated shell / wp-admin).

<!-- superseded next block -->
### (superseded) earlier Next
1. Do the two owner action items above.
2. Set up Jest — done.
3. Visual fidelity check — done.
4. Then `/specify` M2 (Home: hero slider + services tabs) using
   `docs/superpowers/specs/2026-07-11-perego-corex-design.md` as the input design (its environment
   section is superseded by this file; its content mapping/phasing still stands) — following the same
   real Spec Kit flow (spec → plan → tasks → implement, TDD throughout) demonstrated in spec 001, on
   its own `feature/002-*` branch.
