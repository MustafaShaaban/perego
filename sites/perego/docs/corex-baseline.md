# CoreX Baseline Record — Perego Creative Studio

> Required by PEREGO_IMPLEMENTATION_PROMPT.md Phase 1. Records the exact upstream
> (CoreX) state the Perego client site is synchronized to. Update this file whenever
> the site re-synchronizes with a newer CoreX release.

## Runtime re-evaluation (2026-07-11)

Fetched both remotes and all tags at execution time
(`git fetch origin --prune --tags`, `git fetch upstream --prune --tags`).

| Fact | Value |
|---|---|
| `origin` (fetch/push) | `git@github.com:MustafaShaaban/perego.git` — **Perego** ✅ |
| `upstream` (fetch) | `git@github.com:MustafaShaaban/corex.git` — **CoreX** ✅ |
| `upstream` (push) | `DISABLED_DO_NOT_PUSH_TO_COREX` — push guardrail in place ✅ |
| Latest published CoreX stable tag | **`v0.33.0`** @ `71639e72340af5e9fd934aa35d57bb0121d82d64` |
| `upstream/main` HEAD (inspected) | `ff61bf0cbb0cfbc5bca6c64fcc7346449cd5729e` — `fix(security): hide wp-login.php from everyone, not just logged-out visitors` |
| Perego branch tip at sync-check | `af9e4fea425df50c746b5b3f25920db2a3523071` (`feature/002-home`) |

## Sync status: ALREADY CURRENT — no merge required

- `v0.33.0` (`71639e7`) **is an ancestor of HEAD** — verified with
  `git merge-base --is-ancestor v0.33.0 HEAD` → true.
- `upstream/main` (`ff61bf0`) **is also an ancestor of HEAD** — verified with
  `git merge-base --is-ancestor upstream/main HEAD` → true.
- `git log HEAD..upstream/main` is **empty** — there are no upstream commits the
  Perego branch does not already contain.

The Perego history already incorporates the latest stable release **and** all
post-release `upstream/main` commits (the spec-068 "admin product functional
completion" line plus the `wp-login`/login-slug security fixes: `ff61bf0`,
`3381105`, `9d54ce0`, `cab984d`, `3ff634b`, `d451a3d`, `0485448`, `af8465e`,
`20fffb1`, `8be4119`, …). These were merged during the original environment
bootstrap (`git merge upstream/main --allow-unrelated-histories` at framework
`v0.33.0`, per `sites/perego/PROGRESS.md`).

**Decision:** remain on the current baseline. `upstream/main` is newer than the
latest release but is already fully merged, so no new synchronization branch or
merge is needed this session. No upstream commits are intentionally deferred.

## Adopted baseline

- **CoreX tag:** `v0.33.0`
- **CoreX release SHA:** `71639e72340af5e9fd934aa35d57bb0121d82d64`
- **`upstream/main` SHA inspected & included:** `ff61bf0cbb0cfbc5bca6c64fcc7346449cd5729e`
- **Sync method:** already merged (unrelated-histories merge at bootstrap); re-verified
  as an ancestor of HEAD at runtime — no re-merge performed.
- **Merge commit:** original bootstrap merge (pre-existing history); no new merge this run.
- **Deferred upstream commits:** none.

## Framework isolation check

`grep -rl -i "perego" plugins/ addons/ packages/ theme/` (excluding `node_modules`)
returns **no matches** — there is no Perego code polluting CoreX-owned framework
directories. Perego lives entirely under `sites/perego/`, matching the CoreX
`sites/<client>/` convention.
