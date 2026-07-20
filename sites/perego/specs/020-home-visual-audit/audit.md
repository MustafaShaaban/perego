# Spec 020 — Home page visual audit: live site vs design handoff

**Date:** 2026-07-16 · **Reference:** `_design_handoff/Perego-Creative-Studio-Final-Handoff/site/index.html`
served statically (reveal-on-scroll fired before every capture) · **Live:** `http://perego.local/` (+ AR home).
**Captures:** `sites/perego/output/020-home-audit/` — full-page: `handoff-en-1440`, `handoff-ar-1440`,
`handoff-en-375`, `live-en-1440`, `live-ar-1440`, `live-en-375`, `live-ar-375` (`-full.png`); per-section
element shots (`*-hero.png`, `*-services.png`, `*-footer.png`); post-fix evidence (`live-*-after.png`).
The handoff ships no AR-mobile reference beyond its client-side RTL flip, so the AR-375 comparison is
live-vs-RTL-rules rather than live-vs-capture.

## Method

Full-page and element screenshots at matched viewports (1440×900, 375×812), EN + AR, with each section
scrolled into view so the handoff's reveal animations settle (the old spec-008 baselines were captured
without this — their "empty" sections are a stale-evidence artifact, not a design difference).
Layout metrics cross-checked with computed styles (e.g. corp tile 115×77 and individual card 413×150 are
**identical** on both sides; clients-section heading 56px both sides).

## Findings

| # | Section | Difference (live vs handoff) | Classification | Disposition |
|---|---------|------------------------------|----------------|-------------|
| D1 | Footer bottom bar | Live: `© 2026 Perego` + Terms/Privacy. Handoff: `© <year> Perego Creative Studio — بيريجو. All rights reserved.` + **Journal**, Terms, Privacy (`site/index.html` footer bottom). Same gap on AR + mobile. | **Real drift** | Fix: restore full copyright line + Journal link (localized) |
| D2 | Footer quick-message form | Live textarea has no `maxlength` and no counter. Handoff caps message at **1200** chars with a live `0 / 1200` counter (`site/js/main.js` CAPS + field-counter; visible on home and contact reference pages). | **Real drift** | Fix: `maxlength="1200"` + live counter, matching handoff styling |
| D3 | Services-teaser card labels | Live: **white**, single-line (`VIDEO EDITING`). Handoff: **accent** `#d86af3` (inherited link color), explicit two-line break (`Video<br>Editing`), on desktop + mobile, EN + AR. | **Real drift** | Fix: accent token color + two-line wrap |
| D4 | Join-us form | Live adds a required **Email** field (not in handoff) and its input has no placeholder; label reads `Portfolio / website link` vs handoff `Portfolio/website link`. | **Accepted divergence** (an application needs a reply address; field is wired to storage/email) | Keep field; add placeholder for pattern consistency; align label copy |
| D5 | Hero slider controls | Live shows prev/pause/next; handoff page shows dots only. | **Not drift** — handoff `docs/INTERACTIONS.md` explicitly instructs the build to "add prev/next + pause/play + aria-live" | None |
| D6 | Clients section density | ~~Live: 4 corporate tiles / 4 individual cards.~~ **Round 8 (2026-07-19):** owner-approved demo seed now matches the handoff — 20 corporate eq-icon tiles + 3 `REVIEW EL ETNEN` cards with the restored 3-line layout (title / subtitle / **+1M** views). | **Resolved for demo** — real, approved client material still replaces this before launch (FR-006). Also fixed two code gaps: the `.indiv-card__sub` line is now rendered (new `_perego_client_sub` meta) and the stat bolds via a `<strong>`-only `wp_kses`. | Done — corporate cap 12→20; `seed-clients.php` reseeds the demo set |

No other visible differences at either viewport in either language: hero typography/wrap/CTA, about &
mission glass panels, services imagery/stagger/hover ring, clients layout, footer forms' underline fields
and buttons all match.

One additional functional finding made while reading the implicated renderers:

| # | Section | Difference | Classification | Disposition |
|---|---------|-----------|----------------|-------------|
| D7 | AR home links | Hero CTA, "See All Services", and all four service cards built hrefs with `home_url()`, so the AR home linked to the **EN** pages (`/contact`, `/services/...`) — the same gap spec 019 closed for the nav. | **Real drift** (functional) | Fixed on the home renderers: `localizedUrl()`; AR now links `/ar/contact-2/`, `/ar/services/`, `/ar/services/<slug>-2/` (all verified 200). The same residual exists on non-home renderers (breadcrumbs, 404, portfolio grid, services overview, project nav) — deferred to those pages' passes. |

