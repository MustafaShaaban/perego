# Tasks: Unified Automatic Model Routing

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [quickstart.md](quickstart.md)

## Phase 1: Specification and governance

- [X] T001 [US1] Validate the completed Spec Kit artifacts and requirements checklist in `specs/069-unified-model-routing/`.
- [X] T002 [US1] Add the shared Model Routing Gate to `docs/en/04-team-workflow/model-routing.md`.
- [X] T003 [P] [US1] Add the Arabic mirror-policy page at `docs/ar/04-team-workflow/model-routing.md`.
- [X] T004 [US1] Add concise synchronized routing requirements to `AGENTS.md` and `CLAUDE.md`.
- [X] T005 [US1] Update `COREX-WORKING-GUIDE.md`, English/Arabic workflow indexes, and agent start prompts with Model Routing Gate ordering.

## Phase 2: Provider adapters

- [X] T006 [US1] Create `.codex/config.toml` with Terra/medium and bounded agent orchestration.
- [X] T007 [P] [US2] Create four Codex agent definitions under `.codex/agents/` with route-specific models, effort, sandbox, and instructions.
- [X] T008 [US3] Create `.codex/MODEL-ROUTING.md` with precedence, diagnostics, smoke prompts, fallback, disabling, and rollback.
- [X] T009 [US1] Create `.claude/settings.json` with Sonnet/medium while preserving no unrelated project configuration.
- [X] T010 [P] [US2] Create four Claude agent definitions under `.claude/agents/` with route-specific supported frontmatter and no nested Agent tool.
- [X] T011 [US3] Create `.claude/MODEL-ROUTING.md` with precedence, environment overrides, diagnostics, smoke prompts, fallback, disabling, and rollback.

## Phase 3: Deterministic validation

- [X] T012 [US3] Write positive and negative fixture cases under `scripts/test/fixtures/model-routing/`.
- [X] T013 [US3] Implement `scripts/verify-model-routing.mjs` with actionable checks for docs, TOML, JSON, frontmatter, models, routes, safety rules, and hygiene.
- [X] T014 [US3] Write `scripts/test/verify-model-routing.test.mjs` before finalizing the validator behavior.
- [X] T015 [US3] Add `verify:model-routing` and validator-test package scripts in `package.json`.

## Phase 4: Durable handoff and completion

- [X] T016 [US4] Update `PROGRESS.md` and `DECISIONS.md` with architecture, safety, precedence, fallback, handoff, validation, and rollback records.
- [X] T017 [US3] Run the read-only Spec Kit consistency analysis across spec, plan, and tasks; resolve every material finding.
- [X] T018 [US3] Run `npm run verify:model-routing`, validator tests, JSON/TOML/frontmatter checks, and available Codex/Claude diagnostics.
- [X] T019 [US3] Execute bounded explicit-agent runtime smoke checks when authenticated sessions permit; document each environment-gated result honestly.
- [X] T020 [US3] Run docs, clean-code, and test guards; run `git diff --check` and changed-file secret/path scans.
- [ ] T021 [US4] Review the final diff, mark all completed tasks, commit, push only to `origin`, create/update a Perego PR, and inspect its CI. **Blocked externally:** the active GitHub CLI token is invalid; commit `3386012` is pushed to Perego origin and awaits `gh auth login -h github.com`.

## Dependencies

T001 gates T002-T016. T012 precedes T013-T015. T002-T011 and T013-T016 precede validation. T017-T020 precede T021. Only one writer works at a time; `[P]` tasks are file-disjoint but remain sequential in this implementation to preserve the single-writer policy.
