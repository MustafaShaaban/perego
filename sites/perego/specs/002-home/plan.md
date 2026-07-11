# Plan 002 — Home (M2)

## Approach

Two new server-rendered `perego/*` blocks under `perego-site/src/Blocks/`, following the exact spec-001
pattern: a `block.json` (apiVersion 3, `viewScriptModule` for Interactivity API where needed, `style`
SCSS, `editorScript` index.js returning a placeholder edit + `save: () => null`), a render-callback
class emitting Interactivity API directives, registered in `PeregoSiteServiceProvider::boot()` on `init`.

Content is sourced from a small typed content provider rather than reaching into the handoff JSON at
runtime — the handoff `content/{en,ar}.json` is gitignored reference material, so the copy is mirrored
into a `PeregoSite\Content\HomeContent` value object (locale-aware, falls back to `en`). This keeps the
blocks dynamic/editable-ready (a later spec can swap the source for options/CPT) without a hard file
dependency.

## Blocks

### perego/hero-slider (US1)
- `block.json` — name `perego-theme/hero-slider` (matching the theme-namespaced convention spec 001
  established for `perego-theme/site-header` etc.), `viewScriptModule: file:./view.js`.
- `HeroSliderRenderer.php` — emits `<section class="hero" data-wp-interactive="perego/hero-slider"
  data-wp-context='{...slides, activeIndex:0, isPlaying:true, isPaused:false}'>`, the slides, the dot
  tablist, prev/next + pause/play controls, and an `aria-live="polite"` status region. Slides + labels
  injected from `HomeContent`. `<h1>` only on slide 0.
- `view.js` — Interactivity API store `perego/hero-slider`:
  - `state.activeIndex`, derived `state.currentLabel` for the live region.
  - `actions.goTo(i)`, `actions.next()`, `actions.prev()`, `actions.togglePlay()`.
  - `callbacks.init()` — starts the 6.5s timer unless reduced-motion; wires hover pause via
    `data-wp-on--mouseenter/mouseleave`; `visibilitychange` pause; clears timer on manual interaction and
    restarts only via play.
  - Timer stored module-side keyed by nothing (single hero per page) — mirror the preloader's timer
    discipline.
- `style.scss` — ported 1:1 from the prototype's `.hero*` rules, every literal replaced by a
  `--perego-*` var / `theme.json` preset. Adds `.hero__controls`, `.hero__control`, `.hero__status`
  (visually-hidden live region) not in the static prototype but required by the build notes.

### perego/services-teaser (US2)
- `block.json` — name `perego-theme/services-teaser`, no view script (static links; hover is pure CSS).
- `ServicesTeaserRenderer.php` — heading + arrow link + the four `.service-card` anchors from a fixed
  `SERVICES` map (slug, label two-liner, alt), token/CSS-driven offsets.
- `style.scss` — ported `.services-teaser`, `.service-cards`, `.service-card*` rules, tokenized.

## Content
- `PeregoSite\Content\HomeContent` — `heroSlides(): array`, `heroCta(): string`, `servicesTeaserTitle()`,
  `servicesTeaserSeeAll()`, `services(): array` (slug/name/alt) — locale via the injected LanguageService
  driver's `currentLocale()`; EN + AR arrays inline (mirrored from the handoff `content/*.json`).

## Template
- `perego-theme/templates/front-page.html`: header part → preloader → hero-slider →
  wp:group(about: two glass panels via core heading/paragraph) → services-teaser → footer part.
  `post-content` is dropped from front-page (the homepage is fully composed of blocks, per the FSE-native
  requirement); other templates keep `index.html` unchanged.

## Testing
- Pest `HeroSliderRenderTest` — slide count/order, h1-on-first, tablist + aria-selected, controls +
  live region present, context JSON shape, CTA target, token-only (no hex in output).
- Pest `ServicesTeaserRenderTest` — four cards in order, correct hrefs/labels/alt, lazy images, See-All link.
- Pest `HomeContentTest` — EN/AR locale selection + fallback, slide/service counts.
- Jest `hero-slider/view.test.js` — advance/wrap, goTo, prev/next, pause on hover + visibility,
  reduced-motion no-timer, stop-on-interaction, play/pause toggle, live-region label update. Reuse the
  spec-001 `@wordpress/interactivity` test double.

## Guard Gate
wp-guard + clean-code-guard (PHP), test-guard (Pest + Jest) on the diff before it ships.

## Verify live
`wp eval` render each block + `do_blocks(file_get_contents(front-page.html))` against the real install;
HTTP smoke `curl -H "Host: perego.local" http://127.0.0.1/` → 200 with hero + teaser markup.
