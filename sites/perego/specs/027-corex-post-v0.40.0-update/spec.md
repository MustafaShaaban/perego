# Spec 027 — CoreX framework update, v0.40.0 → `upstream/main`

**Branch:** `chore/corex-post-v0.40.0-update` (off `fix/026-lighthouse-client-performance`)
**Mode:** CoreX Framework Mode — `sites/perego/` deliberately untouched
**Status:** Complete (2026-08-05).
**Precedent:** `specs/024-corex-v0.40.0-update/spec.md`, whose procedure this reuses verbatim.

## Goal

The framework was pinned at v0.40.0 on 2026-07-29. `upstream/main` has since moved **nine commits**
ahead of the tag — upstream specs 090–096, 62 files, 0 deletions, 0 renames. The owner asked for the
framework to be brought fully up to date.

## The tag-vs-main judgement, which this time had a third option

There is **no release tag past v0.40.0**. There is an unmerged upstream branch, `release/v0.41.0`,
containing `main` plus exactly one commit: a version stamp produced by `wp corex version`, touching
**25 files** (its own message says 23 — the commit is the authority).

`upstream/main` was taken, not the release branch. The framework code is identical either way — the
only delta is the number. Taking an untagged release-prep commit would make this fork claim v0.41.0
before upstream has published it, and leave us diverged across those stamped files if upstream amends
or re-cuts the release. **What we have is v0.41.0's content without its number**; when upstream tags
it, the next update starts from the tag and the stamp arrives with it.

This is the same class of judgement as the v0.35 update (`DECISIONS.md` 2026-07-23), which took `main`
over a tag — but for the opposite reason. There, `main` carried a wanted fix the tag lacked. Here, the
branch carries nothing but a version number, so `main` is the conservative choice rather than the
eager one.

## What upstream added

The published documentation site (090) and the 85 broken links it shipped with (092); a CI rule
requiring every always-running check (091); one browser fixture user per spec, after a shared login
took down a pull request that changed no browser test (095); a support email whose rendering follows
its transport (093); and the bulk of it — the in-admin guide (094, 096), which upstream's release note
describes as growing from 4 guides and 6 topics to 16 and 40. The screenshot count is checkable and
checks out: `addons/corex-guides/assets/screenshots/` holds **14**, of which 12 arrived in this merge.

## Scope

### Merge

`git merge --no-commit --no-ff upstream/main`, then `git checkout upstream/main --` for
`addons plugins packages theme tests`, then restore the survivors from `HEAD`.

**Zero conflicts.** Root `DECISIONS.md` was the only file changed on both sides and it auto-merged;
both sides were verified present afterwards rather than assumed — the fork's `#140` model-routing
entry and upstream's new `#208`–`#215`.

### The six files that stay ours

Re-derived at merge time against the freshly fetched `upstream/main`, per spec 024's lesson that an
assessment written against one version describes that version rather than planning the next. **All
nine commits touched none of them**, so nothing was re-forked on top of an upstreamed feature and the
2026-07-23 rule did not have to be applied.

| File | Why |
|---|---|
| `addons/corex-email/src/MailService.php` | `deliver()` still rebuilds the message with seven constructor arguments, dropping position 8 (`$from`) and 9 (`$attachments`). Perego's three mailboxes would collapse to one. Ours passes by name. |
| `plugins/corex-config/src/Data/SubmissionsSource.php` | `readable()` — still absent upstream; without it an operator reads raw JSON in the inbox. |
| `plugins/corex-config/package.json` | Upstream still ships a mojibaked description. |
| `tests/Unit/Data/DataRegistryDeferredTest.php` · `tests/Unit/Forms/FormListenerRoutingTest.php` · `tests/Unit/Forms/SubmitControllerSanitizeTest.php` | Pin *upstream's* defective behaviour, so a future upstream fix goes red on purpose. |

### The auto-merge trap

Cleared by assertion rather than per-file review:

```bash
git diff upstream/main --name-only -- addons plugins packages theme tests
```

printed **exactly** those six names and nothing else. Any other name would be a file the merge touched
that nobody decided about.

### Why the Perego-side reconciliation was empty this time

At v0.40.0 three regressions landed silently in `sites/perego/`, because nothing there conflicts.
This merge changed **no front-end runtime file at all** — nothing under `theme/` or `packages/`, no
`corex-runtime.js`, no `corex-forms`. The changed surface is admin-only (`corex-guides`, `DocsUrl`,
`corex-admin-shell.css`), plus docs, specs, tests and CI. The Arabic-validation, `max_words` and
phone-strictness class of silent regression had no surface here, and `sites/perego/**` was verified
untouched by the merge.

Neither lockfile moved, so no reinstall and no dependency reconciliation was required.

## Two findings recorded rather than fixed

