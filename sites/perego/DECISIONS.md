# Perego — Decision Log

Record each non-trivial decision (context · decision · why · status).

## 2026-07-11 — This repo is a CoreX checkout with the client site nested at sites/perego/

**Context**: initially set up as two separate directories (a standalone CoreX checkout at
`C:\wamp64\www\corex`, and an independent WordPress install at `C:\wamp64\www\perego` referencing it
via cross-directory junctions) — a real test of CoreX, so a real fork-and-build workflow, not a
convenience shortcut.

**Decision**: `C:\wamp64\www\perego` is instead a full CoreX framework checkout in its own right —
`origin` = `git@github.com:MustafaShaaban/perego.git`, `upstream` = `git@github.com:MustafaShaaban/corex.git`
(merged at `v0.33.0`, `--allow-unrelated-histories`). The client site is generated at `sites/perego/`
inside it, exactly matching CoreX's own documented `sites/<client>/` convention. `C:\wamp64\www\corex`
(the separate checkout) is not referenced anywhere in this project.

**Why**: this is meant to validate the framework's real intended consumption model — fork, build a
client site under `sites/<client>/`, and be able to pull framework updates via `git fetch upstream` —
not a shortcut that happens to boot a site.

**Status**: done. Follow-on: pulling future `upstream` updates should go through the same merge
pattern used here (fetch, merge `--allow-unrelated-histories` only needed for this first merge since
histories were unrelated; ordinary `git merge upstream/main` going forward).

## 2026-07-11 — Client feature work uses CoreX's native Spec Kit, not ad hoc plans

**Context**: the environment bootstrap above was executed via hand-written PowerShell + ad hoc task
tracking, before this checkout had CoreX's own Spec Kit scaffolding available to it.

**Decision**: every feature from here on (`M2` onward per the design doc) goes through
`/specify → /clarify → /plan → /tasks → /implement`, the same flow `COREX-SPECKIT-START.md` and this
repo's own `AGENTS.md`/`CLAUDE.md` mandate, with the guard skills as the quality gate.

**Why**: this is a real test of CoreX's own workflow, not just its code — using a different planning
system for the client site than the framework itself uses would defeat that.

**Status**: done for this repo's tooling availability; each milestone spec still to be written.

## 2026-07-11 — perego-site tests need their own Pest config + ABSPATH-defining bootstrap

**Context**: the root `phpunit.xml.dist` only covers the framework's own `tests/Unit` — a client
site's Pest suite needs its own config. Running the `--starter` example's `ExampleTest.php` through a
naive bootstrap (autoload only) produced **zero output and exit code 0** — no error, no failure, just
silence. Traced it (expensive — looked like a PHP crash at first) to every generated `PeregoSite\*`
class carrying `defined('ABSPATH') || exit;` (the same direct-access guard convention as Corex's own
classes, see the root repo's `DECISIONS.md` #20). Outside WordPress, `ABSPATH` is undefined, so the
guard's `exit;` fires — silently, since bare `exit` with no argument prints nothing.

**Decision**: added `sites/perego/perego-site/phpunit.xml.dist` (own `testsuite`, bootstrap) +
`tests/bootstrap.php` that defines `ABSPATH` (and requires the root Composer autoloader) before any
`PeregoSite\` class loads — mirroring the root `tests/bootstrap.php` pattern exactly, just scoped to
this client site.

**Why**: this is the established framework convention, not a bug to work around differently; matching
it exactly keeps client tests consistent with how the framework tests itself.

**Status**: done. Run via `cd sites/perego/perego-site && php ../../../vendor/bin/pest`.