## Fixes applied + re-verification (2026-07-16)

- **D1** — `SiteFooterRenderer::renderBottomBar()`: full handoff copyright + localized Journal link.
  Verified live EN (`© 2026 Perego Creative Studio — بيريجو. All rights reserved.` + Journal/Terms/Privacy)
  and AR (translated المدونة link). `live-footer-after.png`, `live-ar-footer-after.png`.
- **D2** — `QuickMessageForm` message rule `max:2000`→`max:1200` + `maxlength` attr; new
  `site-footer/view.js` projects the live `n / 1200` counter via the shared `data-perego-counter`
  pattern (limit read from the textarea's maxlength); adapter SCSS renders it like the contact page's.
  Verified EN (`0 / 1200`) and AR (bidi-reordered exactly like the handoff's own RTL rendering).
- **D3** — `services-teaser/style.scss` label: accent token + `max-inline-size: calc(6.2em + 48px)`
  reproduces the handoff's two-line accent labels for all four seed labels (EN verified; AR unchanged
  apart from the accent color — the handoff carries no AR copy). `live-services-after.png`.
  Note: element screenshots can rasterize with a stale cached stylesheet after a rebuild — verify wrap
  changes with `Range.getClientRects()` line boxes plus a fresh full-viewport capture.
- **D4** — join form: EN label aligned to `Portfolio/website link`; email input gains a
  pattern-consistent placeholder (EN + AR). Field itself kept (documented divergence).
- **D7** — `HeroSliderRenderer` + `ServicesTeaserRenderer` link building moved to
  `LanguageDriver::localizedUrl()`. AR link targets verified 200.

Suites after the fixes: Pest **268/268** (816 assertions), Jest **80/80** (12 suites, including the new
site-footer counter tests). Builds: theme Sass + perego-site blocks compile clean.

## Bookkeeping

Spec 012's five acceptance boxes were still unchecked with header "In progress" although the work merged in
PR #20 — ticked as part of this spec (see spec.md there).

## Round 2 — handoff V2 reconciliation (2026-07-16)

The owner supplied a new, more detailed handoff (`Perego-Creative-Studio-Developer-Handoff-V2`) and flagged
five live issues. Investigating each against the V2 handoff's `docs/`, `CONFLICTS_REGISTER.md`, and `site/`
prototype (extracted to `sites/perego/output/handoff-v2-extract/`) found concrete code-level causes for all
five, including two real defects rather than copy/token drift:

| # | Issue reported | Root cause | Fix |
|---|---|---|---|
| D8 | Hero slider still shows prev/next/pause controls | Built exactly per the V2 handoff's own `[BUILD]` instruction (`INTERACTIONS.md`, `ACCESSIBILITY_HANDOFF.md`) — not a bug. Owner explicitly asked for these removed (dots-only) after confirmation, **overriding the handoff**. | Removed `HeroSliderRenderer::renderControls()`, the `prev`/`next`/`togglePlay` actions and `playPressed`/`playLabel` derived state in `hero-slider/view.js`, and the `.hero__control*` CSS. Dots, autoplay, hover-pause, reduced-motion gating, and the live region all kept. |
| D9 | Header doesn't stick while scrolling | Real CSS bug: `body { overflow-x: hidden; }` (`perego-reference.scss:65`) breaks `position: sticky` on every descendant in all major browsers — `html`'s already-correct `overflow-x: clip` workaround (`perego-wordpress-adapter.scss`) was never mirrored onto `body`. | Changed `body`'s `overflow-x` to `clip`. Verified live: header now sticks (`boundingTop: 0` after scrolling) and `.is-scrolled` toggles. |
| D10 | Section heights don't match the design | Code-level values already matched the handoff (hero 100vh/92vh, about 78vh, etc.) on both sides. Live measurement found the one real delta: the hero's `-94px`/`94px` overlap left a 4px seam against the header's actual 90px height — the handoff's own `CONFLICTS_REGISTER.md` C-09 flags exactly this mismatch. | Aligned hero overlap to `-90px`/`90px` in `hero-slider/style.scss` and the legacy copy in `perego-reference.scss`. Verified live: header-to-hero gap is now exactly `-90px` (no seam). |
| D11 | Corporate Clients tiles don't open a lightbox | Tiles had no `data-image`/`data-video`/`data-gallery` attribute, so the global lightbox listener never picked them up. The handoff's `CONFLICTS_REGISTER.md` C-05 flags this as "needs Perego approval" — owner gave that approval directly. | `ClientsCarouselRenderer::corporateCard()` now emits `data-image` (the client's featured-image logo) when one is set, matching the same conditional-media pattern individual cards already use. |
| D12 | Individual Clients lightbox doesn't work at all | CSS collision: `perego-reference.scss` still carried a full duplicate `.lightbox` ruleset ported from the static prototype, defaulting to `opacity:0; visibility:hidden;` and only revealing on an `.is-open` class the real `media-lightbox/view.js` never sets (it toggles the `hidden` attribute instead, matching the block's own correct `style.scss`). Both stylesheets targeted the same `.lightbox` class on every page. | Deleted the dead ruleset from `perego-reference.scss`. Verified live: unhiding the dialog now yields `display:flex; opacity:1; visibility:visible` (previously suppressed). |
| D13 | Footer forms: validation/success message colors, fonts, sizes wrong | Two independent bugs: (1) the framework's `.corex-form__error` sizes itself with `var(--wp--preset--font-size--sm)`, a slug this theme never defines (only `small`), so the rule was invalid; (2) `.corex-form__status.is-success`/`.is-error` had no explicit text color (inherited plain body text); (3) the join-form's `[data-tone="error"/"success"]` reused the purple `accent`/`accent-soft` tokens — not validation colors at all, so both states looked the same purple. | Added `error-text` (`#ff9ad1`) and `success-text` (`#b7f5d4`) tokens to `theme.json`, matching the handoff exactly. Wired them into `.corex-form__error`, `.corex-form__status.is-success`/`.is-error` (`perego-wordpress-adapter.scss`), and `join-form__status[data-tone]` (`join-form/style.scss`). Verified live: both new tokens resolve correctly (`--wp--preset--color--error-text: #ff9ad1`, `--success-text: #b7f5d4`). |

Suites after these fixes: Pest **270/270** (819 assertions), Jest **75/75** (12 suites — 3 hero-slider
control tests removed, 2 corporate-lightbox tests added). Builds: theme Sass + perego-site blocks compile
clean. Live-verified against `perego.local` with Playwright (header sticky, hero controls, lightbox
visibility, hero/header seam, form color tokens).

## Round 3 — clients gallery/lightbox rebuild, join-form parity, clients margins (2026-07-16)

Round 2's D11/D12 fixes were necessary but not sufficient: the owner reviewed the live result and clarified
the actual required behavior — corporate tiles need a real multi-item **gallery** (mixed images + videos),
individual cards need a **source-type-aware** video (embed / uploaded file / external link opening in a new
tab), and all of it must be editable without a developer. Also flagged: join-form's validation styling still
didn't match the contact form, and the clients section's header-to-slider margins looked off despite the
CSS values matching the handoff on paper.

| # | Issue reported | Root cause | Fix |
|---|---|---|---|
| D14 | Corporate tiles need a real gallery (images + videos, not one logo) | The Client CPT only ever supported one featured image; no gallery field existed. | New `ClientPostType::META_GALLERY` (JSON array of `{type:'image'\|'video', id, url}` items) + `Admin\ClientMediaMetaBox` (a `wp.media`-backed repeater, modeled on the existing `ProjectGalleryMetaBox`, rendered directly inside the Client's block-editor screen). `ClientsCarouselRenderer::corporateCard()` resolves the gallery to a `data-gallery` list; the shared lightbox already auto-detects each item's type per-item, so a mixed image/YouTube gallery needed no lightbox JS changes. Falls back to the single logo when there's no gallery. |
| D15 | Individual video needs to distinguish embed / uploaded / external-new-tab | Only a single video-URL field existed; the lightbox's `mediaType()` guesses type from the URL shape alone, so an intentional external link could be wrongly treated as a broken image. | New `ClientPostType::META_VIDEO_TYPE` enum (`embed`\|`upload`\|`external`, default `embed` for back-compat), editable in the same `ClientMediaMetaBox` (a select + a `wp.media` video-picker convenience button for the "uploaded file" case). `individualCard()` omits `data-video` entirely for `external` so the lightbox's click-delegate never intercepts it — the anchor's native `target="_blank"` navigation proceeds. Play icon still shows for any video type. |
| D16 | Join-form validation still doesn't look like the contact form | Round 2 only fixed *colors*; the join-form's banner never got the contact form's pill/icon/padding/bold treatment (color-only match, not a structural one), and it had **no per-field inline errors at all** — only one bottom banner, while the contact form shows an error under each invalid field. | `JoinFormRenderer.php` now emits a `corex-form__error` span (matching the shared CoreX form runtime's own `FieldRenderer` contract) after every validated field, plus `aria-describedby`/`aria-invalid`; the banner gained the shared `corex-form__status` class alongside its own. `view.js` now validates name/email/CV independently, reports each into its own field, and focuses the first invalid field. The banner and fields now render from the *same* CSS rules the contact form uses — one source of styling, not a second hand-matched copy. |
| D17 | Clients section header-to-slider margins look off | `clients-carousel/style.scss` — a full **dead stylesheet** from an abandoned Swiper-based implementation (`view.js` is pure scroll-snap + arrow buttons; no Swiper import anywhere) — still shipped a `.clients__inner { display:flex; gap:clamp(40px,6vw,72px); }` rule. Two of its selectors (`.clients`, `.clients__inner`) happened to still match the live DOM and silently overrode the theme's correct `.clients__head` margin-based spacing (already byte-identical to the handoff) with a uniform flex gap. Same "dead block-scoped stylesheet collides with the real one" pattern as D9/D12. | Deleted the file entirely (block.json's `style` key removed to match; the unused `swiper` npm dependency removed too). Verified live: header-to-slider gap is now `44px` at desktop width, matching `clamp(28px,3.5vw,44px)` exactly. |