**The docs workflow now deploys to GitHub Pages.** `docs.yml` gained a `deploy` job
(`actions/deploy-pages@v4`) triggered by push to `main`. On this fork that is latent, not active:
GitHub Pages is not enabled on `MustafaShaaban/perego` (the Pages API returns 404), and this project
never pushes to `main`. It is **not** patched out, because editing upstream's workflow would create a
fork edit on top of an upstreamed feature — the exact thing the 2026-07-23 rule forbids, and the thing
that guarantees a conflict next version. If anything ever lands on the fork's `main`, the job fails
noisily rather than publishing anything.

**`verify:dependencies` fails on two advisories, and this merge did not cause it.**
`brace-expansion` (GHSA-rgw5-rvv9-x895) and `fast-uri` (GHSA-7p8r-x3mc-p8w7), both high, both
transitive, both on `npm-root`. Every input to that check — `package-lock.json`, `package.json`,
`scripts/dependency-security-policy.json`, the policy module and the verifier — is **byte-identical
across the merge**. These are advisories published against an unchanged lockfile since 2026-07-29.
Bumping our lockfile to close them would diverge it from upstream's, which is what spec 024 explicitly
avoided; upstream closed the previous batch in its own spec 089 and should close these.

## Reported upstream — all four still open

All four are still open, and the evidence is mechanical rather than a re-read. The three pinning tests
**still pass** — `it runs a duplicated listener id twice — upstream behaviour, reported` and
`it accepts a scalar for a list field and stores it as a string — upstream behaviour, reported` among
them. A pinning test that asserts a defect passes only while the defect exists, so their passing *is*
the confirmation, and none of them can be flipped yet. The `MailService::deliver()` `$from`/
`$attachments` drop and the mojibaked `corex-config/package.json` are confirmed separately: zero of
the nine upstream commits touched either file.

## Verification

| | before | after |
|---|---|---|
| Framework Pest | 1723 | **1734** (upstream added 11) |
| Framework Jest | 52 suites / 431 | **53 suites / 434** |
| Perego Pest | 594 | **599** (spec 026 added 5) |
| Perego Jest | 303 | **312** (spec 026 added 9) |

**The framework Jest suite needs the docs built to be honest.** `tests/docs-links.test.js` arrived with
the merge and *skips itself* when `docs-app/dist` is absent — which is how it first ran here, reporting
"2 skipped". Building the docs site (55 pages) turns those two skips into two real passes. This is the
same failure shape spec 092 was written about: a link check that silently sampled only the working
half. Recorded so the next run does not read a skip as a pass.

Also green: `verify-editor-sorting` (7 cards, drag and non-drag reorder, no page errors);
`verify-theme-images` (8 contracts, WebP total 327,122 bytes). `DocsUrl` now emits
`https://mustafashaaban.github.io/corex/…` — both the site root and a sample deep link return 200.

**Live, and stronger than v0.40.0's result.** Eight public routes captured EN and AR before and after
the merge are **byte-identical** — not even the two `?ver` values that update needed, because no
front-end asset moved.

**The container boots.** Spec 024's worst defect was a service provider throwing at `init`, found by
loading the site because no test boots the container. `GuidesServiceProvider` is new admin-boot code,
so WordPress was booted through WP-CLI and `admin_init` plus `admin_menu` were fired: no fatal, guides
classes load.

`http://perego.local/wp-admin/` returns 404, and that is **not** an admin failure: this database has
`siteurl` and `home` set to `http://peregoads.com`, so `admin_url()` resolves to
`http://peregoads.com/wp-admin/` and the local vhost was never serving the admin at that path. It is
also not attributable to this merge, which changed no authentication or admin-routing code. Firing the
admin hooks through WP-CLI is what actually exercises the new admin-boot path, and it is why that was
done rather than treated as optional.

**Not done, and why.** The live brief-form submission from spec 024's battery was skipped: it can
dispatch real email from the client's three mailboxes, and the merge provably touched no forms code,
which the framework suite already covers.

## One thing this branch inherits and does not fix

`verify-a11y` reports **2 serious violations** (baseline 0): `role-img-alt` on
`.indiv-card[role="img"]`, home EN and AR. It is **not** from this merge — commit `8ef527cc`
(spec 026) changed the inert client card from `role="listitem"` to `role="img"`, and that commit is an
ancestor of this branch via `fix/026`.

It is worse than the rule name suggests. `role="img"` collapses the entire card into a single image
node, so the `<h3>` title and body copy inside it stop being reachable by assistive technology —
an accessible name would satisfy axe without restoring what the role hides. `corporateCard()` passes an
`aria-label`; `individualCard()` calls `cardTags($client, 'indiv-card')` with no extra attributes, so
the inert branch emits a nameless `role="img"`.

Left for spec 026, where it originated and where its own verification belongs. Recorded here because
this branch is what surfaced it.
