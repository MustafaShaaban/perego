# Perego Route Acceptance Matrix

**Reference root**: `_design_handoff/Perego-Creative-Studio-Final-Handoff/site/`  
**Rule**: `Not accepted` means the route must not be described as design-complete. A functional route-health check alone is not visual acceptance.

| Handoff template | Perego route / template | Required evidence | Owner | Current status |
| --- | --- | --- | --- | --- |
| `index.html` | `/` / `front-page.html` | EN+AR, desktop+mobile; header, preloader, hero, about, services, clients, footer | Perego delivery | **Design-complete** — 13/13 visual differences closed, editor-canvas migration (T012) done, a11y/interaction/route-health/unit all green (see [evidence/home.md](./evidence/home.md)). **Blocked on owner material** for launch: the clients section still shows placeholder demo names (FR-006) |
| `services.html` | `/services/` / `archive-perego_service.html` | EN+AR, desktop+mobile; cards, CTA, navigation | Perego delivery | Design-complete — hero image, whatwedo split+media, CTA background/button-class bugs fixed (see [evidence/services.md](./evidence/services.md)); "Selected work" teaser deliberately not built (no real project thumbnails yet) |
| `service-video-editing.html` | `/services/video-editing/` / `single-perego_service.html` | EN+AR, desktop+mobile; hero, tabs, canvas content, CTA | Perego delivery | Design-complete — hero image + whatwedo media fixed, verified as the representative single (see [evidence/services.md](./evidence/services.md)) |
| `service-motion-graphics.html` | `/services/motion-graphics/` / `single-perego_service.html` | EN+AR, desktop+mobile; hero, tabs, canvas content, CTA | Perego delivery | Design-complete — shares the fixed renderer/template with video-editing (slug corrected: real route is `/services/motion-graphics/`, not `2d-motion-graphics`) |
| `service-graphic-design.html` | `/services/graphic-design/` / `single-perego_service.html` | EN+AR, desktop+mobile; hero, tabs, canvas content, CTA | Perego delivery | Design-complete — shares the fixed renderer/template with video-editing |
| `service-website-making.html` | `/services/website-making/` / `single-perego_service.html` | EN+AR, desktop+mobile; hero, tabs, canvas content, CTA | Perego delivery | Design-complete — shares the fixed renderer/template with video-editing |
| `portfolio.html` | `/work/` / `archive-perego_project.html` | EN+AR, desktop+mobile; filters, empty state, cards | Perego delivery | Design-complete — breadcrumb + demo-note added, AR category-label/filter bug fixed, real (demo) featured images seeded for all 9 projects (see [evidence/work.md](./evidence/work.md)); real project photography is an owner-content gap (FR-006) |
| `project.html` | representative `/work/<project>/` / `single-perego_project.html` | EN+AR, desktop+mobile; gallery/lightbox, case-study content | Perego delivery | Design-complete for hero/hero-image/prose (see [evidence/work.md](./evidence/work.md)); gallery/lightbox, prev/next nav, and related-projects deliberately deferred as a separate feature slice |
| `archive.html` | `/journal/` / `home.html` | EN+AR, desktop+mobile; archive cards and pagination | Perego delivery | Design-complete — cards were completely unstyled (native WP blocks had zero CSS); breadcrumb, card styling, section background, and featured images all fixed (see [evidence/journal.md](./evidence/journal.md)); AR category-term translation is a tracked content gap |
| `single-post.html` | representative post / `single.html` | EN+AR, desktop+mobile; comments and share/content layout | Perego delivery | Design-complete — `post-single*` classes had zero CSS (whole page unstyled); added full styling, a locale-aware breadcrumb (new `post-breadcrumb` block), and fixed the silent-empty author byline (`post_author = 0`) via `fix-journal-post-authors.php` (see [evidence/single-post.md](./evidence/single-post.md)); avatar/read-time/author-bio/related-articles deliberately deferred |
| `contact.html` | `/contact/` / `page.html` | EN+AR, desktop+mobile; project brief form default/error/success | Perego delivery | Design-complete — the CoreX Forms brief (and the site-wide footer form) were **completely unstyled** on the client (block-only stylesheet never loads for server-rendered forms); ported the handoff form treatment onto `.corex-form__*`, added the 2-col field grid, fixed the visible honeypot + submit button (see [evidence/contact.md](./evidence/contact.md)). Service-chooser toggle-buttons adapted to a native multi-select. **Cross-cutting launch blocker surfaced**: UI-string i18n is unwired (English nav/labels on AR) |
| `terms.html` | `/terms/` / `legal.html` | EN+AR, desktop+mobile; TOC and draft notice | Perego delivery | Design-complete — fixed the title (a **site-wide** WP quirk dropped the h1/h2/h3 font-size presets → 16px headings everywhere; resilient re-declaration) and the white-gap background (see [evidence/legal.md](./evidence/legal.md)); AR section bodies are a tracked content gap |
| `privacy.html` | `/privacy/` / `legal.html` | EN+AR, desktop+mobile; TOC and draft notice | Perego delivery | Design-complete — shares the fixed `legal.html` template/renderer with terms (see [evidence/legal.md](./evidence/legal.md)) |
| `search.html` | `/?s=<query>` / `search.html` | EN+AR, desktop+mobile; populated, empty, and no-query states | Perego delivery | Design-complete — results upgraded from a plain list to the shared `.post-card` grid + kicker tags, breadcrumb, and pagination pills (see [evidence/search-404-page.md](./evidence/search-404-page.md)) |
| `404.html` | unknown route / `404.html` | EN+AR, desktop+mobile | Perego delivery | Design-complete — 404 numeral, heading, description, and both CTAs match the handoff; verified EN+AR (see [evidence/search-404-page.md](./evidence/search-404-page.md)) |
| `page.html` | representative standard page / `page.html` | EN+AR, desktop+mobile | Perego delivery | Design-complete (template) — H1 + constrained prose on dark canvas; the `sample-page` demo copy is WP default placeholder (Phase-5 launch cleanup), see [evidence/search-404-page.md](./evidence/search-404-page.md) |

## State inventory

- Global: header default/scrolled, desktop dropdown, mobile menu open/closed, language switcher, footer forms.
- Home: preloader first/repeat visit, hero active/paused/manual/reduced-motion, client carousels.
- Work: every filter chip, no-results, project gallery dialog and keyboard navigation.
- Forms: default, focused, invalid, form summary, submitting, success, server error, rate-limited, and upload type/size error where applicable.
- Legal: TOC active section and keyboard navigation.

## Evidence fields required for each acceptance row

1. Reference file and viewport.
2. WordPress EN and AR URL, including the actual Polylang translation URL.
3. Deterministic baseline and live capture paths.
4. Difference list with resolution commit/task ID.
5. Visual reviewer result: accepted, blocked on owner material, or not accepted.
6. Linked verification results for overflow, console errors, H1, language/direction, a11y, and interactions.
