# Perego — Launch Checklist (handoff)

Consolidated, actionable next steps. The engineering work is **done and verified**; what remains is a
human merge decision and owner-supplied content. Canonical status lives in `PROGRESS.md`; the launch
audit in `specs/004-design-fidelity/content-manifest.md`.

## Verified state (2026-07-13)

- **Spec 004** (design fidelity, all 16 routes) → PR #10 (`feature/004-design-fidelity` → `feature/002-home`).
- **Spec 005** (UI-string i18n, AR launch blocker closed) → PR #11 (`feature/005-i18n-strings` → `feature/004-design-fidelity`).
- Gates: **Pest 200 · Jest 67 · route-health 72/0 · a11y 12 pages/0 serious-critical · interactions 4/4** — all green.
- Both PRs report **MERGEABLE / CLEAN**.

## 1. Merge the PRs (yours to run — self-merge is correctly blocked for the agent)

The auto-mode guardrail blocks the agent from merging its own PRs without review (self-approval). Merge
them yourself, **stack order bottom-up after review**:

**On GitHub**: review + "Merge" PR #11 (into `feature/004-design-fidelity`), then PR #10 (into `feature/002-home`).

**Or via CLI** (note `--repo` — the gh default resolves to *corex*, which must never receive pushes):
```bash
gh pr merge 11 --repo MustafaShaaban/perego --merge   # 005 i18n → 004
gh pr merge 10 --repo MustafaShaaban/perego --merge   # 004 (now incl. 005) → 002-home
```
(To have the agent run these, explicitly authorize "merge without review" or add a `gh pr merge` permission rule.)

## 2. Owner content (T020) — blocked on your material; seeds are ready

Each item below has a proven **idempotent** seed script. Provide the real content (edit the script's
data or the WordPress admin), then run the seed. Run any seed with:
```bash
wp eval 'require "sites/perego/perego-site/scripts/<script>.php";' --path=wp
```

| Content | Script | What's needed from you |
| --- | --- | --- |
| Real client names/logos (replaces "Sample Corporate/Individual") | `seed-clients.php` | approved client names + logo files + display consent |
| Real projects + case studies (replaces demo) | `seed-projects.php`, `seed-project-media.php` | real project copy + photography |
| Real journal articles (replaces examples) | `seed-journal.php`, `seed-journal-media.php` | real article copy + images |
| Real service copy (if changing) | `seed-services.php` | final service descriptions |
| AR demo/content parity | `seed-ar-content.php` | AR copy for the above |

## 3. Legal — professional review/translation (not auto-generated, by design)

- **EN** Terms/Privacy are placeholder drafts (`seed-legal.php`) — replace with **counsel-reviewed** text.
- **AR** legal pages intentionally show a "pending professional Arabic legal translation" note — Arabic
  legal prose is deliberately **not fabricated**. Commission a professional Arabic legal translation.

## 4. Minor pre-launch cleanup

- `/sample-page/` still holds WordPress's default "Sample Page" copy (kept as the `page`-template
  route-health fixture). Before launch, replace its copy or exclude it from public navigation/indexing.

## Not blocking launch (verified done)

- All 16 route templates design-complete vs the handoff; forms functional/validated/spam-trapped/styled.
- AR routes render Arabic UI chrome (nav/forms/buttons/aria/status) and Arabic journal category badges.
- SEO 100; no fabricated ratings/reviews/awards/prices (enforced by test).
