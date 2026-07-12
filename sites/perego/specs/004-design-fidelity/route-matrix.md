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
| `portfolio.html` | `/work/` / `archive-perego_project.html` | EN+AR, desktop+mobile; filters, empty state, cards | Perego delivery | Design-complete — breadcrumb + demo-note added, AR category-label/filter bug fixed (see [evidence/work.md](./evidence/work.md)); real project thumbnails are an owner-content gap (FR-006) |
| `project.html` | representative `/work/<project>/` / `single-perego_project.html` | EN+AR, desktop+mobile; gallery/lightbox, case-study content | Perego delivery | Not accepted |
| `archive.html` | `/journal/` / `home.html` | EN+AR, desktop+mobile; archive cards and pagination | Perego delivery | Not accepted |
| `single-post.html` | representative post / `single.html` | EN+AR, desktop+mobile; comments and share/content layout | Perego delivery | Not accepted |
| `contact.html` | `/contact/` / `page.html` | EN+AR, desktop+mobile; project brief form default/error/success | Perego delivery | Not accepted |
| `terms.html` | `/terms/` / `legal.html` | EN+AR, desktop+mobile; TOC and draft notice | Perego delivery | Not accepted |
| `privacy.html` | `/privacy/` / `legal.html` | EN+AR, desktop+mobile; TOC and draft notice | Perego delivery | Not accepted |
| `search.html` | `/?s=<query>` / `search.html` | EN+AR, desktop+mobile; populated, empty, and no-query states | Perego delivery | Not accepted |
| `404.html` | unknown route / `404.html` | EN+AR, desktop+mobile | Perego delivery | Not accepted |
| `page.html` | representative standard page / `page.html` | EN+AR, desktop+mobile | Perego delivery | Not accepted |

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
