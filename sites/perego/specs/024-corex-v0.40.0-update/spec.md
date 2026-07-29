# Spec 024 — CoreX framework update, v0.35.1 → v0.40.0

**Branch:** `chore/corex-v0.40.0-update` (off `feature/023-project-media-and-canvas-ordering`)
**Mode:** CoreX Framework Mode — `sites/perego/` also touched, for consumer reconciliation
**Status:** Complete (2026-07-29).
**Supersedes:** the v0.37.0 assessment in `DECISIONS.md` (2026-07-28), which is now obsolete.

## Goal

The framework had been pinned at v0.35.1 since 2026-07-22, when a v0.37.0 update was assessed and
deferred so 341 framework files would not land inside an open client PR. Upstream has since published
v0.38.0, v0.38.1, v0.39.0 and v0.40.0. The owner asked for the update.

## What changed about the plan

**The recorded reconciliation map was obsolete, in our favour.** It said five of our patches were
superseded and **eleven had to be re-applied**. Verified against upstream at v0.40.0, all four fork
commits have been upstreamed — including `corex-runtime.js:444`, which now reads *"`wp.i18n` alone was
a trap"* with `:453` using `form.dataset.corexMessages`: **our fix and our reasoning, verbatim**.

So the job became *take upstream wholesale and delete the fork edits*, per the precedent at
`DECISIONS.md` 2026-07-23 — a fork edit carried on top of an upstreamed feature guarantees the same
conflict next version.

## Scope

### Merge

`git merge --no-commit --no-ff upstream/main`, then `git checkout upstream/main --` for
`addons plugins packages theme tests`, then restore the two survivors. 27 files conflicted; the
non-source ones (`package.json`, both lockfiles, `.specify/feature.json`) were resolved by hand, with
**upstream's lockfile taken as the base** because it carries the spec-089 advisory closure.

`upstream/main` and tag `v0.40.0` are the same commit, so the tag-vs-main question from the v0.35
update did not arise. 0 deletions, 0 renames — additive.

### The two framework files we keep

**`addons/corex-email/src/MailService.php`.** Upstream's `deliver()` rebuilds the message for
sanitisation with **seven** constructor arguments, dropping position 8 (`$from`) and 9
(`$attachments`) at the one place every send passes through. They threaded `$from` through six files
and then stripped it. Perego's three mailboxes (`PeregoMailbox`: info@ / contact@ / noreply@) would
have collapsed to the single configured identity with nothing logged; upstream's own spec-081
attachments feature is dead on arrival for the same reason. Ours passes both **by name, not position**,
so a future reorder fails loudly rather than misfiling silently.

**`plugins/corex-config/src/Data/SubmissionsSource.php`.** Untouched upstream; our `readable()` still
turns `["brand-identity","motion-graphics"]` into a readable list.

Plus `plugins/corex-config/package.json`, where upstream ships a mojibaked description
(`Corex Config â€” admin screens`).

### The auto-merge trap

27 files conflicted; **eleven more changed on both sides and merged clean** — including
`RequestMailer.php` and `Queue/ActionSchedulerDispatcher.php`, where both sides added the same `from`
handling. That is exactly the case that produced a duplicated meta clause at v0.35.

The defence is one exhaustive assertion rather than a per-file checklist:

```bash
git diff upstream/main --name-only -- addons plugins packages theme tests
```

It must print exactly the files we chose to keep. Any other name is a file the merge touched that
nobody decided about.

### Perego-side reconciliation — the part that does not conflict

Three regressions live in `sites/perego/`, so they merge silently and would have shipped unnoticed:

1. **Arabic validation messages.** `FrameworkFormStrings` keys `gettext_corex` on *exact English source
   strings*; upstream's new `Block\ValidationMessages` reworded three, so `url`, `phone` and the new
   `default` rendered **English on `/ar/`**. Retargeted — and `FrameworkFormStringsTest` now asserts
   every key of `ValidationMessages::all()` **and** `FormSubmissionService` has an Arabic entry, so the
   next rewording is a red test rather than a silent regression.
2. **`max_words` lost its client rule.** Upstream reasons there is no server rule either — true of
   CoreX, false of Perego. The two word counters Perego already ships now block the submit themselves,
   rather than re-forking `corex-runtime.js` (the file that carried most of this merge's conflicts).
3. **Phone loosened**, so a bare local number passes again. `StrictPhone` is registered **alongside**
   upstream's rule, not over it.

### The mistake worth recording

`RuleRegistry::register()` **throws** on a duplicate name and offers no unregister — there is no
override seam. Registering `StrictPhone` as `phone` took the entire site down with an uncaught
`InvalidArgumentException` at `init`. It was found by loading the site, not by any test, because no
test boots the container. `strict_phone` is therefore layered onto the field alongside `phone`:
upstream's rule gives client-side feedback, ours adds the server-side country-code requirement, and it
returns the `phone` message key so the existing Arabic translation is reused.

## Reported upstream

- `MailService::deliver()` drops `$from` and `$attachments`.
- `registerListeners()` runs a duplicated listener twice (latent for Perego; both forms name one
  listener, and `FormListenersTest` now keeps it that way).
- `sanitizeList()` accepts a scalar for a list field and stores it as a string.
- `plugins/corex-config/package.json` ships a mojibaked description.

The first three are pinned by tests that assert **upstream's** behaviour, so a future fix turns them
red on purpose rather than passing unnoticed.

## Verification

| | before | after |
|---|---|---|
| Framework Pest | 1479 | **1723** (upstream added ~244) |
| Framework Jest | "191 suites / 1092" | **52 suites / 431** — see below |
| Perego Pest | 582 | **594** |
| Perego Jest | 300 | **303** |

**The framework Jest drop is a correction, not a loss.** `jest.config.js` swept in two
`.claude/worktrees/` copies — 158 duplicate test files producing 76 phantom failures
(`Cannot find module '@wordpress/interactivity'`). The same 52 suites were being run three times. The
config now excludes `<rootDir>/.claude/`, so the count is honest.

Also: `npm audit --omit=dev` and `verify:dependencies` clean on upstream's lockfile;
`@wordpress/scripts` 32 → 33 rebuilt Perego's blocks and editor panels with no breakage;
`verify-a11y` **0 serious/critical**; `verify-editor-sorting` green; `verify-visual` 72 checks /
10 failures, the same pre-existing set (4 × the home page's two `<h1>`s, 6 × routes with no content).

### Live

- **Every public route byte-identical EN and AR** apart from the two asset `?ver` values, upstream's
  `novalidate`/`enctype`, and an attribute **reorder** on form inputs. Attribute sets were compared as
  sets across all 12 routes: **zero differences**.
- A brief submission returns `ok:true` and stores its multi-value field as
  `["video-editing","graphic-design"]` — a list, not a blanked string.
- `01016999700` is rejected (`errors: {phone: phone}`); `+201016999700` is accepted.
- All nine validation messages render Arabic on `/ar/`.

## Follow-up

The `?ver` allowance is now **two** values, not one: upstream fixed the spinner shorthand, so
`corex-runtime.css` moves as well as `corex-runtime.js`. Future route comparisons should expect both.
