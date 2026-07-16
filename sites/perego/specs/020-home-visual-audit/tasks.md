# Tasks — Spec 020 Home page fresh visual audit

- [x] T001 Serve the handoff `site/` statically; verify it renders with JS reveal animations firing.
- [x] T002 Capture matched EN desktop (1440) per-section shots: handoff vs `http://perego.local/`
      (hero + slide states/dots/CTA, about, services teaser, clients corporate + individual, footer forms).
- [x] T003 Capture matched EN mobile (375) per-section shots.
- [x] T004 Capture matched AR/RTL desktop shots (handoff RTL reference vs live `/ar/` home).
- [x] T005 Write `audit.md`: every difference classified real drift / stale-evidence artifact /
      placeholder-content (out of scope), with disposition. → D1–D7 recorded.
- [x] T006 Fix confirmed drift only: D1 footer bottom bar, D2 message cap + live counter, D3 accent
      two-line teaser labels, D4 join-form placeholder/label alignment, D7 AR-localized home links.
      Re-captured after each fix (`live-*-after.png`); Pest 268/268, Jest 80/80.
- [x] T007 Bookkeeping: tick spec 012 acceptance boxes (merged PR #20) + status Done.
- [ ] T008 Guards on the diff (wp-guard / clean-code-guard for code, docs-guard for docs);
      update PROGRESS.md; DECISIONS.md if any non-trivial call was made; push + PR.
