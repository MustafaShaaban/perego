---
title: Model Routing Gate
description: Provider-neutral automatic routing for Codex and Claude Code without weakening Perego governance.
audience: contributor
stability: stable
last_verified: 2026-07-12
---

# Model Routing Gate

The Model Routing Gate selects the least expensive route that can reliably complete the current work. It is a shared policy for Codex and Claude Code; provider files only adapt the route to supported configuration.

> **Role Gate** decides where work belongs → **Model Routing Gate** decides capability and provider adapter → **Spec Kit** defines what to build → **Guard Gate** decides whether the result is safe to ship → **UI/UX review** decides whether visible work is acceptable.

The routing gate never replaces the constitution, ownership, single-workspace policy, no-worktree rule, tests, Guard Gate, approvals, or deployment controls.

## Declare the route truthfully

Before broad exploration or editing, say:

`MODEL ROUTE — <provider> · <L|M|H|V> · <parent-or-agent> · <configured model/effort> · <reason>`

When evidence changes the route, say:

`MODEL ROUTE CHANGE — <old> → <new> · <specific trigger>`

This declaration reports a route and requested configuration. It does **not** claim the parent model changed. Report whether a child was actually spawned and distinguish configured values from runtime-confirmed values. If routing cannot be confirmed, state the limitation.

## Route table

| Route | Use when | Codex | Claude Code | Restrictions |
|---|---|---|---|---|
| **L — low-cost exploration** | Targeted file/symbol discovery, execution mapping, logs, test-output summaries, inventories, spec comparison, structured facts, ownership boundaries | `perego_scout`: Luna / low | `@perego-explorer`: Haiku / low | Read-only; no implementation, redesign, production decision, Git-state change, or broad scan when targeted reads suffice. Return exact paths/symbols and concise evidence. |
| **M — routine work** | Clear localized implementation, small tested fix, documentation, bounded CRUD, mechanical refactor, focused test, existing-pattern UI correction, low-risk configuration | Terra / medium parent or bounded `perego_routine_worker` | Sonnet / medium parent or bounded `@perego-routine-worker` | Keep work in an equivalently capable parent unless isolation, explicit ownership transfer, or a mechanical bounded task has a real benefit. |
| **H — complex/high-risk** | Architecture, public contract, schema/migration, data import/export, auth/authz, secrets, deployment/release, cross-module change, ambiguous root cause, difficult reversal, more than five connected files, two failed routine attempts, or milestone integration | `perego_deep_worker`: Sol / high | `@perego-deep-worker`: Opus / high | One sole writer. Before editing state boundaries, risks, sequence, verification, and rollback. |
| **V — critical verification** | After security, auth/authz, data/migration, deployment/release, public contract, cross-module architecture, or milestone completion | `perego_critical_reviewer`: Sol / high | `@perego-critical-reviewer`: Opus / high | Read-only review of actual diff and execution paths: correctness, regressions, security, integrity, compatibility, failure handling, rollback, tests, acceptance criteria, and scope. |

## Escalate and de-escalate on evidence

Escalate to H when a Route M investigation exposes a listed high-risk condition, when two reasonable routine attempts fail, or when a bounded change gains material cross-module or rollback risk. Escalate to V only after the listed critical change classes or evidence of meaningful risk; trivial formatting and ordinary documentation do not need V.

De-escalate only when evidence reduces the work to a clearly bounded, reversible, localized task. Record the route change and trigger. Task length alone is not an escalation signal.

## Single-writer and token discipline

- Never give the same task to multiple agents.
- Never run Codex and Claude as simultaneous writers on the same branch or files.
- At most one production-code writer is active at a time; parallel agents are normally read-only and have non-overlapping scopes.
- Do not spawn a subagent when the parent already has sufficient context and capability.
- Pass only relevant paths, spec sections, evidence, and acceptance criteria; return distilled summaries, not raw logs.
- Nesting stops at one level. Codex caps threads at three and depth at one.
- Do not enable Codex Ultra or Claude ultracode by default. Do not use Sol/Opus merely because they exist, and never use Luna/Haiku for architecture, security, data integrity, releases, or ambiguous implementation decisions.
- Do not claim savings without measured evidence or run duplicate reviews.

## Provider adapters and precedence

- [Codex adapter](../../../.codex/MODEL-ROUTING.md): project config requests Terra/medium; inspect `/model`, `/agent`, `codex --strict-config doctor`, and effective parent sandbox/approval constraints. Project config loads only in a trusted project.
- [Claude Code adapter](../../../.claude/MODEL-ROUTING.md): project settings request Sonnet/medium; inspect `/model`, `/effort`, `/agents`, `/doctor`, and `claude doctor`. Managed/CLI/local settings can override project settings.

For Claude, report whether `CLAUDE_CODE_SUBAGENT_MODEL` or `CLAUDE_CODE_EFFORT_LEVEL` is set. The former can override per-agent model selection; the latter overrides effort settings. Do not modify either variable or a user’s global configuration.

If a model is unavailable, keep the route role, choose a closest **verified** supported substitute, and record it. Never silently substitute. A requested sandbox or permission mode is not runtime proof when the parent runtime overrides it.

## Cross-provider handoff

Before switching active provider:

1. Stop active children and complete or explicitly abandon the current task boundary.
2. Run focused verification; update task state, `PROGRESS.md`, and non-trivial decisions.
3. Commit, or explicitly document intentional uncommitted state.
4. Record branch, commit, spec path, task IDs, owned/modified files, tests, guards, remaining work, and blockers.
5. The incoming provider re-runs repository root, branch, status, remote, log, and worktree checks; it continues from the latest real state and never recreates undocumented work.

## Runtime validation and rollback

Static validation is mandatory: `npm run verify:model-routing` and its fixture tests run without provider authentication. Run the bounded L/M/H/V explicit-agent smoke prompts in each adapter only when the client is installed, authenticated, and permitted. Record configured model, discovered agent, runtime-confirmed model, and environment-gated result separately; automatic delegation is nondeterministic unless the client visibly reports it.

To disable routing, revert the project `.codex/` and `.claude/` routing files plus the shared references in one dedicated commit. Do not alter user/global provider settings, credentials, or environment variables. This rollback does not affect Perego product runtime or data.