Also live-diagnosed (no code change): the owner couldn't find "CoreX Forms & Flows" in wp-admin. That
screen is correctly registered (`plugins/corex-config/src/Forms/FormsFlowsScreen.php`, same `manage_options`
gate as the rest of the CoreX Framework menu) and the site's sole account is `administrator` with
`corex-forms` active — so this is a findability issue, not a bug. The Submission Inbox's flow filter *was*
a real bug (a raw numeric "Flow ID" box) — fixed in `plugins/corex-config` (framework code, out of this
client-site's scope; see the root repo's `DECISIONS.md` #141 and `PROGRESS.md`).

Suites after these fixes: Pest **285/285** (853 assertions, +15 from the new `ClientMediaMetaBox`/gallery/
video-type coverage), Jest **76/76**. Builds: theme Sass + perego-site blocks compile clean. Live-verified
against `perego.local` (via `wp-cli`-seeded test data, since the live site only has placeholder client
content): a mixed image+YouTube corporate gallery opens correctly in the lightbox with working prev/next;
an individual client set to `external` video type renders `target="_blank"` with no `data-video`, keeping
its play icon; join-form's field errors and banner now carry the same pink/green pill styling as the
contact form; the clients header-to-slider gap matches the handoff.

## Round 4 — YouTube embed bug, forms/flows filters done properly, join-form message clarity, word-count
validation, file-upload feedback, and full home-page FSE editability (2026-07-16)

The owner reviewed round 3 live and reported (with screenshots): (1) individual-client YouTube videos
fail with "Error 153" inside the lightbox, (2) the round-3 Submission Inbox flow filter and a separate
Data Models "Form submissions" filter both still can't offer a real list of forms to pick from, (3)
join-form shows the identical generic sentence on every invalid field and never clears live as you
type, (4) textarea validation should count words, not characters, (5) a chosen CV file shows no
confirmation anywhere in the UI, and (6) the entire home page — header, footer, hero, services,
clients — needs to be genuinely editable (add/edit/remove/reorder text and links) from inside the
WordPress block editor, not a wp-admin sidebar meta box.

| # | Issue | Root cause | Fix |
|---|---|---|---|
| D18 | YouTube "Error 153" in lightbox | `media-lightbox/view.js` appended `?autoplay=1` to whatever URL was pasted with zero normalization — a normal `youtube.com/watch?v=` URL (what anyone naturally copies) is not embeddable; loading it in an iframe is exactly what triggers YouTube's own rejection page. | Added `toEmbedUrl()`: converts `watch?v=`/`youtu.be/`/`shorts/` → `youtube.com/embed/…`, and a Vimeo page URL → `player.vimeo.com/video/…`. One shared fix point — covers every video trigger on the site (client cards, corporate galleries, project gallery). Live-verified: a pasted watch URL now plays inline. |
| D19 | Forms/Flows filters still unusable | CoreX has two *independent* form-identity systems — slug-based **Forms** (this site's real forms, `perego-quick-message` etc.) and numeric-id **Flows** (this site has none). Round 3 only fetched Flows, so the dropdown was empty; the Inbox's backend also had no form-slug filter clause at all (only the separate Data Models reader did); Data Models' own filter UI rendered every field as a generic text box regardless of type. | 🔧 Framework: added a `formSlug` filter to `SubmissionInboxQuery`/`WpSubmissionsReader::inboxMetaQuery()`; a shared `useFormOptions()` hook fetches + merges Forms and Flows; Submission Inbox's dropdown now lists both; Data Models' `QueryBar.js` renders a real `<select>` for `type: 'form'` fields. |
| D20 | Join-form errors identical + not live | Every field reused one generic `invalid` message key; validation only ran on submit. | Distinct message keys per field (`name_required`/`email_invalid`/`cv_required`, plus the existing `wrong_type`/`too_large`), each with real localized copy; `blur` + `input`/`change` listeners re-validate and clear each field's own error live. |
| D21 | Textarea should validate by word count | The shared CoreX `Max` rule is character-based; no word-count rule existed. | 🔧 Framework: new `MaxWords` validation rule (`max_words:N`) + a matching client-side rule in `corex-runtime.js`. Quick-message/project-brief forms switched from `max:1200` to `max_words:200`; both live counters now count words, not characters. |
| D22 | No filename confirmation on CV upload | Confirmed nothing anywhere in the codebase (Perego or CoreX) shows a selected filename — genuinely missing, not broken. | Added a `change` listener + a `.file-drop__filename` slot showing the picked file's name, plus the already-defined-but-unused `.file-drop.has-file` "selected" visual state. |
| D23 | Home page not editable from the block editor | Confirmed via a full architecture map: every distinctive section (header nav/logo, footer social/contact/blurb, hero slides, services-teaser heading, clients-section headings) was either hardcoded PHP or a `PostMetaBoxes` plain-text sidebar field — every block's `edit()` was a static placeholder string, zero canvas editability. | Converted clients-section headings, services-teaser heading/"See All", hero slides + CTA, header nav/logo/sticky, and footer contact-channels/social-links/blurb to real block attributes with `RichText`/`MediaUpload`/`ToggleControl`/repeater Inspector UI. Blocks embedded in the *shared* FSE template (all of the above) use an En/Ar attribute-pair pattern, matching the already-proven `footer-careers` block, rather than page meta (which can't distinguish "which page" inside the Site Editor). Every attribute falls back to the original hardcoded/meta seed when empty, so no existing page changed appearance. The homepage-hero's original page-meta mechanism (spec 012) is kept as a *second* fallback tier so any already-entered hero copy isn't lost. Not converted this round: footer legal links/copyright text (still hardcoded) — a smaller follow-up in the same shape as everything above. |

Also diagnosed (no code change, client-site side): the owner couldn't find "CoreX Forms & Flows" — that
screen is correctly registered and the site's account has the required capability, a findability issue,
not a bug (see round 3 note above).

Suites after these fixes: Pest **303/303** (898 assertions), Jest **93/93** (perego-site) + **15/15** new
(`corex-runtime.test.js` word-count cases) + **10/10** (`inbox.test.js` merged-filter cases) + **68/68**
(`corex-config` full suite) + **1262/1262** (root Unit suite, unaffected — confirms the framework changes
didn't regress anything else). Builds: theme Sass, perego-site blocks, and corex-config admin bundle all
compile clean. Live-verified against `perego.local`: a real pasted YouTube watch URL now plays inline
(no Error 153); the quick-message word counter reads "n / 200 words" live; hero/services/clients/header/
footer all render byte-identical to before (seed fallback intact) with sticky-scroll still working.
