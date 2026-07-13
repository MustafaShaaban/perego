# Spec 003 — Services + Portfolio (M3)

**Status**: in progress · **Branch**: `feature/003-services-portfolio` (built on the M2 line) ·
**Depends on**: spec 001 (shell/tokens/language), spec 002 (blocks + asset pipeline).

## Purpose

The Work/Portfolio surface and the Services surface, faithful to the handoff prototype
(`site/portfolio.html`, `site/services.html`, `site/project.html`, `site/service-*.html`) and its
content + CONTENT_MODEL.md. Projects are **placeholder** data (client = "Sample Client") to be
replaced with Perego's real work — do not invent results/metrics.

## User stories

### US1 — Project CPT + portfolio grid (P1) 🎯 — DONE
As a visitor, I browse the studio's work in a filterable grid so I can see projects by service.
- `perego_project` CPT (public, `work` archive, REST) + `perego_project_category` taxonomy (flat;
  four terms video·motion·design·web = the four services).
- `perego/portfolio-grid` block: "Our Work" heading + intro, a service-filter chip row, a masonry
  grid of project cards (category label · title · "Client: X · year" · media), and a no-results
  message. Client-side filter via the Interactivity API (chip → hide non-matching cards → toggle
  no-results). Bilingual (PortfolioContent EN/AR; card content from the CPT + meta).
- `archive-perego_project.html` template composes header → portfolio-grid → footer.
- Seed: 9 placeholder example projects + the 4 terms (`scripts/seed-projects.php`).

### US2 — Project single + gallery lightbox (P2) — TODO
Project detail page (overview/challenge/approach/solution/result, gallery) + a
`perego/project-gallery-lightbox` (accessible dialog, focus trap, counter, prev/next).

### US3 — Service CPT + service singles + services archive (P1) — TODO
`service` CPT (4 fixed entries) with the handoff service-page layout (hero, intro, process 4-steps,
selected work, closing CTA) + a services archive ("One studio, four services").

## Non-functional
- WCAG 2.2 AA (filter chips `aria-pressed`, no-results `role=status`, lightbox focus trap).
- i18n: chrome via `__()`, content via locale-aware providers / CPT; RTL via logical properties.
- Query discipline: bounded `posts_per_page`, `no_found_rows` on the grid.
- Tests: Pest (CPT args, renderer, repository mapping, content) + Jest (filter, lightbox). Guard Gate.

## Out of scope / deferred
- Real project imagery + copy (M7 / owner-supplied). Placeholder gradient media until then.
- Client CPT + carousel (M4). Journal (M5).
