# Perego Route Acceptance Matrix

**Reference root**: `_design_handoff/Perego-Creative-Studio-Final-Handoff/site/`  
**Authoritative status (2026-07-13)**: All earlier row-level `Design-complete` claims are invalidated by
Spec 008. They were based on route health and qualitative checks, not the required baseline/actual/diff
comparison evidence. **Every route is Not accepted until Spec 008 records deterministic EN/AR responsive
visual evidence and a manual comparison against the locked handoff.**

| Handoff template | Perego route / template | Required evidence | Owner | Current status |
| --- | --- | --- | --- | --- |
| `index.html` | `/` / `front-page.html` | EN+AR, desktop+mobile; header, preloader, hero, about, services, clients, footer | Perego delivery | **Not accepted.** Spec 008 has only an unreviewed EN desktop capture subset; every locale, responsive width, and state remains to be compared. |
| `services.html` | `/services/` / `archive-perego_service.html` | EN+AR, desktop+mobile; cards, CTA, navigation | Perego delivery | **Not accepted.** Earlier qualitative evidence is historical only; no Spec 008 comparison record exists. |
| `service-video-editing.html` | `/services/video-editing/` / `single-perego_service.html` | EN+AR, desktop+mobile; hero, tabs, canvas content, CTA | Perego delivery | **Not accepted.** Representative single requires Spec 008 baseline/current/diff and state review. |
| `service-motion-graphics.html` | `/services/motion-graphics/` / `single-perego_service.html` | EN+AR, desktop+mobile; hero, tabs, canvas content, CTA | Perego delivery | **Not accepted.** Shared renderer does not replace route, locale, or responsive evidence. |
| `service-graphic-design.html` | `/services/graphic-design/` / `single-perego_service.html` | EN+AR, desktop+mobile; hero, tabs, canvas content, CTA | Perego delivery | **Not accepted.** Shared renderer does not replace route, locale, or responsive evidence. |
| `service-website-making.html` | `/services/website-making/` / `single-perego_service.html` | EN+AR, desktop+mobile; hero, tabs, canvas content, CTA | Perego delivery | **Not accepted.** Shared renderer does not replace route, locale, or responsive evidence. |
| `portfolio.html` | `/work/` / `archive-perego_project.html` | EN+AR, desktop+mobile; filters, empty state, cards | Perego delivery | **Not accepted.** Filter and no-results states lack Spec 008 evidence. |
| `project.html` | representative `/work/<project>/` / `single-perego_project.html` | EN+AR, desktop+mobile; gallery/lightbox, case-study content | Perego delivery | **Not accepted.** Gallery/lightbox and related navigation are still absent or unverified. |
| `archive.html` | `/journal/` / `home.html` | EN+AR, desktop+mobile; archive cards and pagination | Perego delivery | **Not accepted.** No Spec 008 route/state visual evidence exists. |
| `single-post.html` | representative post / `single.html` | EN+AR, desktop+mobile; comments and share/content layout | Perego delivery | **Not accepted.** No Spec 008 route/state visual evidence exists. |
| `contact.html` | `/contact/` / `page.html` | EN+AR, desktop+mobile; project brief form default/error/success | Perego delivery | **Not accepted.** Required form-state evidence is incomplete. |
| `terms.html` | `/terms/` / `legal.html` | EN+AR, desktop+mobile; TOC and draft notice | Perego delivery | **Not accepted.** No Spec 008 route/state visual evidence exists. |
| `privacy.html` | `/privacy/` / `legal.html` | EN+AR, desktop+mobile; TOC and draft notice | Perego delivery | **Not accepted.** No Spec 008 route/state visual evidence exists. |
| `search.html` | `/?s=<query>` / `search.html` | EN+AR, desktop+mobile; populated, empty, and no-query states | Perego delivery | **Not accepted.** Required populated, empty, and no-query state evidence is incomplete. |
| `404.html` | unknown route / `404.html` | EN+AR, desktop+mobile | Perego delivery | **Not accepted.** No Spec 008 EN/AR responsive comparison evidence exists. |
| `page.html` | representative standard page / `page.html` | EN+AR, desktop+mobile | Perego delivery | **Not accepted.** No Spec 008 EN/AR responsive comparison evidence exists. |

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
