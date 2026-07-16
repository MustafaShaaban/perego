# Spec 020 — Home page fresh visual audit vs design handoff

**Branch:** `feature/020-home-visual-audit`
**Mode:** Client Site Mode
**Status:** Complete (2026-07-16).
**Depends on:** 002 (home build), 012 (home editability), 019 (AR nav routing).
**Design reference:** `_design_handoff/Perego-Creative-Studio-Final-Handoff/site/` (locked handoff).

## Goal

The owner is finishing the site page-by-page, starting with home. Specs 002/012 recorded pixel fidelity,
but that evidence is from earlier merges and the old full-page baselines
(`output/visual-recovery/baseline/home-en-1440.png`) were captured with the handoff's reveal-on-scroll
animations unfired, so they can't prove today's state. Run a **fresh side-by-side visual audit** of the
live home page against the handoff reference, fix only confirmed drift, and close the spec-012
acceptance-bookkeeping lag.

## Scope

1. Serve the handoff `site/` statically and capture matched screenshots of it vs `http://perego.local/`
   (EN) and the AR home (RTL), at 1440 desktop and 375 mobile, per section: hero (slide states, dots,
   CTA), about panels, services teaser, clients (corporate + individual), footer forms. Scroll each
   section into view so reveal animations settle before capture.
2. Record every visible difference in `audit.md`, classified as **real drift** / **stale-evidence
   artifact** / **placeholder-content (out of scope)**.
3. Fix only real drift, in `perego-theme/` or `perego-site/src/Blocks/*Renderer.php`
   (theme.json tokens, logical properties, RTL-first). Re-capture to prove each fix.
4. Bookkeeping: tick spec 012's five acceptance boxes (work merged in PR #20) and set its status to Done.

## Out of scope

- Real client names/logos/stats/videos — owner material still unavailable; the "Sample Client" seed
  content remains the documented FR-006 launch blocker (spec 004 T020 stays unchecked).
- Other pages (about, services, work, journal, clients, contact) — later passes.

## Acceptance

- [x] Matched EN + AR, desktop + mobile captures of handoff vs live saved under
  `output/020-home-audit/`, per section.
- [x] `audit.md` lists every difference with a classification and disposition; zero unexplained
  differences remain (D1–D7).
- [x] All confirmed-drift fixes re-captured (before/after) and visually matching the handoff, modulo
  documented placeholder content.
- [x] Spec 012 acceptance boxes ticked + status Done.
- [x] Guards clean on the diff (wp-guard — one i18n composition fix applied; clean-code-guard;
  test-guard; docs-guard — one capture-list overclaim fixed); `git diff --check` clean;
  Pest 268/268, Jest 80/80.
