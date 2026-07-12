# Implementation Plan: Unified Automatic Model Routing

**Branch**: `feature/perego-unified-model-routing` | **Date**: 2026-07-12 | **Spec**: [spec.md](spec.md)

## Summary

Create one provider-neutral routing gate and two thin provider adapters. Add project-scoped Codex and Claude configuration, custom agents, root guidance, bilingual workflow documentation, a Node-only static validator with tests, and durable progress/decision records. No product runtime code changes are permitted.

## Technical Context

**Language/Version**: Node.js 20+ for validation; TOML, JSON, YAML-frontmatter, and Markdown configuration
**Primary Dependencies**: Node built-ins only; no provider SDK, credential, or external parser dependency
**Storage**: Repository files only
**Testing**: `node --test` fixture tests plus static validator
**Target Platform**: Windows/macOS/Linux developer workstations and CI
**Project Type**: Repository developer tooling and documentation
**Performance Goals**: Complete static validation in under five seconds on a normal checkout
**Constraints**: No AI-client authentication, product build dependency, global settings, worktrees, secrets, absolute local paths, or runtime product changes
**Scale/Scope**: Eight agent definitions, two provider configuration files, shared and provider policy documents, root guidance, validator and fixtures

## Constitution Check

- [x] **I-VI**: N/A — no theme, plugin, architecture, asset, or styling changes.
- [x] **VII**: PASS — routing adds no runtime security behavior; it strengthens process controls.
- [x] **VIII**: PASS — Arabic mirror policy is retained; no visible product UI changes.
- [x] **IX**: PASS — no optional WordPress dependency is added.
- [x] **X**: PASS — Spec 069, research, plan, tasks, and verification precede implementation.
- [x] **Guard Gate + Definition of Done**: Docs, production-tooling, and test guards will review the finished diff; PROGRESS and DECISIONS will be updated.

## Project Structure

```text
.codex/
├── config.toml
├── MODEL-ROUTING.md
└── agents/*.toml
.claude/
├── settings.json
├── MODEL-ROUTING.md
└── agents/*.md
docs/en/04-team-workflow/model-routing.md
docs/ar/04-team-workflow/model-routing.md
scripts/verify-model-routing.mjs
scripts/test/verify-model-routing.test.mjs
scripts/test/fixtures/model-routing/*
specs/069-unified-model-routing/*
```

## Implementation Sequence

1. Create provider-neutral policy and concise root routing gate references.
2. Add supported Codex and Claude project configuration plus exactly four agents per provider.
3. Implement fixture-driven static validation and add a package script.
4. Add bilingual navigation/start-prompt references, durable decision/progress records, and usage/rollback guidance.
5. Run static tests, parser checks, client diagnostics, focused smoke prompts where authenticated execution is safely available, then guards and final diff review.

## Rollback

Remove the project `.codex/` and `.claude/` routing assets, validator script/entry, and routing references in one reverting commit. This only restores prior developer-tooling behavior; it does not alter user/global provider settings or application data.

## Complexity Tracking

No constitution violations or exceptions are required.
