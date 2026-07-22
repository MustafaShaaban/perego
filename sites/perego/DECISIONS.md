# Perego — Decision Log

## 2026-07-22 — Spec 021 C6: Clients carousel stays SSR (dynamic), composer + headings on shared primitives

The Clients carousel is the homepage's one **dynamic** block: its cards are a projection of many published
`perego_client` posts (each with its own logo/video), not content this single block instance owns. Per the
static-vs-dynamic rule (DECISIONS 2026-07-21), it therefore **keeps its `ServerSideRender` canvas preview** —
it is deliberately NOT converted to a real-markup skeleton, and has no parity test. What spec 021 changes is
the editing surface, brought in line with C4's services-teaser treatment:

- **Headings move to the Inspector.** The four section headings (corporate/individual title + subtitle, En/Ar)
  were in-canvas RichText fieldsets sitting above the SSR — a duplicate of what the SSR already renders. They
  are now `LanguagePair` controls in the Inspector; the SSR preview shows the real headings and updates live as
  they change. (In-canvas RichText isn't feasible for a dynamic block whose content lives inside the SSR.)
- **The composer moves to `RecordPicker`.** The bespoke `PanelBody`/`SelectControl`/`CheckboxControl` UI is
  replaced per client type by a `manual` picker (ordered chosen list + add) and an `automatic` picker
  (per-item show/hide), driven by the unchanged `{type}Mode`/`{type}Order`/`{type}ExcludeIds` attributes. The
  PHP renderer is untouched, so the front end is byte-identical (ClientsCarousel Pest 24/24).
- **Card media stays the Client's own concern.** Each client's tile/logo is edited on that Client's screen; a
  help note says so. The C6 slice owns the block's composition + headings, not per-client media.

## 2026-07-22 — Spec 021 C5: Home About background live-canvas (locked chrome, no controls)

The `home-about-bg` block `edit()` renders real markup (`HomeAboutBgSkeleton`) instead of `<ServerSideRender>`,
applying the C1–C4 static-layout standard. The block is **purely decorative chrome** — a full-bleed background
image with no locale dependency and no editable content — so T014's "direct visual About editing with locked
structural wrappers" is satisfied by the composition, not by this block: the About section's editorial content
is the adjacent native `wp:post-content` (which the editor already edits directly), and this block stays locked
with zero controls. Its only change is dropping the SSR iframe for the real `div.home-about__bg > img`, pinned
by a parity test. No attribute/renderer change, so the front end is byte-identical (HomeAboutBg Pest 2/2).

## 2026-07-22 — Spec 021 C4: Services teaser live-canvas (composer on `RecordPicker`, cards as a seed preview)

The homepage Services teaser `edit()` now renders real markup (`ServicesTeaserSkeleton` in
`services-teaser/preview.js`) instead of `<ServerSideRender>`, applying the C1–C3 static-layout standard and
folding the block's existing automatic/manual/hybrid composer onto the shared Inspector design system.

- **In-canvas head text nests inside the real elements.** The heading and the "See All" label are edited
  with `RichText tagName="span"` nested inside the real `h2.services-teaser__title` and
  `a.link-arrow.services-teaser__link` — not as whole-element replacements. This keeps the arrow `svg` and the
  `aria-labelledby` target id (`#servicesTeaserTitle`) that a whole-element `RichText` would drop, and because
  parity renders the skeleton with plain defaults (no override nodes), the nested spans are edit-time-only and
  never affect the test.
- **The composer moves to `RecordPicker`.** The old bespoke `PanelBody` + `SelectControl` + `CheckboxControl`
  UI is replaced by the shared primitive: a `manual` picker (ordered chosen list + add) for the
  selected-first set and an `automatic` picker (per-item show/hide) for exclusions, driven by the existing
  `servicesMode` / `serviceOrder` / `serviceExcludeIds` attributes. The PHP `cards()` composition is unchanged,
  so the front end is byte-identical — proven by the unchanged ServicesTeaser Pest suite (11/11).
- **Service cards render from the seed, as a design preview.** The four cards are a projection of the
  `perego_service` CPT (label/image overlaid from each Service's teaser meta), and which cards appear on the
  live site follows the composer. Resolving that live selection's images inside editor JS would need a bespoke
  data layer, so the canvas shows the four **seed** cards as a faithful representation of the card design; the
  Inspector composer + a help note own the live behaviour. The parity test validates the card markup; the CPT
  overlay stays the renderer's job. Revisit if owner review wants the live selection mirrored in the canvas.

## 2026-07-22 — Spec 021 C3: Hero live-canvas (slide switcher via the real dots, structured `slides` array)

The homepage Hero `edit()` now renders real markup (`HeroSkeleton` in `hero-slider/preview.js`) instead of
`<ServerSideRender>`, applying the C1/C2 static-layout standard to a **multi-slide** block. Decisions specific
to the Hero:

- **The real dots are the slide switcher.** The front-end hero shows one slide at a time (non-active slides
  carry `hidden`); the editor reuses exactly that — the currently-selected slide is visible and editable, the
  `.hero__dots` buttons switch which slide is composed. This replaces the old ad-hoc "Slide 1/2/3" button
  tablist + stacked `SlideFields`. Because parity ignores attributes (including `hidden`), the skeleton
  rendered with the default `activeIndex` (0) still matches the captured fixture.
- **First slide stays the `h1`, always.** `slideTitleTag(index)` returns `h1` for index 0 and `p` for the
  rest, mirroring the renderer's pre-hydration rule, so there is exactly one `h1` in the DOM regardless of
  which slide is selected. The shared `.hero__title` class makes every slide look identical, so editing a
  later (`p`) slide in place is still WYSIWYG.
- **Structured `slides` array replaces the legacy per-slide attributes.** Slides persist as one
  `slides` array of `{ titleEn, textEn, titleAr, textAr }` (spec 021 structured repeater); `normalizeSlides`
  upgrades the legacy `slide{n}…{En,Ar}` attributes on read so entered copy isn't lost. `HeroContent::resolve()`
  already reads that array first (`composedSlides`), so the PHP renderer is untouched and unedited pages stay
  byte-identical — proven by the unchanged Hero Pest suite (11/11).
- **Editing surface:** in-canvas `RichText` for the selected slide's English headline + supporting text and
  the CTA; the Inspector holds each slide's Arabic copy, the Arabic CTA, and slide management (add / duplicate
  / reorder / remove via `RepeaterControls`), on the shared `../../Editor` primitives. Canvas shows English;
  Arabic is edited in the Inspector — same rule as C1/C2.
- **Parity:** `parity.test.js` pins the whole `.hero` section (prism, slides, CTA, dots, status) against
  `__fixtures__/front-hero.html` (a live capture from `/`) and detects drift (an extra slide). Third block on
  the harness.

## 2026-07-21 — Spec 021: unified block-Inspector design system + full end-user control (program)

Owner review of C1/C2 raised two cross-cutting requirements for **every** block: the settings/options UI is
unpolished and inconsistently built, and content the end user should control is hardcoded. Direction agreed:
keep the public design frozen; change only the block back-end (attributes + renderer plumbing) and the editing
experience, incrementally per component.

- **One Inspector design system.** A small set of shared primitives in `perego-site/src/Editor/`
  (`PanelSection`, `LanguagePair`, `LinkControl`, `LabeledRepeater`, `RecordPicker`, plus the existing
  `MediaField`/`RepeaterControls`) replaces ad-hoc, inline-styled controls. They are styled by
  `perego-theme/assets/src/scss/editor-inspector.scss` → `editor-inspector.css`, enqueued **once** via
  `enqueue_block_editor_assets` — the Inspector sidebar renders outside the canvas iframe, so
  `add_editor_style('main.css')` never reaches it. `partitionRecords` lives in the component-free
  `collection.js` so its selection semantics are unit-tested (the `@wordpress/components` render path is not
  testable under this Jest setup).
- **Full dynamic control.** Every hardcoded user-facing detail becomes an editable attribute (EN/AR when
  localized) with the current value as the fallback default, so no migration is needed and unedited pages are
  byte-identical. First applied to the **footer bottom bar**: `copyrightEn`/`copyrightAr` (a `{year}` token is
  replaced at render) and `legalLinksEn`/`legalLinksAr` (a label+URL repeater). `SiteFooterRenderer::renderBottomBar`
  reads them, falling back to the exact prior copyright + Journal/Terms/Privacy links; `legalHref()` localizes
  internal paths and keeps external/anchor URLs verbatim (mirrors the header CTA rule). The bar's tags/classes
  (`p`, `nav.footer-legal > a`) are unchanged, so the footer parity test stays green.
- **Editing surface.** In-canvas for text/media in the real markup (CTA, blurb, copyright); Inspector for
  structured lists (nav, channels, social, legal links, record pickers). The canvas shows the English variant;
  the Arabic variant is edited in the Inspector.
- **Rollout** is one block at a time (header + footer done in this PR); dynamic/query blocks keep
  `ServerSideRender` + a styled placeholder and gain `RecordPicker` rather than a real-markup canvas.

## 2026-07-21 — Spec 021 C2: Footer live-canvas is a hybrid (real static surfaces + placeholder form columns)

The footer `edit()` now renders real markup (`FooterSkeleton` in `site-footer/preview.js`) instead of
`<ServerSideRender>`, following C1's pattern — but the footer is a HYBRID block, so the treatment differs
from the header by design:

- **Static surfaces render real markup:** the contact column (logo, "Contact us", contact-channel list,
  blurb, social row) and the bottom bar (copyright + legal nav). These match the `SiteFooterRenderer`
  output and are pinned by `parity.test.js`, which asserts `.footer-contact` and `.site-footer__bottom`
  structurally equal a captured PHP fixture (`__fixtures__/front-footer.html`).
- **Dynamic form columns become labelled locked placeholders.** The quick-message and careers columns are
  rendered on the front end via `do_blocks()` (embedding `corex/form`, `footer-careers`, `join-form`) —
  they can't be replicated in editor JS and are dynamic per the static-vs-dynamic rule, so the canvas shows
  a labelled placeholder ("… shown on the live site") inside each `footer-col`. They are excluded from parity.
- **Content editing:** the English blurb is edited in-canvas (`RichText` at its real `p.footer-blurb`
  position); the Arabic blurb moves to an Inspector `TextareaControl`; contact channels, social links, and
  the flat variant stay in the Inspector (unchanged). The canvas shows the English footer.
- **Social-icon glyphs** are mirrored from `SiteFooterRenderer::SOCIAL_ICON_PATHS` into `preview.js` for
  visual fidelity — the same way the block already mirrored the seed channels/links. Parity ignores the
  glyph `d`, so this presentational copy can never break the test.
- **Front end frozen:** no PHP/renderer change — `SiteFooterRenderer` and its 9 Pest tests are untouched,
  so public output is byte-identical; this slice is editor-only.

## 2026-07-21 — Spec 021 C1: Header is the first true live-canvas block (real markup, not ServerSideRender)

The header `edit()` no longer renders a `<ServerSideRender>` iframe; it renders the REAL front-end header
markup (`HeaderSkeleton` in `site-header/preview.js`) — the same `.site-header__inner` tag/class skeleton
that `SiteHeaderRenderer::render()` emits — styled by the theme's `main.css`, which is loaded into the
editor canvas via `add_editor_style` (commit `76562fb`). This is the DECISIONS 2026-07-21 static-layout
standard applied for the first time, and it establishes the pattern for the remaining static blocks.

- **One skeleton, no drift.** `HeaderSkeleton` is pure (no editor-store components). `edit()` renders it
  with two structurally identical override nodes — a `MediaUpload`-triggering logo anchor and a
  `RichText` (`tagName="a"`) CTA — so in-canvas editing is added without changing the markup. The parity
  test (`parity.test.js`) renders the same component with plain defaults and asserts its `.site-header__inner`
  skeleton equals a captured PHP fixture (`__fixtures__/front-header.html`), so editor↔PHP drift fails CI.
- **Route-neutral fixture.** The fixture is the live header from `/contact/`, where no top-level nav item is
  active — matching the stateless editor preview (the lone `is-active`/`aria-current` is the lang-toggle's
  current-language marker, which the preview renders identically).
- **In-canvas vs Inspector.** CTA text and the logo are edited directly in the canvas. Bilingual nav (EN/AR
  pairs + dropdowns), the Services-dropdown source, sticky, and the CTA link stay in the Inspector — the
  canvas shows the **English** nav as its live surface; a single locale can't represent both nav languages
  at once. The lang-toggle and hamburger are rendered statically for visual fidelity (front-end-only
  behaviour, excluded from the parity comparison per the harness's per-block-omission allowance).
- **Front end frozen.** No PHP/renderer change — `SiteHeaderRenderer` and its 28 Pest tests are untouched,
  so public output is byte-identical; this slice is editor-only.

## 2026-07-21 — Spec 021: Service owns its Selected Work choices

The `service-selected-work` block is shared by Service templates, so its project list cannot be saved on the
block instance without applying the same selection to every Service. Portfolio mode, ordered Project IDs, and
automatic exclusions therefore live on each `perego_service` record. Empty legacy metadata means automatic
category results, preserving existing service pages. Language-neutral portfolio choices fall back to the linked
English Service record when a translated record has no local values.

## 2026-07-20 — Spec 021: Services dropdown is opt-in dynamic, not an implicit visual migration

The Header keeps its established Services links while `servicesMenuMode` is `manual` with no selected
records. This protects the frozen public baseline and lets existing template-part instances render exactly as
before. Editors can explicitly select `automatic` to show published Service posts, or choose manual Service
records and arrange their order; automatic mode permits exclusions.

Saved Service IDs are resolved through `pll_get_post()` for the current locale, and the renderer uses the
localized post permalink directly. That avoids guessing translated custom-post slugs and keeps a shared FSE
template part language-safe. The renderer also considers both the selected and translated IDs for exclusion,
so an editor can configure the menu from either language.

## 2026-07-20 — Spec 020 round 15: owner re-report — filters/pager still dead, lightbox nav position

Round 14 fixed the reply-chip's `[hidden]`-override bug but missed the **identical bug on the work grid**, and
re-centered the lightbox arrows on the viewport; the owner re-tested and both were still wrong. Two CSS-only
fixes, proven with a live Playwright probe on `/work/` and a project gallery.

**Work filters + pagination: the same `[hidden]`-override bug as the round-14 reply-chip, on `.post-card` and
`.pagination`.** `portfolio-grid/view.js` filters cards and trims the numbered pager by toggling the native
`[hidden]` attribute, but the immutable reference stylesheet sets author-origin `display` on those selectors
(`.post-card{display:flex}`:1040, `.pagination{display:flex}`:1054, `.pagination button{display:inline-flex}`:1055),
which beats the UA `[hidden]{display:none}` regardless of specificity — so hidden cards/pages stayed visible
(dead filters + "phantom" page numbers for pages that don't exist). The block's JS and PHP were already correct;
the page-count math was correct. Fixed with four author-origin `[hidden]` overrides in
`perego-wordpress-adapter.scss` (loaded after the reference; matching-or-higher specificity, no `!important`):
`.post-card[hidden]`, `.pagination[hidden]`, `.pagination button[hidden]`, `#portfolioEmpty[hidden]`. No JS
change; Jest 94/94 stayed green.

**Lightbox nav re-anchored to the handoff placement (supersedes round 14's viewport-edge decision below).**
Owner wants the arrows exactly as the handoff (`styles.css .lightbox__nav`): `position:absolute` anchored to
`.lightbox__inner`, `inset-inline-{start,end}:-70px` (just outside the media) on desktop, `8px` inside at
≤1120px, `inset-block-start:50%; translateY(-50%)`. Re-added the ≤1120px override round 14 had dropped. Also
applied the owner's requested `padding:20px 17px 20px 15px` on `.lightbox__nav:not([hidden])`, made proper with
`box-sizing:border-box` so the 52px circle keeps its size and the CSS chevron stays centered. Accepted tradeoff
(owner's explicit choice): anchoring to `.lightbox__inner` places the arrows slightly below the image center
because the dots rail + counter sit inside that box — this is the exact handoff behavior. In `_lightbox-chrome.scss`.

Verified live (Playwright, host-mapped `perego.local`): filters show only the chosen category; the pager shows
exactly the real page numbers (video = 23 items → 3 pages, no phantoms) and hides at a single page; lightbox
arrows are `absolute`, padded `20px 17px 20px 15px`, anchored to `.lightbox__inner`, outside the frame on
desktop and tucked inside at ≤1120px.

## 2026-07-20 — Spec 020 round 14: owner re-report — these WERE real code bugs

Round 13 closed several items as "cache, not code." The owner re-tested in a fresh browser and they still
failed. Investigation confirmed **every one was a genuine code defect** (proven; one via a live browser
probe on `/work/`). Correcting the record:

**Cancel-reply chip: `[hidden]` was overridden by an author rule.** `.comment-form__reply-chip{display:flex}`
(perego-wordpress-adapter.scss) beat the UA `[hidden]{display:none}` regardless of specificity, so the chip
could never hide and `view.js` `clearReply()` looked dead. Fixed by guarding the selector
`:not([hidden])`. (Not cache — the compiled CSS had no `[hidden]` guard.)

**Work filter: the Interactivity API region crashed on hydration (WP 7.0.2), wiping the filter chips.** A
live probe showed `.portfolio-filters` emptied and the region unhydrated (cards never bound), while the
header's Interactivity still worked. Rather than chase the directive/hydration fragility, **converted the
whole block to plain-DOM `view.js`** — the same `addEventListener` pattern as `service-selected-work`
(itself the handoff `main.js` filter port). The renderer now emits plain markup (no `data-wp-*`); the block
no longer depends on `@wordpress/interactivity`. This is the robust fix and removes the nested-context
write bug entirely. (Supersedes round-13's "work filter is not a code bug.")

**Work pagination added (owner: "as the design").** Client-side numbered pager (9/page) that recomputes for
the active filter, in the same `view.js`; server renders the pager `hidden` (progressive enhancement) up to
the unfiltered max page count; JS hides page numbers beyond the filtered count and the whole pager at one
page. Query unchanged (bounded ≤120, single fetch). Chose client-side over server paging so it cooperates
with the client-side filter (server paging would filter one page at a time).

**Nav active state clobbered on inner pages.** The home scroll-spy in `site-header/view.js` ran on *every*
page because the footer emits `id="contact"` sitewide and "Contact Us" is `href="#contact"`, so `spyEntries`
was non-empty everywhere; at the top of an inner page it fell back to highlighting Home, stripping the
server-rendered `.is-active` off Journal/Work. Gated the spy to the front page (`body.home`/`front-page`).
Also added ancestor matching to `SiteHeaderRenderer::isActive()` so a single post (`/journal/<slug>`) lights
its parent nav item.

**Mobile drawer opened at page-top after scroll.** `.site-header.is-scrolled{backdrop-filter:blur(14px)}`
made the header a containing block for its `position:fixed` `.main-nav`, so post-scroll the drawer anchored
to the header box (document top). Moved the frosted background to a `.site-header::before` layer (a sibling
of the drawer, not an ancestor); the header itself no longer carries `backdrop-filter`. Hamburger inline-end
spacing bumped 6px→16px.

**Legal TOC not sticky.** Ancestor `.page-section{overflow:hidden}` became the scroll/clip container for the
sticky `.legal-toc`. Swapped to `overflow-x: clip` (clips horizontal bleed without establishing a block-axis
scroll container), restoring sticky sitewide. (Round-13's "TOC sticky verified" did not hold in the owner's
browser.)

**Lightbox arrows re-centered on the viewport.** Round 13 fixed the chevron *glyph* but not the *position*:
the arrows were `position:absolute` inside the content-sized `.lightbox__inner` (which also wraps the dots +
counter, dragging its midpoint low). Switched `.lightbox__nav` to `position:fixed` with
`inset-block-start:50%; translateY(-50%)` and `inset-inline-{start,end}: clamp(8px,2vw,24px)` so they centre
on the viewport and hug its far edges; dropped the now-redundant ≤1120px override.

## 2026-07-20 — Spec 020 round 13: pre-FSE QA punch-list

**Services archive disabled (404), not redirected.** `has_archive=false`; the 4 service singles are the
canonical service pages and the header "Services" dropdown links to them directly, so the bare `/services/`
URL is unreachable via nav and returning 404 is the cleanest "disable" (owner-approved).

**Home in-page nav uses client-side scroll-spy, not server-set active state.** The About/Services/Clients/
Contact links are fragments to home sections; a JS `IntersectionObserver`-style scroll handler in
`site-header/view.js` toggles `.is-active` on the link for the section at ~30% viewport (page-bottom forces
the last). Kept purely client-side so the PHP `isActive()` route logic (used on inner pages) is untouched.
`getBoundingClientRect` (not `offsetTop`) because the home sections don't share an offset parent.

**One global `.container` cap fixes several width bugs.** Rather than per-section overrides, re-assert
`.wp-site-blocks .container { max-inline-size:var(--maxw); margin-inline:auto; padding-inline:var(--gutter) }`
over the adapter's constrained-layout reset. This caps the legal pages, home-About cards, and any generic
page in one rule (`.container` is only ever capped content; alignfull bands aren't `.container`).

**Legal pages get real EN + AR boilerplate, seed-owned, resolved via Polylang link.** Wrote professional
plain-language Privacy/Terms clauses (each page's lead states it is a template to be reviewed by counsel —
not legal advice). The seeder now UPDATES existing legal pages (content is seed-owned pre-launch) and finds
the AR page through `pll_get_post($enId,'ar')`, never the slug (Polylang de-duplicates it to `terms-2`,
which caused duplicate pages when matched by slug). Also fixed the `_perego_legal_updated` meta-key mismatch.

**Lightbox arrows are CSS chevrons, not text glyphs.** The `‹`/`›` characters are optically off-centre in
Open Sans; replaced with a rotated-border chevron in `shared/_lightbox-chrome.scss` (RTL-aware), centered by
the existing grid. The `×` close glyph was already centered and is unchanged.

**Work filter and journal Cancel-reply were not code bugs.** Both were verified working in a clean browser
(Interactivity filter hides the right cards; the custom Cancel button clears the reply chip). The reported
failures were stale build/opcache/browser cache — no code change, just rebuild + hard-refresh.

**AR home About is owned by `seed-home-about.php`, not `seed-ar-content.php`.** The latter used to copy the
EN front-page body into the AR home, overwriting the Arabic About with English. It now creates the AR home
empty so `seed-home-about.php` fills the Arabic copy.

## 2026-07-20 — Spec 020 round 12: service-page "What we do" fidelity + masonry fill

**(addendum) The "What we do" grid width cap is re-asserted over the adapter's own constrained-layout
reset.** That reset (`.wp-site-blocks .is-layout-constrained > :where(…) { max-inline-size:none }`, needed
so alignfull bands bleed full-width) out-specified `.svc-whatwedo__grid`, dropping its cap to `none` — the
section grew past 1440px on screens wider than the handoff. Fixed with a width/margin-only override at
higher specificity (`.wp-site-blocks .svc-whatwedo .svc-whatwedo__grid`) rather than exempting the grid
from the reset (which risks breaking the alignfull band). Verified @1920: 1440px, centered, matching the
handoff file.

**(addendum) The masonry brand-card logo zooms in place on hover instead of jumping.** The reference's
generic `.work-card:hover img { transform: scale(1.07) }` overrode the brand logo's positioning transform
(`translate(-50%,-50%) rotate(-90deg)`), so on hover it lost its centering/rotation and lurched to the
corner. Since `perego-reference.scss` is immutable, the adapter adds `.work-brand:hover .work-brand__logo`
that re-includes the positioning transform plus `scale(1.06)` — an anchored zoom (owner review).

**The round-7 `.svc-whatwedo__media { aspect-ratio: 7/5; object-fit: cover }` crop was reverted to the
handoff's natural image sizing.** Round 7 forced a fixed ratio so the media box was identical across the
four service singles; owner review found the result diverged from the design (the wider graphic-design /
website-making mockups were enlarged and side-cropped). Fidelity to the locked handoff (`.whatwedo__media
img { width:100%; height:auto }`) wins over cross-page box consistency: each service's mockup now renders
at its own natural aspect ratio, rounded + shadowed + tilted, exactly as the reference. Grid width is
unchanged (1440px = handoff).

**The hover "+" zoom glyph (`.work-zoom`) was removed from masonry gallery/image cards — a deliberate
divergence from the handoff.** The handoff shows a "+" expand affordance on non-video tiles; the owner
asked for it gone entirely. `ServiceSelectedWorkRenderer::card()` no longer emits `.work-zoom` (video
tiles keep the ▶ `.play-btn`; gallery tiles keep the "Gallery" badge). Image/gallery tiles still open the
lightbox on click, without a visible icon. The unused `.work-zoom` CSS stays in the immutable
`perego-reference.scss`.

**Arabic project tiles reuse the linked EN post's media via a repository fallback, not per-locale media
seeding.** AR project posts carry no thumbnail/gallery of their own (Polylang doesn't sync media, and the
AR seed sets none), and the masonry filters out tiles with neither — so AR grids rendered empty.
`ProjectRepository::videoUrlFor()` already EN-falls-back; `toGridCard()` (thumbnail) and `galleryFor()`
now do the same via a shared `enTranslationId()` helper. This avoids duplicating attachments across
locales and keeps EN as the single media source of truth, consistent with the existing video-URL fallback.

## 2026-07-20 — Spec 020 round 12: AJAX comments own the whole flow; layered anti-spam

**The journal comment form/list is now fully Perego-owned; core `wp:post-comments-form` +
`comment-reply.js` were dropped.** Reasons: (1) core's reply UX moves the form under the comment and
only a small "Cancel reply" link returns — owners read it as a trap; (2) core forces a full reload
with no feedback. Our block renders the handoff form itself and `journal-comments/view.js` handles
reply as a non-destructive "Replying to X — Cancel" chip (just sets `comment_parent`) and submits via
AJAX. The form still posts to `wp-comments-post.php` without JS (progressive enhancement).

**Comment submission goes through a dedicated REST controller, not the CoreX Forms engine.** CoreX
Forms stores generic submissions; comments must become real WP comments (threading, moderation,
display), so `PeregoCommentController` calls WordPress's `wp_handle_comment_submission()` (the correct
platform API — it runs flood/duplicate/blocklist/Akismet/moderation) and **borrows** the CoreX/careers
security pattern around it: `wp_rest` nonce + honeypot (`perego_hp`) + per-IP/post transient rate
limit. Layered defence satisfies "no spam / no dummy floods"; first-time authors stay held
(`comment_previously_approved=1`), so nothing unmoderated appears. Held comments are **not** injected
client-side (owner's choice) — only auto-approved ones appear live.

**Live injection reuses the server renderer.** The controller returns
`JournalCommentsRenderer::renderCard()` HTML for an approved comment; view.js inserts that
server-escaped markup (never user-raw) so the on-the-fly card is byte-identical to a reload.

**Preloader top gap was WordPress block-spacing, not the admin bar alone.** `.wp-site-blocks > *`
gets a global `margin-block-start:24px`; the adapter zeroed it only for header/main, leaving the
preloader pushed down 24px. Fix = `margin:0` on `.preloader` (plus full-viewport `inset:0`/`100dvh`
and a z-index above the admin bar so a logged-in editor sees no gap either).

**Scrollbar: brand-purple, CSS-only smooth (no JS momentum lib).** Owner chose the lightweight path —
`scrollbar-color`/`::-webkit-scrollbar*` in the adapter + the pre-existing reduced-motion-guarded
`scroll-behavior:smooth`. A JS inertia library (Lenis) was declined for bundle/a11y reasons.

## 2026-07-19 — Spec 020 round 11: journal comments via a custom renderer, not core comment blocks

**Custom `perego-theme/journal-comments` server-rendered block instead of `wp:comment-template`.**
The handoff comment card is an initials avatar in a gradient circle + a bordered body card, with an
indented reply. WordPress's core comment blocks emit gravatar-**image** avatars, `depth-N` nesting,
and `.wp-block-comment*` classes — the reference `.comment/.comment__*` styles (already in
`perego-reference.scss`) can never match them, and there's no clean way to get initials avatars from
core. So we render the list ourselves (mirroring `RelatedPostsRenderer`), reusing the reference CSS
verbatim, and keep only `wp:post-comments-form` for the functional (nonce/submit) form. The provider
callback threads comments (top-level then its replies, insertion order) so a reply renders under its
parent rather than being scattered by a flat date sort.

**Comment form reshaped to the handoff card with CSS, not a second custom block.** The working core
form stays; the `.comment-respond` wrapper becomes the 760px-centered card (aligned under the 760px
comment list), the heading moves inside via `comment_form_defaults` `title_reply` = "Leave a
comment", and the fields are re-laid with CSS grid + `order` (Name + E-mail side by side, Comment
full width) — because core stacks them Comment→Name→Email. Keeps submission intact while matching the
design.

**Demo comments are seeded (reversing round 9's "no demo comments").** "Identical, same cards" means
the handoff's three comments (Sara Adel; Mostafa Emam reply; Karim Hassan) must exist, so
`seed-journal-comments.php` seeds them idempotently on EN posts; the 3 old mismatched demo comments
on post 45 were deleted so it gets the correct set.

## 2026-07-19 — Spec 020 round 10: journal archive/single parity

**The journal archive stagger is a WP flow-layout artifact, fixed in CSS, not by changing the
post-template's layout type.** The `wp:post-template` renders as `.blog-grid` (display:grid) but WP
also stamps it `is-layout-flow`, which injects `margin-block-start` on every sibling after the first
— fine as a row gap in flow, but on grid items it offsets them within their row. Rather than switch
the block to `layout:{type:grid}` (which loses the reference's exact 3/2/1 responsive breakpoints
baked into `.blog-grid`), we neutralize the injected margin with `.blog-grid > * + * {
margin-block-start: 0 }` and let the grid `gap` own spacing. Real-specificity selector beats core's
`:where()` rule.

**Comment form trimming needs BOTH `comment_form_default_fields` and `comment_form_fields`.** Only
the latter reliably affects the block-based `core/post-comments-form` (it calls `comment_form()`
which always applies `comment_form_fields` at the end, but the default-fields filter can be bypassed
when fields are pre-supplied). We register both to strip WP's Website (`url`) field and the "save my
info" cookies checkbox, matching the handoff's Name + E-mail + Comment form. No block markup change.

**New `seed-journal-tags.php` — the single's tag pill row needs `post_tag` terms that no prior seed
created.** Idempotent, only tags posts with none, sets Polylang term language per locale so EN/AR
stay separate. The `.post-single__tags a` pill CSS was ported from the dead
`perego-legacy-pre-recovery.scss` (whose `--perego-*` vars are undefined at runtime) into the
adapter using the reference's real `.post-tag` token values, and the block's "Tags:" prefix +
default comma separator were dropped so it matches the handoff's bare pills.

**Debugging note: on this WAMP box, `curl` can serve a stale comment-form render (opcache
worker/keep-alive) while the real browser is correct.** During this round `curl` kept showing the
removed Website field even after opcache_reset, but Playwright (real Chromium) and isolated
`comment_form()`/`WP_Block::render()` all rendered the trimmed form. Verify PHP-render changes with a
real browser here, not `curl`.

## 2026-07-19 — Spec 020 round 9: two build steps must both run; don't diagnose visual bugs from source alone

**`perego-theme` and `perego-site` each have their own `npm run build`, and both must run before any
visual claim about the live site.** Round 7 rebuilt only `perego-theme` (SCSS/theme JS) after
touching `perego-site` PHP; `perego-site`'s own block build (`build/Blocks/`, covering
`media-lightbox`, `service-selected-work`, and every other block's JS/CSS) was hours stale,
including a whole shared lightbox-chrome partial that had never been compiled at all. The owner's
"lightbox isn't visually right" report was 100% this — the live page was serving pre-refactor CSS.
No error surfaced anywhere because a stale build isn't a build failure, it's just silently wrong
output.

**Root-caused via Playwright screenshots + computed-style inspection, not by re-reading the SCSS.**
Source code review had already (wrongly) concluded the CSS was correct twice. Only opening the live
page, screenshotting the rendered lightbox, and walking `.lightbox`'s ancestor chain in the browser
for stacking-context traps (`transform`/`filter`/`contain`/`backdrop-filter`) actually proved the
z-index and DOM were fine — the discrepancy was purely a build artifact. Going forward: when an
owner reports a visual bug after a code change, verify against a live render before assuming the
source is wrong.

**`project-gallery-lightbox/index.js`'s dangling `import './style.scss'` was silently breaking the
entire plugin's CSS bundle.** Round 6 deleted that block's `style.scss` (folded into the shared
`.lightbox` contract) but left the import in the editor script; webpack failed the whole build on
it, so no other block's CSS got the chance to rebuild either — one dead import blocked the fix for
everything else in this round. Removed; no other code changes needed.

## 2026-07-19 — Spec 020 round 8: home clients section matched to the owner reference image

**The individual card's middle line became a real field (`_perego_client_sub`), not a reuse of the
stat.** The handoff card has three text lines (title / subtitle / stat) and the `.indiv-card__sub` CSS
already existed, but the renderer only ever emitted title + stat and there was no meta for the middle
line — so the "intertainment show" line was structurally impossible. A dedicated subtitle meta (mirroring
how stat/video are separate fields) is cleaner than overloading the stat or splitting it heuristically,
and gives editors an explicit field.

**The stat now permits inline `<strong>` via `ClientPostType::sanitizeStat()` (`wp_kses` strong-only),
not `sanitize_text_field`.** The handoff bolds the number (`<strong>+1M</strong> views`). Rather than a
second "stat number" field or magic first-token bolding, the stat allows exactly one tag — `<strong>` —
sanitized identically on REST write, admin save, and render (`wp_kses($stat, ['strong' => []])`), so the
demo seed and any editor can bold the figure and nothing else can be injected.

**Corporate cap raised 12 → 20 (`CORP_MAX`/`INDIV_MAX` split), and demo view counts were seeded despite
the "don't fabricate view counts" note.** The reference shows a full 2×10 corporate grid and populated
`+1M views` cards; the owner explicitly approved seeding this demo content for visual parity. This is a
scoped, documented override of `seed-clients.php`'s original caution (real, approved client data still
replaces all of it before launch — FR-006). `seed-clients.php` also retires the legacy 4+4 "Sample"
placeholders (demo data only) so they don't inflate the carousels next to the new tiles.

**The `--perego-*` design-token alias bridge was restored in `perego-wordpress-adapter.scss`, not by
re-importing the legacy stylesheet.** The perego-site block CSS (shared media/gallery lightbox + card
chrome) consumes `--perego-*` tokens, but the only file defining them (`--perego-*: var(--wp--custom--
perego--*)`) was `perego-legacy-pre-recovery.scss`, which the round-6 rebuild stopped importing — so
every `--perego-*` var was undefined at runtime. Most visibly, `.lightbox { z-index: var(--perego-z-
lightbox) }` became `auto` and the dialog rendered *below* the sticky header/nav/preloader. Re-importing
the whole legacy file would reintroduce its conflicting component rules, so only the `:root` alias block
was ported into the client adapter (where "neutralize WP/CoreX interference" changes belong, since
`perego-reference.scss` is the immutable handoff stylesheet). Values remain single-sourced in theme.json.

**The individual card's native-`<button>` chrome reset moved from the block's `style.scss` to the theme
adapter.** The lightbox-trigger card renders as a `<button>` (handoff C-04), but its reset lived in
`clients-carousel/style.scss`, which the JS build never imports — so no block stylesheet is emitted and
the UA `2px outset` border shipped to production. The reset now lives beside the token bridge in
`perego-wordpress-adapter.scss`; the block `style.scss` is reduced to a pointer comment. The card's
`1fr / 42%` grid (text left, thumbnail right) was already correct in `perego-reference.scss`.

## 2026-07-19 — Spec 020 round 7: "What we do" media gets a fixed aspect-ratio, not natural sizing

**`.svc-whatwedo__media` crops to `aspect-ratio: 7/5` instead of the handoff's natural per-image
sizing.** The handoff itself has no fixed box for this image — each service's mockup renders at its
own natural ratio (video-editing 1.40, motion-graphics 1.38, graphic-design/website-making 1.63),
so the section is a genuinely different height per page even in the reference design. Owner wants
the 4 service singles visually consistent, which the handoff's own markup doesn't guarantee — chose
`7/5` (≈1.4) since it's closest to 3 of the 4 images' natural ratio, minimizing crop on those and
accepting a small side-crop on graphic-design/website-making's wider image, over stretching or
letterboxing. `website-making` intentionally still uses its own distinct "web showcase" grid
(browser-chrome cards + filters) for its Selected-work section, confirmed with owner — not migrated
to the shared `.work-masonry`.

## 2026-07-16 — Spec 020 round 6: full design-parity decisions

**One lightbox instance: the project gallery's embedded dialog was removed, not skip-listed.** The
handoff's GlobalMediaLightbox contract mandates exactly ONE dialog per page, opened by (among others)
the project-gallery thumbnails. The project-gallery-lightbox block's own Interactivity dialog
double-opened alongside the site-wide media-lightbox (both bound the same `data-gallery` thumbs).
Rather than teaching the global binder to skip that region (two dialog implementations forever), the
block now renders thumbs only — plain global-lightbox triggers with a new `data-gallery-index` attr the
global dialog honours as its opening position. The block's view.js/style.scss are gone; the shared
chrome lives once in `src/Blocks/shared/_lightbox-chrome.scss`.

**Lightbox chrome rebuilt on real tokens.** The two lightbox stylesheets referenced `--perego-*`
custom properties defined only in the dead `perego-legacy-pre-recovery.scss` — undefined at runtime
(z-index/radius/colors silently invalid). The shared chrome now uses the live theme.json tokens
(`--wp--custom--perego--*`, presets) and matches the reference design exactly (52px accent nav circles
±70px outside in gallery mode, 44px accent close at −14/−14 with glow, 8px dots with a 22px accent
active pill, visible "n / total" counter). Embed/video autoplay is muted (handoff C-03 — "no autoplay
with sound").

**Owner-approved client interactivity overrides handoff C-05's default-off.** The docs default
corporate tiles to non-interactive pending Perego approval; the owner explicitly requested the
prototype behaviour (corp tile → 4-item mixed gallery, individual card → ▶ lightbox). Demo media is
backfilled by `scripts/seed-client-media.php` (set-only-when-absent, EN+AR, shared attachments).
Individual cards follow C-04: embed/upload = a `<button>` lightbox trigger only; external = a plain
new-tab link with NO play affordance (▶ is reserved for in-lightbox media).

**Projects gained `_perego_video_url`; the card variant rule is video > gallery > image.** Matches the
handoff's masonry mix (`data-video` cards carry the always-visible `.play-btn`; galleries the zoom +
badge; images the zoom). AR reads the EN post's value via the established `metaWithEnFallback`.

**Demo fill = 23 projects per masonry category, distribution from one shared lib.**
`lib-project-variants.php` fixes position→variant (3=gallery; 8/18/21=image; rest video with the
prototype's own placeholder embed) so seed-projects/seed-project-galleries/migrations never disagree.
Image-variant demo projects deliberately get no gallery meta (a >1 gallery would re-type their card);
their demo singles show no gallery grid — acceptable placeholder trade-off. Work-archive + adjacent
caps bumped 60→120 (bounded); the archive keeps filters with no pagination (the handoff's pagination
is a static placeholder).

**Header "Contact Us" is the bare `#contact` fragment, bypassing `localizedUrl()`.** Every page's
footer carries `id="contact"`; localizing the fragment would absolutize it to the homepage and break
the same-page anchor. Fragments are language-neutral, so the renderer skips the driver for `#…` hrefs.

**Journal single completed with core-block composition + one new block.** Hero avatar chip and the
author-box are core blocks (avatar/post-author-name/post-author-biography) bridged onto the reference
classes in the adapter stylesheet; "Related articles" is a new server-rendered `perego-theme/related-posts`
block (JournalRepository clones ProjectRepository::relatedFor's same-category + locale + fill
discipline). Journal cards still never open the lightbox (handoff §8.2).

## 2026-07-16 — Spec 020 round 5: service-page parity decisions

**`svc-whatwedo` styling lives in the WP adapter, not by renaming markup to `.whatwedo`.** The reference
stylesheet is an immutable handoff copy; the seeded post_content + `ServicesOverviewRenderer` already ship
`.svc-whatwedo` classes in editors' saved content across 8 posts. Re-styling the existing WP class names in
`perego-wordpress-adapter.scss` (values copied from the reference `.whatwedo*` rules) fixes both surfaces with
zero content migration; renaming classes inside saved `post_content` would have needed another migration for a
purely cosmetic equivalence.

**Process fix = adapter CSS overrides, not a seed-markup migration.** WP's emitted
`.wp-container-core-group-is-layout-*` rules (one class) lose to the adapter's two-class selectors
(`.process .process-list`) regardless of order, so the seeded groups keep their editor-canvas flex layouts
and no editor content is touched. Only the per-service icons needed a content migration
(`migrate-service-process-icons.php`) — src-swap anchored inside `process-step__icon` figures, theme-owned
`icon-*.png` srcs only, so an editor-picked media-library icon is never overwritten. A NEW migration script
(not an extension of `migrate-service-process.php`) because that script's idempotence signature
(`contains 'process-list'`) is now true for every post — it stays a completed one-shot.

**One shared masonry component for the service singles and the archive.** The `.m1`–`.m15` mosaic, brand
card, and Load-more are one designed contract; `ServiceSelectedWorkRenderer::masonry()` is that single
source and `ServicesOverviewRenderer` receives the renderer via constructor injection. m-classes are
assigned sequentially, so with today's 2–3 seeded projects per category the mosaic renders a partial first
band and grows into the full design as real projects are added — we deliberately did NOT bulk-seed 15+
placeholders per category (owner supplies real work; the web category is the exception below).

**Web-showcase site type/URL are project meta on the EN post; AR reads through an explicit fallback.**
Polylang Free doesn't sync custom meta. A live site's type and URL are language-neutral data, so
`ProjectRepository::toWebCard()` falls back to the linked EN post's meta instead of mirroring values onto AR
posts — one source of truth, and an editor updating the EN URL fixes both languages. The 6 handoff demo
sites (Aurora Retail …) were seeded EN+AR because the showcase's filter pills need type coverage and the
handoff itself ships them as its demo showcase; their Latin brand titles stay Latin in AR, as brands do.
The showcase renders filter pills only for types present in the data (never a pill that empties the grid)
and hides the group below two types.

**Reveal animations restored globally; seeded prose intentionally not given `.reveal`.** The theme now adds
the `.js` root class + the handoff's IntersectionObserver (reduced-motion + no-IO safe). Renderer-emitted
elements carry `.reveal` as designed; retro-adding `.reveal` wrappers to seeded whatwedo/process post_content
would mean another content migration for a purely decorative delta — skipped on purpose.

**Home sections at 100vh/100svh ≥901px is an owner-requested deviation from the reference.** The handoff
gives `.home-about` 78vh and services-teaser/clients natural padding heights; the owner asked for
full-viewport sections "when possible". Scoped to `.home` + desktop only (a forced 100vh band around short
content reads as empty space on phones), with the `100svh` progressive pair everywhere `100vh` is used.

## 2026-07-14 — Homepage editable seam: teaser ← Service CPT meta; hero ← block attributes (spec 012 T002)

**Context**: Spec 012 T001 audit found the homepage renders to the handoff visually, but hero slides/CTA and
the services-teaser (title, "See All", four cards) are hardcoded in `HomeContent::COPY` (EN+AR) — not
WordPress-editable. About (`post-content`) and Clients (Client-CPT `WP_Query`) already edit. The teaser card
presentation — short **two-line** label ("Video<br>Editing"), a teaser card image (`card-*.png`), and a
teaser alt — is semantically distinct from the Service post's title ("Video Editing & Post-Production") and
featured image, so a naive "use post title/thumbnail" rebind would be a visual regression (violates FR-4).

**Decision**:
- **Services teaser (T003) ← `perego_service` CPT, keyed by slug.** Register teaser-presentation post meta on
  `perego_service`: `_perego_teaser_label` (short card label, may hold the two-line form),
  `_perego_teaser_image_id` (attachment id for the card image), `_perego_teaser_alt` (card alt). Seed them
  idempotently from `HomeContent` + the handoff `card-*.png` assets. `ServicesTeaserRenderer` queries the four
  published Services for the current Polylang locale in fixed order, reads the teaser meta, and falls back to
  the `HomeContent` seed **per field** when a post or its meta is absent — output stays byte-identical and
  non-fatal. Extend spec 010's `PostMetaBoxes` with the teaser fields (registered editor controls). Teaser
  heading + "See All" stay as the `HomeContent` seed default, exposed as editable in a later pass (lower
  priority than the cards).
- **Hero (T004) ← block attributes on the hero-slider block.** The hero is homepage-level (not a CPT), so the
  FSE-native seam is the block's own attributes (slides: title/text; plus CTA), seeded from `HomeContent`,
  edited in the block editor. Requires a `wp-scripts` build. Sequenced after T003.
- **`HomeContent::COPY` becomes the seed/default only**, never the sole live source — mirrors the spec 009
  block-first, seeded-then-editable pattern. No `perego_section` CPT revival, no private CoreX duplicate; a
  missing CoreX capability is logged in `docs/corex-framework-gaps.md`, not forked.

**Why**: Matches the program's "CPTs only for Services/Projects/Clients + registered editor controls for
structured metadata" and "global content through FSE blocks/template parts" while guaranteeing zero visual
regression via per-field seed fallback. Keeps the teaser a projection of the Service CPT (edit a Service →
teaser updates), the architecturally consistent choice next to the already-CPT-driven Clients carousel.

**Status**: adopted (T002). Implementation (T003 meta+renderer+seeder+PostMetaBoxes+Pest) pending — gated on
verification (Pest + wp-cli seed + EN/AR visual diff) since it must not ship unverified.


## 2026-07-14 — CoreX admin bundle build gap (framework/deployment, not a Perego defect)

**Decision**: The reported "CoreX dashboard does not expose Forms/Submissions/Data Models" is caused by the
CoreX admin React bundle (`plugins/corex-config/build/admin/index.js`) being **unbuilt** — it 404s, so the
admin pages render their PHP heading shell but the interactive app never mounts. Fixed by building it with
the repo's hoisted `wp-scripts` (a deployment step; `build/` is gitignored; **no framework source edited**).
This is a **CoreX/deployment gap**, not a Perego client defect: the deployment pipeline must build CoreX
admin assets (`npm run build --workspaces` or per-plugin build). No private Perego duplicate was created.

**Why**: Client Site Mode forbids editing framework internals, but building already-present framework assets
to make the local runtime functional is a runtime/deployment action with no committed framework changes. The
program explicitly lists "build/rebuild required admin assets" and "correct deployed CoreX/runtime mismatch"
as spec 010 objectives. Backend REST (82 routes) and the admin menu were already healthy; only the built app
was missing.

**Status**: corex-config admin bundle built + serving 200 locally. **Durable action for the owner/deploy:**
ensure the deploy builds CoreX admin assets (they are gitignored, so this local build does not persist in
git). Tracked in `specs/010-.../evidence/runtime-audit.md`.

## 2026-07-14 — Runtime resolver + autonomous continuation (Final Completion Program)

**Decision**: Canonical runtime is the local WAMP install at `http://perego.local/` (Chromium
host-resolver-rules map it to 127.0.0.1; `PEREGO_LIVE_BASE` overrides). All DB inventory/dry-run/backup/
migration run via wp-cli against `wp/` (siteurl/home = perego.local — same DB). The ngrok mirror
`mower-hamstring-baggy.ngrok-free.dev` is an optional second host; use it when reachable, otherwise fall
back to perego.local; never stop because ngrok is offline. Do not destructively change WP home/siteurl to
switch hosts. Work continues autonomously through specs 009–018 in dependency order without pausing between
tasks/specs; stop only for a genuine external blocker, after finishing other unblocked work and updating
durable memory + commit/push.

**Why**: The goal names `peregos.local`, but the real local vhost + hosts entry is `perego.local`; the
established visual/interaction tooling already targets it robustly. Migrating the WAMP DB (wp-cli) while
verifying visuals on the same host keeps DB and screenshots consistent. ngrok is a tunnel/mirror, not a
proven-separate production DB.

**Status**: adopted; recorded in roadmap + PROGRESS RESUME HERE + spec 009 evidence.

## 2026-07-14 — Final Completion Program: block-first global content; remove the Global Sections CPT (spec 009)

**Decision**: Adopt the Final Completion Program (specs 009–018). Global content becomes block-first,
FSE-editable; the `perego_section` ("Global Sections") CPT and all its code/records are removed after a
safe, dry-run-gated, idempotent migration. Spec 009 branches off the current recovery tip
(`feature/008-visual-fidelity-recovery` @ `1b7ecda`) as `feature/009-cms-block-architecture-cleanup`, so
no unmerged recovery work is lost; each later spec gets its own branch/PR.

**Why**: The Global Sections CPT is a hidden content store the block-first rule forbids for global
fragments, and the read-only dry-run proved it is mostly inert — of 7 seeded roles (×EN/AR = 14 records),
**only `footer-careers` renders on the frontend**; the real header/footer/CTA/404 copy renders from PHP
providers. So the architecture is both wrong and largely unused, and removal is low-risk once
`footer-careers` gets an editable home. Branching off 008 (not `main`) avoids discarding the substantial,
unmerged spec-008 recovery.

**Status**: foundation only. Migration reporter (`scripts/migrate-global-sections.php`, dry-run) built and
run against the **local** DB (0 anomalies). NOT yet done: live-DB dry-run (deployed-commit identity
unproven), backup gate, footer-careers editable surface, CPT/code removal, cleanup report. Destructive
`apply` is intentionally unimplemented until the backup gate passes. This supersedes Decision 8
(2026-07-12, which introduced the CPT) — that architecture is now being removed.

## 2026-07-13 — Serve the locked handoff fonts locally

**Decision**: Perego serves Open Sans weights 300/400/600/700 and Cairo weights 400/600/700 from
`perego-theme/assets/fonts/`, rather than enqueueing Google Fonts or its preconnect hints.

**Why**: The full browser matrix exposed one external Google Fonts request as a broken resource on every
normal route when external network access was denied. The locked handoff requires those families but does
not include font files; the open-licensed Fontsource packages provide the exact required WOFF2 assets.

**Status**: verified: the local fonts preserve the theme's existing font-family tokens and the full
72-route EN/AR matrix now has zero broken resources. This is a client-theme asset change only; no CoreX
framework code changed.

## 2026-07-12 — Final handoff is the locked visual authority; design-fidelity recovery is the active work

**Decision**: `_design_handoff/Perego-Creative-Studio-Final-Handoff/site/` is the single binding visual,
responsive, interaction, content, and asset reference for Perego. The active client feature is
`sites/perego/specs/004-design-fidelity/` on branch `feature/004-design-fidelity`.

**Why**: The delivery audit established that substantial functionality exists, but specs, progress records,
and exact visual acceptance are out of sync. Existing route-health and accessibility checks are valuable but
do not prove pixel-level parity with the handoff. Future UI work must be evidence-led, route-scoped, and
compared with the handoff before it can be marked complete.

**Consequences**: No redesign, substitute styling, invented imagery, or new interaction may be introduced.
Missing owner-supplied business content, production assets, or legally approved copy is a launch blocker, not
permission to invent a substitute. Older status notes remain historical evidence; the authoritative delivery
queue is `PROGRESS.md`'s recovery section and Spec 004's task register.

Record each non-trivial decision (context · decision · why · status).

## 2026-07-11 — This repo is a CoreX checkout with the client site nested at sites/perego/

**Context**: initially set up as two separate directories (a standalone CoreX checkout at
`C:\wamp64\www\corex`, and an independent WordPress install at `C:\wamp64\www\perego` referencing it
via cross-directory junctions) — a real test of CoreX, so a real fork-and-build workflow, not a
convenience shortcut.

**Decision**: `C:\wamp64\www\perego` is instead a full CoreX framework checkout in its own right —
`origin` = `git@github.com:MustafaShaaban/perego.git`, `upstream` = `git@github.com:MustafaShaaban/corex.git`
(merged at `v0.33.0`, `--allow-unrelated-histories`). The client site is generated at `sites/perego/`
inside it, exactly matching CoreX's own documented `sites/<client>/` convention. `C:\wamp64\www\corex`
(the separate checkout) is not referenced anywhere in this project.

**Why**: this is meant to validate the framework's real intended consumption model — fork, build a
client site under `sites/<client>/`, and be able to pull framework updates via `git fetch upstream` —
not a shortcut that happens to boot a site.

**Status**: done. Follow-on: pulling future `upstream` updates should go through the same merge
pattern used here (fetch, merge `--allow-unrelated-histories` only needed for this first merge since
histories were unrelated; ordinary `git merge upstream/main` going forward).

## 2026-07-11 — Client feature work uses CoreX's native Spec Kit, not ad hoc plans

**Context**: the environment bootstrap above was executed via hand-written PowerShell + ad hoc task
tracking, before this checkout had CoreX's own Spec Kit scaffolding available to it.

**Decision**: every feature from here on (`M2` onward per the design doc) goes through
`/specify → /clarify → /plan → /tasks → /implement`, the same flow `COREX-SPECKIT-START.md` and this
repo's own `AGENTS.md`/`CLAUDE.md` mandate, with the guard skills as the quality gate.

**Why**: this is a real test of CoreX's own workflow, not just its code — using a different planning
system for the client site than the framework itself uses would defeat that.

**Status**: done for this repo's tooling availability; each milestone spec still to be written.

## 2026-07-11 — perego-site tests need their own Pest config + ABSPATH-defining bootstrap

**Context**: the root `phpunit.xml.dist` only covers the framework's own `tests/Unit` — a client
site's Pest suite needs its own config. Running the `--starter` example's `ExampleTest.php` through a
naive bootstrap (autoload only) produced **zero output and exit code 0** — no error, no failure, just
silence. Traced it (expensive — looked like a PHP crash at first) to every generated `PeregoSite\*`
class carrying `defined('ABSPATH') || exit;` (the same direct-access guard convention as Corex's own
classes, see the root repo's `DECISIONS.md` #20). Outside WordPress, `ABSPATH` is undefined, so the
guard's `exit;` fires — silently, since bare `exit` with no argument prints nothing.

**Decision**: added `sites/perego/perego-site/phpunit.xml.dist` (own `testsuite`, bootstrap) +
`tests/bootstrap.php` that defines `ABSPATH` (and requires the root Composer autoloader) before any
`PeregoSite\` class loads — mirroring the root `tests/bootstrap.php` pattern exactly, just scoped to
this client site.

**Why**: this is the established framework convention, not a bug to work around differently; matching
it exactly keeps client tests consistent with how the framework tests itself.

**Status**: done. Run via `cd sites/perego/perego-site && php ../../../vendor/bin/pest`.

## 2026-07-11 — Polylang installed + active; languages not yet configured

**Context**: spec 001 T027 — attempt installing Polylang to validate `PolylangLanguageDriver`
against the real plugin, not just Brain Monkey stubs.

**Decision**: `wp plugin install polylang --activate` succeeded (v3.8.5). `LanguageService` correctly
auto-detects it (`function_exists('pll_current_language')` is now true) and resolves
`PolylangLanguageDriver` in the live environment — verified via `wp eval`. Polylang has **no
languages configured yet** (`pll_current_language()` returns `false`, which our driver already
handles by defaulting to `'en'`) — it exposes no simple public API for programmatic language setup
(`PLL()->model` has no public `add_language()`); that's normally done through its own wp-admin setup
wizard (Languages → Add New Language), not something to reverse-engineer via WP-CLI.

**Why**: forcing Polylang's internal, undocumented setup path is riskier than just doing the one-time
admin-UI step a real site owner would do anyway.

**Status**: follow-up needed before real bilingual content authoring: visit `/wp-admin/admin.php?
page=mlang` and add English (default) + Arabic. Not blocking for spec 001 — the language *mechanism*
(driver resolution, toggle, persistence) is fully built and tested either way, per the driver's own
graceful default.

## 2026-07-11 — `upstream` (corex) is fetch-only, never push, from this project

**Context**: owner directive: "corex repo should be just a starting point and updates, DON'T EVER PUSH
TO COREX REPO FROM PEREGO PROJECT."

**Decision**: `git remote set-url --push upstream DISABLED_DO_NOT_PUSH_TO_COREX` — a hard technical
guardrail, not just a remembered rule. Any accidental `git push upstream` now fails immediately (no
such remote URL) instead of silently succeeding. `upstream` exists **only** to `git fetch`/`merge` in
framework updates; all pushes from this project go to `origin` (`MustafaShaaban/perego`) only.

**Why**: corex is the shared framework other projects also build on; this client site must never be
able to write to it, even by accident (wrong remote name typed, muscle memory from another repo, etc).

**Status**: done. Verify with `git remote -v` — `upstream`'s push URL must always show the disabled
placeholder, never a real one.

## 2026-07-11 — M3 needs pretty permalinks + .htaccess (and a Git-Bash path-conversion gotcha)

**Context**: the `project` CPT (M3) has a `work` archive + per-project singles, which need pretty
permalinks. The dev install shipped with plain permalinks and **no `wp/.htaccess`**, so every
CPT URL 404'd (Apache had nothing routing `/work/` to `index.php`).

**Decision / environment steps** (runtime only — `wp/` is gitignored, so none of this is committed;
reproduce on any fresh install):
1. `wp eval 'update_option("permalink_structure","/%postname%/"); flush_rewrite_rules(true);'
   --path=wp` — **not** `wp rewrite structure '/%postname%/'`: Git Bash's MSYS runtime rewrites a
   leading-slash CLI argument into a Windows path (`/%postname%/` → `/C:/Program Files/Git/%postname%/`),
   silently corrupting the permalink structure. Setting the option via `wp eval` (no leading-slash
   arg on the command line) avoids the conversion. (General rule for this repo: never pass a bare
   `/...` value as a wp-cli positional arg from Git Bash; use `wp eval`/`update_option`, or prefix
   with `MSYS_NO_PATHCONV=1`.)
2. Created `wp/.htaccess` with the standard WordPress `mod_rewrite` block. The perego vhost already
   has `AllowOverride All` + `mod_rewrite` loaded, so it took effect immediately.

**Also**: seeded 9 placeholder example projects + the 4 category terms via
`sites/perego/perego-site/scripts/seed-projects.php`. Run it with
`wp eval 'require "sites/perego/perego-site/scripts/seed-projects.php";' --path=wp` (the direct
`wp eval-file` path hits a wp-cli quirk with the script's `use`/`WP_CLI::log` ordering; the `require`
form works). These are the handoff's PLACEHOLDER projects ("Sample Client") — replace with Perego's
real work before launch; do not invent results/metrics (CONTENT_MODEL.md).

**Status**: done for this dev environment. The owner's environment + the M7 deploy pipeline must apply
the same permalink structure + `.htaccess` (WordPress writes the latter automatically when permalinks
are saved through wp-admin).

## 2026-07-11 — M2 asset build pipeline (the blocks were never compiled)

**Context**: M1 shipped the header/footer/preloader blocks with `block.json` referencing raw
`style.scss` + an ESM `view.js` (Interactivity API). Browsers can't load either — so **no block CSS
and no interactivity actually loaded**, and the theme's `main.scss`/`main.js` were never compiled to
`assets/css/main.css` / `assets/js/main.js` at all. The site rendered unstyled server markup; that's
the real reason M1's "visual fidelity unverified" gap existed. M2 (the first milestone with visible
front-end that must look right) cannot ship without fixing this.

**Decision**: stood up the compile step (Node 20+ is available; `@wordpress/scripts` + `sass` resolve
from the repo-root `node_modules`):
- **Blocks**: `wp-scripts build --experimental-modules --webpack-src-dir=src/Blocks
  --output-path=build/Blocks` compiles each block's `style.scss → style-index.css` and, for the
  Interactivity API blocks, `view.js →` a real script **module** with a `view.asset.php` declaring the
  `@wordpress/interactivity` dependency (WP core provides that module + the import map). The
  `--experimental-modules` flag is required in wp-scripts 32.x to build `viewScriptModule` fields.
  Each source `block.json`'s `style` now points at the built `style-index.css` (create-block
  convention). `register_block_type` resolves `build/Blocks/<name>` via a new
  `PeregoSiteServiceProvider::blockDir()` helper, falling back to `src/Blocks/<name>` pre-build so
  registration/markup still works (only the compiled assets are absent) until a build runs.
- **Theme**: `sass main.scss → assets/css/main.css` (the `--perego-*` token :root that every block's
  CSS depends on) + `wp-scripts build assets/src/js/main.js`.
- **Committing**: compiled output is **gitignored** (`sites/perego/.gitignore` + the root's
  `**/build/`), matching the repo's "compiled assets are generated, never committed" rule. Built for
  local dev now; the M7 deploy pipeline (`dist/`) will build for production.

**Why**: this is the standard WordPress block toolchain; using `--experimental-modules` keeps the
Interactivity API code (the design doc's mandated approach) rather than rewriting all three blocks to
plain view scripts. Verified end-to-end over HTTP: the homepage enqueues all block styles + the theme
token base, the import map resolves `@wordpress/interactivity`, and all three view modules load.

**Status**: done for M1+M2 blocks. Follow-up: wire `npm run build` (both packages) into the M7 deploy
pipeline so production gets compiled assets without a manual step.

## 2026-07-11 — Jest for perego-site gets its own config, not a root jest.config.js edit

**Context**: spec 001 T013/T023/T031 — the three Interactivity API blocks' `view.js` files had no
automated JS coverage, only manual verification. `@wordpress/interactivity` (the module `view.js`
imports for `store()`/`getContext()`/`getElement()`) is a WordPress runtime script handle, not an
installed npm package — Jest can't resolve it without a mapping, and the root framework's
`jest.config.js` has no such mapping (nothing in the framework uses the Interactivity API's `store()`
this way yet).

**Decision**: added `sites/perego/perego-site/jest.config.js` (extends the framework's
`@wordpress/scripts` jest-unit preset, scoped to this plugin via its own `rootDir`) +
`tests-js/wp-interactivity-mock.js` (a minimal test double: `store()` records its config under the
namespace so a test can pull the real `actions`/`callbacks` back out; `getContext()`/`getElement()`
return whatever the test last set). Did **not** edit the framework's root `jest.config.js` to add the
same mapping there, even though that would let the monorepo-wide `npm run test:js` also pick these
tests up cleanly — that file is framework tooling, out of bounds for Client Site Mode.

**Why**: keeping the fix entirely inside `sites/perego/perego-site/` respects the Role Gate boundary
(don't edit CoreX framework internals from client-site work) even though it leaves a rough edge: running
`npm run test:js` from the repo root now discovers these three test files (root's `jest.config.js` only
excludes `wp/` and `docs-app/`, not `sites/`) and fails to resolve `@wordpress/interactivity` for them.

**Status**: done for this site — 25/25 Jest tests green via
`npx jest --config sites/perego/perego-site/jest.config.js --rootDir sites/perego/perego-site`.
Follow-up (framework side, not this site's to fix): add `<rootDir>/sites/` to the root
`jest.config.js`'s `testPathIgnorePatterns`, matching the existing `wp/`/`docs-app/` exclusions, so the
root aggregate command doesn't reach into per-site test configs it can't resolve.

## 2026-07-11 — Autonomous implementation run: repair-in-place, editor-first CPTs, Polylang Free

**Context**: `PEREGO_IMPLEMENTATION_PROMPT.md` drives an end-to-end completion pass on the latest
compatible CoreX baseline.

**Decision 1 — Repair in place, not clean restart.** The `sites/perego/` client layer already follows
CoreX + FSE correctly (FSE blocks, container-wired renderers, real CPTs, tests, guards; no framework
pollution). Continue building on it rather than regenerating via `make:site`. Evidence + trigger-by-trigger
analysis in `docs/decision-repair-vs-restart.md`.

**Decision 2 — CoreX baseline already current.** `v0.33.0`/`71639e7` is the latest stable tag and is an
ancestor of HEAD; `upstream/main` `ff61bf0` is also already merged. No re-sync needed this run; recorded in
`docs/corex-baseline.md`. Recovery checkpoint `recovery/2026-07-11-pre-impl` pushed before structural work.

**Decision 3 — Editor-first service content.** The four `perego_service` posts store their "What we do" /
"Our Process" prose as real block markup in `post_content` (editable on the canvas), rendered by
`single-perego_service.html` via `core/post-content`. Only structural chrome (eyebrow, H1, tabs) is a
dynamic block (`perego-theme/service-hero`). ServiceContent is the seed default, not a runtime prose
source. **Why**: satisfies the prompt's editor-canvas rule and is the pattern to follow when retrofitting
the Home/portfolio surfaces (which still render prose from PHP providers — tracked remediation).

**Decision 4 — Polylang **Free** slug strategy: never share slugs.** Slug-sharing across translations is
Pro-only. The seeders let WordPress give the AR post a distinct slug (`video-editing-2`), namespaced by the
`/ar/` directory prefix, and keep the canonical service key in `_perego_service_slug` meta (read by the
hero block). We explicitly do **not** force a shared slug via `$wpdb` — an earlier attempt did, and it made
AR URLs 301-collapse onto EN, exactly the Pro-only assumption the prompt forbids. **Why**: the build must
pass with zero Pro dependency.

**Decision 5 — Polylang `/ar/` rewrite flush is a documented admin step.** Polylang registers its
directory rewrite rules only on a real admin/browser request, never under `wp-cli`/`wp eval` (verified:
`wp rewrite flush`, deleting `rewrite_rules` + front-end regeneration, and manual
`PLL_Links_Directory::rewrite_rules` re-registration all leave 0 language rules). So the one-time
**Settings → Permalinks → Save** step is documented in `docs/multilingual-guide.md` rather than worked
around with any Pro-only or hacky mechanism. All translation data/config is complete and correct without it.

**Status**: services surface (singles) + Polylang EN/AR configuration + bilingual service seeding shipped,
verified live, committed (`114a0b4`, `c902fd5`, `5017611`, `edd1f12`, `31b07ea`) and pushed. Remaining
scope tracked in `PROGRESS.md` "Next".

**Decision 6 — Client forms register into CoreX Forms via the app container.** `corex-forms` exposes no
filter/hook for third-party form registration and its `FormsServiceProvider::registerForms()` hardcodes the
example form. Perego forms (`QuickMessageForm`, `ProjectBriefForm`) therefore register into the shared
`FormRegistry` singleton resolved through the public `\Corex\Boot::app()->container()` accessor, on `init`,
guarded so the site degrades when `corex-forms` is inactive. The engine's default listeners (store + email)
are shared across forms, so registering after boot still delivers. **Why**: this is client-site composition
using a public accessor, not an edit to framework code (Role Gate: Client Site Mode).

**Decision 7 (RESOLVED 2026-07-12) — Join-us CV upload + branded emails path.** Resolved with a
platform-safe bespoke endpoint that does **not** hard-depend on the inactive add-ons. `PeregoCareersController`
(`POST perego/v1/careers/apply`) validates the CV with WordPress's own `wp_check_filetype_and_ext` + a `finfo`
content sniff (never trusting the browser MIME) under the exact CoreX Careers policy (pdf/doc/docx ≤ 5 MB),
stores it as a **private** attachment via `wp_handle_upload`, and — **best-effort only** — records the
application against a standing "Open Application" `corex_job` through `ApplicationStore` **when `corex-careers`
is active** (guarded; the email is the primary record otherwise). The public form is anonymous, so it is gated
by a honeypot + per-IP rate limit rather than a nonce (a `wp_rest` nonce cannot authenticate anonymous
visitors). Branded EN/AR confirmation + admin-notification emails go through the already-shipped
`PeregoMailer`/`PeregoEmailRenderer` seam (Phase 7/8 email commits), not the deferred `corex-email` wiring.
**Why this over the earlier options:** it neither pulls in the full careers CPT/table footprint as a hard
dependency (the option-a concern) nor reinvents upload security (the option-b concern) — it reuses core's
upload/filetype APIs and degrades cleanly when either add-on is absent. `scripts/seed-careers.php` idempotently
seeds the standing job when Careers is present. _Original open context preserved below for the record._

**Decision 7 (superseded open context) — Join-us CV upload + branded emails path.** The remaining Phase 7 items
depend on capabilities not currently active:
- *Join-us / CV form*: `corex-forms` has no `file` field type (its `FieldTypeRegistry` built-ins stop at
  text/email/phone/select/…). Adding one is CoreX **Framework Mode** work, out of Client Site Mode.
  `corex-careers` ships the intended secure upload path (`UploadValidator` spec 012 + `ApplicationService`)
  but is job-centric and inactive. Options: (a) activate + adapt `corex-careers` (general "open application"
  job) — spec-preferred but pulls in the careers CPT/table/status-flow footprint; (b) a bespoke client-side
  upload endpoint — reinvents platform security (discouraged by wp-guard); (c) request a CoreX Framework
  Mode task to add a first-class `file` field to `corex-forms`.
- *Six EN/AR branded emails*: need `corex-email` activated and wired to the `RoutedMailer`/
  `MailTemplateCatalog` seam (the `SendEmailListener` already routes `forms.<slug>.submitted`); until then
  the engine uses the `wp_mail` fallback to the admin address.
Both change the site's active-plugin footprint and/or mail behaviour — **held for owner direction.**

**Decision 8 (2026-07-12) — `perego_section`, not `perego_global_section`, for the global-content CPT.**
The implementation prompt suggests `perego_global_section` (its "such as" wording allows latitude), but
WordPress hard-limits `register_post_type` names to **20 characters** and `perego_global_section` is 21 —
the CPT registered as a silent no-op (no error, no post type). We renamed it to **`perego_section`** (14
chars), keeping the `perego_` prefix and the meaning, and added a unit test asserting the name stays ≤ 20 so
it cannot regress. **Why**: a 21-char name is not a style choice, it is a WordPress constraint; the shorter
name is the only correct option. Roles are carried in `_perego_section_role` meta (not slugs), and the `404`
role key is stored as `not-found` because a purely-numeric array key (`'404'`) coerces to int in PHP and
would break string role comparisons.

**Decision 9 (2026-07-12) — Polylang-Free translatable CPT via `pll_get_post_types`; strict no-mix render.**
`perego_section` is declared translatable through Polylang's **Free** `pll_get_post_types` filter (a public
WordPress.org API — no Pro dependency), so linked EN/AR records resolve by language and the editor gets the
normal language + translation-linking UI. The `GlobalSectionRenderer` enforces a hard **no-language-mixing**
rule: a role renders only from a record in the *exact* current locale — a missing translation shows an inline
notice to editors and **nothing** to visitors, never the other language's copy. This satisfies the prompt's
"fallbacks that do not mix interface languages" and "missing translations must be visible to administrators
and covered by tests" without any Pro-only synchronization/duplication feature. Verified live: the header
EN/AR pair links `{"en":72,"ar":73}` and the block renders per-language content correctly.

**Decision 10 (2026-07-12) — Language switcher is real navigation, not a JS toggle.** The prototype's
JS-only language toggle (swap `lang`/`dir`, persist a cookie, `window.location.reload()` the same URL) is
explicitly forbidden by the implementation prompt, and it never reached the translated entity. The header
switcher now renders **server-side anchors to `LanguageDriver::urlFor($locale)`**: under Polylang each is the
real translated URL (`/ar/…` via `pll_the_languages(raw)`), so switching is genuine navigation that works with
no JavaScript; the current locale is a non-link `aria-current` marker. A new driver-contract method
`managesLanguageViaUrl()` (Polylang `true`, fallback `false`) drives a `data-lang-url-managed` flag: the
view-script now touches language state **only** in the fallback mode (mirror the cookie into `<html>` on load,
persist each switch click for cross-page memory) and never applies a stale cookie under Polylang. To make the
no-Polylang fallback switch without JS too, `FallbackLanguageDriver::currentLocale()` now honors a `?lang=`
query var (authoritative for the request) ahead of the cookie. **Why**: it satisfies the prompt's "correct
current-language URLs / switch to the translated entity / no JS-only toggle / no Pro dependency" with real
Polylang Free URLs, degrades gracefully (a missing translation link falls back to home, never a mixed/broken
URL), and keeps EN/AR fully working when Polylang is absent.

**Decision 11 (2026-07-12) — Serve a real `/llms.txt`; add `x-default` to reuse, not duplicate, CoreX
readiness + Polylang hreflang.** For agent readiness (Phase 10) the prompt says to make CoreX's existing
readiness checks pass rather than build a parallel dashboard. CoreX's `ReadinessScorer` (corex-config Insights)
checks an `llms_txt` signal, so `PeregoAgentReadiness` serves a real `/llms.txt` on `init` (virtual, no file):
`text/plain` + `X-Content-Type-Options: nosniff`, built from the site name, the handoff tagline, the real
navigation routes, and the EN/AR language URLs — no fabricated claims. For hreflang, Polylang Free already
emits per-language `alternate` links, so we add only the missing **`x-default`** on `wp_head` to complete the
EN/AR/x-default set the handoff SEO model requires. **Why**: both reuse platform/framework facilities (WP
sitemap + robots, Polylang alternates, CoreX readiness) and add only the genuine deltas, keeping the site free
of duplicate SEO machinery. Verified live: `/llms.txt` 200 text/plain; `<head>` carries EN + AR + x-default.

<!-- language-switcher verification anchor --> Verified live: `/ar/` → `<html dir="rtl" lang="ar">`.

**Decision 12 (2026-07-12) — All Perego CPTs are Polylang-translatable; verification is headless + slug-aware.**
A headless verification pass (`scripts/verify-visual.mjs`, Chromium via Playwright) found `/ar/services/` and
`/ar/work/` returning **404**: `perego_service`, `perego_project`, and `perego_client` were never declared
translatable, so Polylang produced no `/ar/…` archive routes for them. Fixed by generalizing the
`pll_get_post_types` filter (`PeregoSiteServiceProvider::registerTranslatablePostTypes`) to register all four
Perego CPTs — a Polylang **Free** API, no Pro dependency — after which a rewrite flush made the AR archives
resolve 200. The verifier derives each Arabic URL from the page's own `hreflang="ar"` alternate rather than
prefixing `/ar/`, because Polylang Free de-duplicates AR slugs (the contact page's AR translation is
`/ar/contact-2/`, not `/ar/contact/`) — a naïve prefix tests the wrong URL and reports false failures. **Why**:
the fix restores real AR routing for the content types with the platform's own mechanism, and the slug-aware
verifier is a correct, reusable gate. Result: 24 checks, 0 hard failures; the only remaining items are honest
content gaps (AR Work/Journal translations) surfaced informationally, not as defects.

**Decision 13 (2026-07-12) — Client-side styling of server-rendered CoreX Forms (`.corex-form__*`).**
The CoreX Forms engine renders the contact "Start a Project" brief and the footer "quick message" form as
server-rendered `.corex-form__*` markup, but the framework ships those styles **only as a Gutenberg-block
stylesheet** (loaded conditionally when the *block* renders) and against framework tokens (`--ink`/`--surface`/
`--primary`) the Perego dark theme does not define — so on the client both forms rendered as raw, unstyled
browser controls, including a **visible honeypot** input. Fixed in **Client Site Mode** by styling `.corex-form__*`
in the client theme's `main.scss` with Perego tokens (12-col grid, underlined inputs, select/multi-select, submit
via the shared `.perego-btn`, off-screen honeypot, error/status states), and setting `width:half` on the paired
brief fields (a supported CoreX Forms field key) for the 2-column handoff layout. **Why**: the form's presentation
is a legitimate client concern; we did not touch the framework. The service-chooser toggle-buttons in the handoff
are adapted to the engine's native `<select multiple>` (a new field type would be framework scope).

**Decision 14 (2026-07-12) — Resilient re-declaration of the h1/h2/h3 font-size presets.**
WordPress 7.0.1 in this install **drops the `h1`/`h2`/`h3` font-size presets** from the CSS emitted by
`wp_get_global_stylesheet()`, so `var(--wp--preset--font-size--h1)` resolved to empty and every heading using it
fell back to 16px site-wide (legal title, services section titles, etc.). Verified deterministic, not a cache:
`theme.json` is correct, `WP_Theme_JSON_Resolver::get_theme_data()`/`get_merged_data()` **settings** both contain
the presets with the right clamp values, but the emitted stylesheet omits them (and core's `x-large`) while
keeping core `medium`/`large`; survives `clean_cached_data()` + transient purge in a fresh process; corex's
`wp_theme_json_data_theme` filter no-ops (no `brand.json`). Fixed by re-declaring the three presets in the theme's
`:root` (`main.scss`) from the **same** theme.json clamp values. **Why**: it makes the theme resilient to the core
quirk, keeps theme.json as the conceptual source of truth, and is harmless if a future core/build fix emits the
presets. The core emission quirk itself is worth a separate framework-mode investigation.

**Decision 15 (2026-07-12) — Locale-aware breadcrumbs on native FSE templates via tiny server blocks.**
`single.html` (journal post) and the search page had no per-page PHP renderer, so a locale-aware breadcrumb could
not be added as static FSE-template HTML without hardcoding English on the AR route. Added a small
`perego-theme/post-breadcrumb` server block (`PostBreadcrumbRenderer`, mirroring `JournalHeaderRenderer`) and
rendered the search breadcrumb inside `SearchResultsRenderer`, both language-aware via `GlobalContent`. **Why**:
consistent with the established server-block pattern; avoids the "hardcoded English on AR" bug class on
language-neutral templates.

**Decision 16 (2026-07-12) — UI-string i18n deferred to its own slice (launch blocker, not a 004 fix).**
Design-fidelity work surfaced that every AR route renders the header nav, form labels, and buttons in **English**:
there are no `.po`/`.mo` files and no `pll_register_string` calls for the `perego-site`/theme text domains, and
`SiteHeaderRenderer`'s nav labels are hardcoded English (not `__()`-wrapped). This is pre-existing (present on the
already-accepted AR home) and the handoff is English-only, so it is **not a deviation from the handoff** and is out
of spec 004's scope. **Decision**: track it as the top bilingual-launch blocker for a dedicated i18n slice rather
than absorb a large cross-cutting change into per-route design work; the approach (gettext `.po`/`.mo` vs Polylang
string translations) is an owner call. Recorded in `specs/004-design-fidelity/content-manifest.md`.

**Decision 17 (2026-07-13) — Single-post reading time as a locale-aware server block.** The handoff
single-post meta row (`single-post.html:71`, plus the `readTime` string in `content/{en,ar}.json`) shows a
"6 min read" estimate that no native WP block emits. Added `perego-theme/post-reading-time`
(`PostReadingTimeRenderer`), mirroring the Decision-15 breadcrumb pattern: it estimates from the post body
at 200 wpm (floored to 1 minute, unicode-aware word split so Arabic counts correctly) and renders the
localized label via `GlobalContent::readTime()` (EN "N min read" / AR "N دقيقة للقراءة"). **Why**: closes a
real 004 fidelity gap that was previously listed as a deferred "meta embellishment", using the established
server-block-for-locale-aware-native-templates pattern rather than a plugin. The label lives in
`GlobalContent` (locale-keyed array, like the other neutral-template strings), so it needs no `.po`/`.mo`
entry; the block's admin-only title/description follow the existing convention of being POT-tracked but not
AR-translated. The remaining meta embellishments (author avatar, "By" prefix, separator dots) stay deferred
as cosmetic.

**Decision 18 (2026-07-13) — Keep WordPress's default "Sample Page" out of the index/sitemap in code.**
The `page`-template route-health fixture is WordPress's auto-created `sample-page`, which still carries
the default lorem copy and was appearing in `wp-sitemap-posts-page-1.xml` and indexable (`blog_public=1`).
Rather than delete the fixture (needed for the `page`-route health check) or hand-edit content, added
`Seo\PlaceholderPageIndexing`: a `wp_robots` filter setting `noindex,nofollow` on that page and a
`wp_sitemaps_posts_query_args` filter dropping it from the posts sitemap, both keyed on WordPress's fixed
`sample-page` slug. **Why**: closes a real (if minor) placeholder-content indexing leak now, without
disturbing the fixture; the checklist already sanctioned "exclude from indexing" as a resolution. Scoped
to the well-known WP default slug and documented as removable once the fixture is deleted at launch
(LAUNCH-CHECKLIST §4). Verified live: the page emits `noindex,nofollow` and no longer appears in the
sitemap, while real pages (e.g. `/contact/`) are unaffected.

**Decision 19 (2026-07-14) — CoreX Forms framework bug: no `novalidate`, worked around client-side.**
Manually driving the Contact form through its required states (spec 008 T005) reproduced the exact
defect the completion contract flagged: submitting with an empty required field shows the browser's
native "Please fill out this field" bubble instead of the handoff's custom inline `.corex-form__error` +
form-summary UI. Root cause: `corex-forms`' `FormBlockRenderer`/`FlowBlockRenderer` render
`<form class="corex-form">` without a `novalidate` attribute, so the browser's own constraint validation
intercepts the submit before `window.Corex.forms`' own schema-mirrored validator (spec 043) ever runs —
the framework's validation/loading/error/success JS is otherwise fully wired and correct. This is a CoreX
**framework** bug (`plugins/corex-forms/src/Block/{FormBlockRenderer,FlowBlockRenderer}.php`), out of
Client Site Mode scope to fix directly. **Worked around** in the client layer: `perego-theme/assets/src/js/
main.js` sets `form.noValidate = true` on every `.corex-form` at `DOMContentLoaded`, before any user
interaction is possible — no race condition, no framework file touched. Flagging for a CoreX Framework
Mode task to add `novalidate` to the renderer directly (removing the client workaround once fixed
upstream). **Why**: the same pattern as the two framework quirks logged in the 2026-07-11 environment
bootstrap note — work around in the client site, document, and flag upstream rather than either shipping
the native-bubble defect or editing framework internals from a client-site session.

**Decision 20 (2026-07-14) — Site-wide media lightbox as a plain-JS global block; `.lightbox` visibility
bug found in the already-shipped project gallery too.** Building the Services archive's missing
"Selected work" section needed an accessible image/video/gallery dialog, and no such shared component
existed — the existing `project-gallery-lightbox` is scoped to one project's own gallery via the
Interactivity API, tied to that block's own DOM/context. Rather than force a single-project pattern to
work across disparate trigger buttons scattered in other blocks' markup, built `perego-theme/media-lightbox`:
one dialog server-rendered once (in both footer template parts, so it's present on every route) whose
view script is plain vanilla JS — not the Interactivity API — delegating a click listener to any
`[data-image]`/`[data-video]`/`[data-gallery]` trigger anywhere on the page, ported from the handoff's own
`main.js` lightbox IIFE (media-type detection, prev/next, dots) with the accessible-dialog contract already
proven by `project-gallery-lightbox` (focus trap, Escape/backdrop close, focus restoration, scroll lock),
plus an `inert` background while open. **Why plain JS instead of Interactivity API**: the Interactivity
API's context model assumes triggers live inside the same store's DOM tree; a single global dialog reused
by unrelated blocks (client cards, services masonry, future homepage lightboxes) doesn't fit that shape
cleanly, while vanilla delegated-click matches exactly how the handoff's own reference implementation
already works.

Verifying it caught a much bigger, pre-existing bug: the dialog opened correctly in the DOM (`hidden`
attribute removed, focus trap fired, `aria-modal` set) but was **completely invisible on screen**.
Root cause: `perego-reference.scss`'s `.lightbox` rule is `opacity:0;visibility:hidden` by default,
becoming visible only via an `.is-open` class — the mechanism the static handoff's own demo JS toggles —
but both Perego lightbox blocks toggle the WordPress-idiomatic `hidden` attribute instead, which that rule
never accounts for. Checking the **already-shipped** `project-gallery-lightbox` confirmed the identical
defect: its 9/9 interaction checks had all passed because they only asserted the `hidden` DOM attribute,
never the actual computed style — the project gallery lightbox had been "verified" while genuinely
invisible to every real visitor. Fixed with one scoped rule in `perego-wordpress-adapter.scss`:
`.lightbox:not([hidden]) { opacity:1; visibility:visible; }` (its specificity naturally beats the
reference rule, so no `!important`), fixing both dialogs at once. Also strengthened
`verify-interactions.mjs` to assert `getComputedStyle(...).opacity`/`.visibility` directly rather than
just `hidden`, so this exact bug class cannot silently regress again. **Why this matters beyond the fix
itself**: it's concrete evidence that "the interaction script passed" is not sufficient proof of visual
correctness — matching the completion contract's own repeated warning not to treat functional/DOM checks
as visual acceptance.

**Decision 21 (2026-07-14) — AR Contact page silently lost its custom template because block-theme
template hierarchy matches page templates by slug, and Polylang mutates translated slugs.** Running the
full 8-viewport × EN/AR visual-recovery capture (previously only spot-checked at 1440/375) surfaced a
severe regression invisible to every prior manual check: the Arabic Contact page rendered as a bare,
unstyled list of native `corex-form__*` fields with no service-chooser buttons and no card container,
while English rendered the full designed layout. Root cause: `page-contact.html` was never registered as
a named custom template (unlike `legal.html`, already correctly registered in `theme.json`'s
`customTemplates` and explicitly assigned via `_wp_page_template` on both the `terms`/`terms-2` and
`privacy`/`privacy-2` pairs) — instead it relied on WordPress's implicit `page-{slug}.html` template-hierarchy
match. That works for the English page (slug `contact`) but Polylang appends `-2` to a translation's slug
to avoid a global slug collision (`contact` → `contact-2`), so the AR page's slug never matches
`page-contact.html` and WordPress silently falls back to the generic `page.html` template — no error, no
warning, just a completely different, undesigned page. Fixed by registering
`{ "name": "page-contact", "title": "Contact", "postTypes": ["page"] }` in `theme.json` and explicitly
setting `_wp_page_template = page-contact` on both page 57 (EN) and page 58 (AR) via `wp post meta
update`, mirroring the legal pages' already-correct pattern — template selection no longer depends on a
slug that Polylang is free to mutate. Verifying the fix also surfaced a second, smaller bug: the
service-chooser buttons' concise labels (`ContactServiceChooserRenderer::HANDOFF_LABELS`) were hardcoded
English strings passed through a bare `__()` call with no matching `.po` catalog entries for three of the
four labels (only `"Website Making"` happened to coincidentally already exist in the catalog as a
full-name string), so AR rendered three buttons in English and one in Arabic. Fixed by having the renderer
take the injected `LanguageService` and reuse `HomeContent::services()`'s already-correct, already-translated
locale-aware short names (the same source the home services-teaser cards use) instead of maintaining a
second, divergent, untranslated copy of the same four labels. **Why this matters beyond the fix itself**:
this is the second time this session that a defect was invisible to narrow, single-viewport manual
review and only surfaced once the full route × viewport × language matrix was actually captured and
looked at — reinforcing that partial/spot-check verification is not equivalent to the completion
contract's required full-matrix visual proof.

## 2026-07-14 — Spec 016: join-form placeholders + AR gettext gaps (i18n, third full-matrix catch)

Spec 016's EN/AR pass (T007) surfaced two more i18n defects invisible to single-language review, both
found only once the AR contact page was actually rendered and read. **(1)** `JoinFormRenderer` passed the
name and portfolio input `placeholder`s as **hardcoded English literals** (`'Put your name here'`,
`'Put your Portfolio/website link'`) rather than through the content seam — so the careers form showed
translated *labels* above *English* placeholders in AR. Fixed by adding `namePlaceholder`/
`portfolioPlaceholder` to both locale blocks of `Content\GlobalContent::join()` (the same locale-array
source the labels already use) and reading `$t['namePlaceholder']`/`$t['portfolioPlaceholder']` in the
renderer — no second divergent copy. **(2)** The project-brief budget `<select>` default `__('Select a
range')` and the footer message placeholder `__('Say hello.')` were `__()`-wrapped correctly but had **no
`msgstr` in `perego-site-ar.po`** (the AR catalog carried 68 of 172 pot msgids), so both fell back to
English inside an otherwise-Arabic RTL form. Fixed by appending the two AR translations and recompiling
`perego-site-ar.mo` (`wp i18n make-mo`). Locked in by strengthened `JoinFormRenderTest` AR assertions
(placeholders now Arabic; no EN placeholder leakage). **Pattern (now three times this session):** a
gettext string that "looks" translated because it is `__()`-wrapped is not translated unless the catalog
actually carries its `msgstr` — and hardcoded UI literals bypass the catalog entirely. Both classes of bug
are invisible until the AR viewport is rendered and inspected, not merely asserted in a hidden-DOM test.

## 2026-07-15 — Spec 017: legal "Last updated" as an editable projection + `ar` language pack

The handoff legal pages show `<p>Last updated: July 1, 2026</p>` under the H1, but the `legal.html`
template never rendered it — and `LegalTocRenderer`'s docblock had promised a "Last updated line (from
page meta)" that was left unimplemented. Rather than hardcode a date into the template (dead the moment
the client revises a policy), it is now a **`perego-theme/legal-updated`** server-rendered block in the
legal-hero backed by editable page meta `_perego_legal_updated`: an editor sets the curated review date
in a new **"Legal page"** meta box, and when blank it falls back to the page's own `post_modified`. This
keeps the visible line faithful *and* genuinely editable, matching the CPT-projection + per-field-seed
pattern used across specs 012–014.

**PostMetaBoxes `page:legal` pseudo-schema.** The metabox map is keyed by post type, but two different
boxes now live on the `page` type (front-page hero + legal date) with different scoping. Instead of
restructuring, a `page:legal` schema key maps to the real `page` type via a small `realType()` helper;
`addBoxes` scopes it to `legal`-template pages (`get_page_template_slug === 'legal'`) and `save` processes
every schema entry whose real type matches the post being saved. Clean, additive, no churn to the
existing hero box.

**`ar` core language pack is now a deploy requirement.** `wp_date` localizes month names from the
installed core translations; the environment had **no** language packs installed, so every Arabic date on
the site (not just this line) rendered English month names. Fixed at runtime with
`wp language core install ar`. This is an additive, non-destructive environment change and must be
reproduced on deploy (add to the provisioning/deploy checklist) so AR dates localize in production. The
label itself ("آخر تحديث") comes from `GlobalContent`, so it was never affected. **Pattern echo:** as with
the spec-016 gettext gaps, an Arabic-context defect stayed invisible until the AR page was actually
rendered — the fourth full-matrix catch this run.

## 2026-07-15 — Spec 018: orphan cleanup, release requirements, and the accepted residual

**Orphan removal is reversible + repointed, never blind.** The only orphan DB data at close-out was
default WordPress content: `Sample Page` (id 2) and the default `privacy-policy` **draft** (id 3). Id 3
was still wired as `wp_page_for_privacy_policy`, so it was repointed to the real EN privacy page (40)
*before* trashing — deleting it first would have blanked the site's designated privacy page. Both were
verified unreferenced (front/posts/privacy options, nav menus, Polylang translations) and **trashed**, not
force-deleted, so the step is reversible; a timestamped DB export was taken first. The obsolete
`perego_section` CPT was already fully migrated (0 posts) and there were 0 orphan postmeta rows, so no
deeper surgery was warranted. Seeders were deliberately **kept** — they are re-provisioning/provenance
infrastructure and there was no evidence any specific one is dead; deleting working seeders to chase a
"remove obsolete seeds" line item would trade real reproducibility for cosmetic tidiness.

**Two deploy steps are now mandatory** (a green local checkout is not enough): `npm run build` in
`perego-site` (the `build/Blocks/` output, incl. spec-017's `legal-updated`, is gitignored and
regenerated) and `wp language core install ar` (so `wp_date` localizes Arabic month names). Both are
recorded in the spec-018 spec.md release section.

**Accepted residual — AR primary-nav localization.** Header nav hrefs use `home_url()`, so on AR pages the
links resolve to the EN base URLs. This is a systemic Polylang nav-routing gap (first logged in spec 011),
not a per-page fidelity miss, and the language switcher + AR archives themselves work. It is deliberately
**not** bundled into acceptance: the correct fix (`pll_home_url()` + translated permalinks for every nav
item in `SiteHeaderRenderer`) is a focused i18n-routing task, and rushing a change to the header that
renders on every page at the tail of a long session would risk a broad regression for a cosmetic-scope
close-out. Documented as the recommended next task instead.

## 2026-07-15 — Spec 019: AR nav routing via a driver method, not Polylang-in-the-renderer

The header/footer nav built every link with `home_url($path)`, which is language-agnostic, so on `/ar/`
pages the nav pointed at the English URLs. The fix had to localize hrefs **without** making Polylang a
hard dependency of the renderers (constitution IX). So instead of sprinkling `pll_*` calls into
`SiteHeaderRenderer`, a new `localizedUrl(string $path): string` was added to the `LanguageDriver`
interface: the Polylang adapter owns all the plugin-specific resolution, the fallback adapter returns
`home_url($path)` (single-URL, client-side swap), and the renderers depend only on the interface as before.

**Two non-obvious Polylang behaviours drove the resolver's shape.** (1) `url_to_postid()` never
reverse-resolves the **posts page** (blog index), so `/journal` fell through to a naive language-prefix
guess `/ar/journal`, which Polylang 301-redirects *back to the English* journal — a broken nav link that
looked fine until the resolved URL was actually followed. Fixed by matching the posts page via its
**default-language** permalink: on a non-default request Polylang filters both `get_option('page_for_posts')`
and `get_permalink()` to the current language, so the code normalises to the default post first
(`pll_get_post(get_option('page_for_posts'), pll_default_language())`), compares against the
default-language nav path, then translates → `/ar/المدونة/`. (2) CPT **archives** (`/work`) also don't
reverse-resolve to a post, so they take the language directory prefix (`/ar/work/`) — which is the exact
target Polylang's own canonical redirect points at. Everything degrades to the plain URL on any failure,
so a missing translation is a same-language link, never a broken one. **Pattern:** an HTTP 200 on the nav
href's *raw* form is not proof — the link has to be followed to catch a 301-to-the-wrong-language.

## 2026-07-16 — Spec 020: fresh audit before fixes; join-form Email kept as a documented divergence

**Re-audit instead of trusting merged evidence.** The owner reported "the home page isn't finished per the
handoff" although specs 002/012 recorded pixel fidelity. Rather than argue from the merged evidence, spec
020 re-captured live-vs-handoff from scratch — and the old evidence had two blind spots worth recording.
(1) The spec-008 full-page baselines were captured with the handoff's reveal-on-scroll **unfired**, so
whole sections render empty in them; any comparison must scroll the page first. (2) Element screenshots
taken right after a CSS rebuild can rasterize with the **stale cached stylesheet** even after a reload —
a wrap/color fix that is live in the DOM can look unchanged in the capture. Verify wrap changes with
`Range.getClientRects()` line boxes plus a fresh viewport capture, not an element shot alone. The re-audit
found three visual drifts (footer bottom bar, message counter, teaser label color/wrap) and one functional
one (AR home links on `home_url()`), all real, all previously signed off.

**Join-us Email field stays although the handoff has no such field.** A careers application without a
reply address is dead data: the field is required, wired to storage + the `perego/v1/careers/apply`
contract, and removing it to chase pixel parity would break a working flow. Kept as a **documented
divergence** (audit D4) and visually reconciled instead (pattern-consistent placeholder, label copy
aligned to the handoff's `Portfolio/website link`). The handoff is the authority on how things look, not
a reason to delete owner-serving function that was deliberately added.

**Teaser label wrap is CSS measure, not markup `<br>`.** The handoff hard-breaks the four card labels
(`Video<br>Editing`). Since spec 012 made labels editor-supplied meta, a markup break would corrupt the
editing seam, so the two-line rendering is reproduced with `max-inline-size: calc(6.2em + 48px)` (em
tracks the clamped font; 48px is the fixed inline padding under border-box). Sized between the widest
first line ("2D Motion" ≈ 5.9em) and the narrowest one-liner ("Video Editing" ≈ 7.4em), so any
similar-length label — EN or AR — breaks like the design without special-casing the seed strings.

## 2026-07-16 — Spec 020 round 2: hero controls removed against the handoff; corporate lightbox (C-05) approved

**Hero prev/next/pause controls removed — owner overrides the handoff.** A new, more detailed handoff
(`Perego-Creative-Studio-Developer-Handoff-V2`) still explicitly instructs adding prev/next + pause/play
buttons to the hero slider (`docs/INTERACTIONS.md`, `docs/ACCESSIBILITY_HANDOFF.md`, both tagged
`[BUILD]`) — the live site had them built exactly per that instruction. The owner was asked directly
("these buttons match what the handoff requires — remove them, or is something else wrong with them?")
and confirmed: remove them, dots-only. This is a deliberate divergence from the handoff's own written
instructions, not a bug fix — recorded here so a future audit doesn't "fix" the hero back toward the
handoff's `[BUILD]` note. `HeroSliderRenderer::renderControls()` and the corresponding `view.js`
actions/state were deleted; autoplay, hover-pause, reduced-motion gating, dot navigation, and the
aria-live announcer were kept (still required by the same accessibility doc, and not part of the owner's
objection).

**Corporate client tiles now open the lightbox — owner resolves CONFLICTS_REGISTER C-05.** The handoff's
own conflict register flags "should corporate logo tiles open a lightbox" as `⚠️ Open / needs Perego`,
with `HANDOFF_AUTHORITY.md` instructing agents not to resolve open items unilaterally. The owner resolved
it directly ("Corporate Clients should open lightbox"), so `ClientsCarouselRenderer::corporateCard()` now
emits a `data-image` attribute (the client's featured-image logo, when set) so the tile opens in the
existing site-wide media lightbox — mirroring the conditional-media pattern individual clients already
use (no media set → tile stays non-interactive, same as before).

## 2026-07-16 — Spec 020 round 3: client media (gallery/video type) edited via wp-admin meta boxes, not block attributes

**Client galleries/video sources are `register_post_meta()` + a dedicated `wp.media` meta box, not
block-editor attributes.** The owner asked for corporate logos to open a gallery of mixed images/videos and
individual cards to support three video source types (embed/upload/external-new-tab), all editable by the
client without a developer, and left the *how* to me ("decide for me the best practice... make sure
everything is visual in FSE in the end"). The `clients-carousel` block renders **many** Client CPT posts at
once, so there is no single block instance a per-client gallery could attach to as an attribute — the only
architecturally sound seam is per-post. This also matches the codebase's own established, deliberately-chosen
pattern: the 2026-07-14 hero decision (above) explicitly abandoned block attributes in favor of page meta +
`PostMetaBoxes` specifically to avoid a `wp-scripts`-build-dependent editing surface, and the Project gallery
(`ProjectGalleryMetaBox`) already proves the same `wp.media`-picker-in-a-meta-box pattern for exactly this
kind of "pick some media, see it inline" editing. Client posts already open in the WordPress block editor
(Gutenberg), and meta boxes render directly inside that screen (below the content, same as every other
Client field), so this satisfies "visual in FSE" without inventing a new, unproven pattern (a custom React
sidebar panel) nowhere else in this codebase uses.

**New meta:** `_perego_client_gallery` (JSON array of `{type:'image'|'video', id, url}`, corporate) and
`_perego_client_video_type` (`embed`|`upload`|`external`, individual) on `perego_client`, both registered
with REST schema + sanitization + `auth_callback` like every other structured field in this codebase. New
`Admin\ClientMediaMetaBox` (modeled line-for-line on `ProjectGalleryMetaBox`'s nonce/capability/autosave
guard shape and inline-no-build-step `wp.media` JS) owns both fields; `PostMetaBoxes`'s old plain-text
"Video URL" field was removed from the Client schema (moved into the new box) to avoid the same field
appearing editable in two places.

**Join-form validation now reuses the contact form's own classes instead of a second hand-matched copy.**
Round 2 gave the join-form's banner the *right colors* but not the *right structure* (no pill/icon/padding,
no per-field errors at all) — a second bug of the same shape the round-1/round-2 fixes kept finding
elsewhere (a bespoke, parallel implementation drifting from the "real" one). Rather than re-author the pill
CSS a second time, `JoinFormRenderer.php` now emits the same `corex-form__error`/`corex-form__status`
classes the shared CoreX form runtime uses, so both forms draw from one CSS source and cannot visually
diverge again without a shared-class change catching both.

**Clients-carousel's dead Swiper stylesheet deleted.** `clients-carousel/style.scss` was leftover from an
abandoned Swiper-based build (the block has used plain scroll-snap + arrow buttons for a while — `view.js`
has no Swiper import). Its `.clients`/`.clients__inner` selectors still matched the live DOM and silently
overrode the theme's correct header-to-slider spacing with a flex `gap`, the same "dead block-scoped
stylesheet outlives a refactor and quietly wins a specificity fight" bug already found and fixed twice in
round 1 (the site-header block's `.perego-header` CSS, the media-lightbox's legacy `.lightbox` CSS in
`perego-reference.scss`). Worth a standing suspicion whenever a *reported* visual bug doesn't match what the
*theme's* CSS says it should look like: check for a second, block-scoped stylesheet targeting the same
class names before assuming the reported value is wrong.

## 2026-07-16 — Spec 020 round 4: home page converted to real block attributes, reversing the 2026-07-14
meta-box decision — plus an En/Ar attribute-pair pattern for text embedded in shared FSE templates

**Block attributes, not meta boxes — the owner explicitly reversed the earlier architecture call.** The
2026-07-14 hero decision (above) and every round since deliberately chose page-meta/CPT-meta +
`PostMetaBoxes` sidebar fields over block attributes, specifically to avoid a `wp-scripts`-build-dependent
editing surface. The owner has now explicitly asked for the opposite — "for the slider must be able to
edit them from RichText... all of these things must be editable through the FSE not from the options
pane" — for the entire home page: header, footer, hero, services, clients. This is the client's call to
make and it overrides the prior convention; this entry exists so a future audit doesn't "fix" any of this
back toward meta boxes citing the old decision. Converted: clients-section headings, services-teaser
heading/"See All", hero slides + CTA, header nav/logo/sticky toggle, footer contact-channels/social-
links/blurb. Card/CPT-driven content (client galleries, service card images, project data) stays
CPT-driven — that's a different, still-correct decision (one block instance can't hold many-posts' worth
of data as attributes), not reversed here.

**En/Ar attribute pairs for template-embedded blocks — not a locale-fallback trick.** The first attempt at
this (clients-section headings) initially used a single attribute per string with a locale-aware
`ClientsContent` seed fallback — the same shape as the hero's page-meta mechanism. That's wrong for a
block instance living in a **shared FSE template** (`front-page.html`, `header.html`, `footer.html`):
there is only one instance of the block across every language, so one attribute value would show
identically on the English and Arabic pages — there is no "current post" to key off inside the Site
Editor the way page meta can key off "the currently queried front page." The fix, already proven by the
pre-existing `footer-careers` block (`headingEn`/`headingAr`/`blurbEn`/`blurbAr`, spec 009): store **both**
languages as separate attributes on the one block instance, shown together in the editor (not toggled),
and let the PHP `render_callback` pick the current-locale variant. Every converted block in this round
(clients, services, hero, header, footer) follows this same convention. **Rule of thumb going forward:**
any block whose instance lives in a template/template-part (not a per-post `post_content`) needs the
En/Ar-pair pattern for any locale-sensitive text attribute — a single-attribute-with-seed-fallback design
is a real bug there, not just a style choice.

**Hero keeps a three-tier fallback (attribute → the old page-meta mechanism → seed), not a clean cutover.**
Unlike the other four blocks (which had zero prior admin UI to preserve), the hero already had a working,
owner-editable mechanism (spec 012's page meta) that may already hold real, owner-entered copy. Silently
dropping that tier would lose data. `HeroContent::resolve()` checks the new attribute first, then the old
meta, then the seed — so existing hero copy keeps rendering exactly as before until an editor re-enters it
via the new RichText fields, at which point the meta tier becomes permanently moot (attribute always wins
once set) and the old `PostMetaBoxes` "Homepage hero" box was removed (kept editing in two places would
just be confusing, not backward-compatible).

**Nav items, contact channels, and social links are JSON-string attributes, not arrays-of-objects or
per-item RichText.** WordPress block attributes support nested array/object types via a REST schema, but
a flat JSON string in a single `string`-typed attribute is simpler to reason about in the PHP
`render_callback` (one `json_decode`, no schema validation edge cases) and is exactly what the block's own
`edit()` already needs to do anyway (`JSON.stringify`/`JSON.parse` around a local repeater list). Nav
items/contact channels/social links aren't language-specific data (a phone number or a URL doesn't
translate), so — unlike the En/Ar text pairs above — these are single (non-bilingual) attributes shared
across both locales, consistent with how they were previously hardcoded PHP consts shared by both
languages.

## 2026-07-21 — Spec 021: editor previews move from `ServerSideRender` iframes to live-canvas `edit()`
markup guarded by parity tests (refines framework Decision #43); structured repeaters replace JSON-string
attributes (reverses the round-4 note above, at the owner's explicit request)

**Live-canvas editing, not an SSR iframe + sidebar — the owner asked for "the Bricks experience."** Framework
Decision #43 mandated that every dynamic block preview its PHP `render_callback` via `<ServerSideRender>` —
"one renderer, never a duplicated JS implementation." That gave an accurate but **non-interactive** preview:
the canvas is a server-rendered iframe, and all editing happens in the right-hand Inspector (even the round-4
RichText fields live in the sidebar, not on the design). The owner's directive this session — *"forget the
current implementation, I want the Bricks/Elementor experience, editable in the canvas"* — makes that model
the defect to remove. **Decision:** for **static-layout blocks** (header, footer, hero, services-teaser,
home-about, service-hero, and the What-We-Do / Process sections), `edit()` now renders the **real component
markup** using the **same compiled SCSS** the front-end loads (imported via `block.json` `style`/`editorStyle`),
so the canvas is pixel-identical to production; editable text is `RichText` **placed in that markup**, media is
`MediaPlaceholder`/`MediaReplaceFlow` **in place**, and the Inspector keeps only non-content settings (link
targets, toggles, source mode). `<ServerSideRender>` is retained **only** for **dynamic/query blocks** whose
content has no fixed value at edit time (clients-carousel, portfolio-grid, related/search), and even those get
an on-brand styled placeholder rather than a bare box.

**PHP stays the single front-end renderer; a parity test is what makes that safe.** The real risk this
introduces is exactly what #43 was avoiding — editor markup and PHP output drifting apart. So this decision is
only valid **with** its guard: each static block ships a **markup-parity test** (a Jest snapshot of the
editor-rendered structure checked against a fixture of the PHP `render_callback` output). The front-end render
path is unchanged and remains authoritative; `edit()` is a faithful editor-only projection of it, and the
parity test fails the build if the two structures diverge. This is a **refinement of #43, not a repudiation**:
#43's "one source of truth = the server renderer" still holds for what ships to visitors; we add an
editor-only view of that same structure, tied to it by a test rather than by an iframe.

**Structured repeaters replace JSON-string attributes — this reverses the round-4 note above, on the owner's
call.** The round-4 entry deliberately stored nav items / contact channels / social links as a single
JSON-`string` attribute and left a note telling future audits not to "fix" it back. An in-canvas repeater
(add / reorder / remove items directly on the design) is materially cleaner to build and reason about over
typed array/object attributes with a REST schema than over a hand-parsed JSON blob, and the owner explicitly
approved "structured attribute arrays … never JSON strings" for this work. **Decision:** migrate these to
structured attributes as each block is rebuilt, with an **idempotent read-time upgrade** — the PHP renderer and
`edit()` accept the legacy JSON-string value and normalize it to the structured shape, so no saved content is
lost and unedited blocks render unchanged until touched. The En/Ar-pair rule (round 4) and the "one instance
in a shared template needs both languages as attributes" rule are unaffected and still apply.

**Reusable editor toolkit so all 15 components are consistent.** The existing shared `perego-site/src/Editor/`
directory (already holding `MediaField`, `RepeaterControls`, `collection`) is **extended** — not replaced — with
the in-canvas primitives the live model needs (`EditableText`, `EditableMedia`, `RecordPicker`, `LinkControl`,
`LanguagePair`) consumed by every rebuilt block, so "start clean" does not mean fifteen bespoke editors. The new
primitives and the parity-test harness are built once, before the first component (Header) lands.

**Rule of thumb going forward:** a block whose content is fixed at edit time renders its real markup in
`edit()` (live-canvas, parity-tested); a block whose content is a runtime query keeps `<ServerSideRender>` with
a styled placeholder. New repeaters use structured attributes; legacy JSON-string values are upgraded at read
time, never dropped.
