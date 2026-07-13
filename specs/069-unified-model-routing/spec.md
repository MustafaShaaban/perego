# Feature Specification: Unified Automatic Model Routing

**Feature Branch**: `feature/perego-unified-model-routing`
**Created**: 2026-07-12
**Status**: Ready for Planning
**Input**: Implement a complete, validated, provider-neutral model-routing system for the Perego repository that supports OpenAI Codex and Anthropic Claude Code while preserving the existing Role Gate, Spec Kit, Guard Gate, single-workspace policy, and human approvals.

## Product Contract

Perego needs one shared routing policy, not competing provider playbooks. The policy selects the least expensive capable route, while the provider adapter selects the configured model, effort, tools, and sandbox for that route. A routing declaration reports configuration truthfully; it never claims that a parent session changed models or that a configuration was enforced when the runtime cannot prove it.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Start a routine Perego task cheaply and safely (Priority: P1)

A contributor starts a normal Perego task and can classify it before broad exploration or editing. Routine tasks stay in the balanced parent session unless a bounded handoff offers a real benefit.

**Why this priority**: This is the normal workflow and prevents unnecessary subagent cost without weakening existing safeguards.

**Independent Test**: Run the static validator against project configuration and inspect the root instructions and shared policy for the mandatory routing declaration and routine-parent rule.

**Acceptance Scenarios**:

1. **Given** a routine, localized task, **When** the contributor classifies it, **Then** the policy selects Route M and documents Codex Terra/medium and Claude Sonnet/medium without requiring a child agent.
2. **Given** a task with a small read-only discovery need, **When** it is classified, **Then** the policy selects Route L and a restricted explorer returns concise path-and-symbol evidence without changing Git or source files.

---

### User Story 2 - Delegate high-risk work without conflicting writers (Priority: P1)

A contributor identifies an architecture, security, migration, deployment, public-contract, or cross-module change and can use one strong sole writer followed by a read-only critical reviewer.

**Why this priority**: High-risk work needs explicit ownership, verification, and rollback planning.

**Independent Test**: Validate the Route H and V agent definitions, read-only restrictions, maximum nesting depth, and sole-writer instructions without signing in to either provider.

**Acceptance Scenarios**:

1. **Given** a high-risk change, **When** Route H is selected, **Then** the designated worker must state boundaries, risks, sequence, verification, and rollback before editing.
2. **Given** a critical change is complete, **When** Route V is selected, **Then** the reviewer is read-only and reports concrete correctness, security, compatibility, rollback, and coverage findings.
3. **Given** a high-risk writer is active, **When** another agent is considered, **Then** the policy prohibits it from editing the same scope.

---

### User Story 3 - Use either provider with the same safety policy (Priority: P1)

A team member using Codex or Claude Code receives equivalent routes, clear provider-specific setup, and honest override diagnostics.

**Why this priority**: The repository must remain usable across the two supported coding clients without configuration drift.

**Independent Test**: Parse Codex TOML, Claude JSON/YAML frontmatter, and shared English/Arabic policy references with a deterministic local command.

**Acceptance Scenarios**:

1. **Given** a trusted Codex project, **When** a new session starts, **Then** the project config requests Terra/medium with at most three threads and one delegation level.
2. **Given** a Claude Code project session, **When** it starts without an overriding environment variable, **Then** project settings request Sonnet/medium and the four project agents are discoverable.
3. **Given** an environment variable or runtime policy overrides an agent setting, **When** the contributor diagnoses routing, **Then** the documentation requires reporting configured versus runtime-confirmed values separately.

---

### User Story 4 - Hand off safely between Codex and Claude (Priority: P2)

A contributor can stop at a clean task boundary and transfer the active work from one provider to the other without losing ownership, verification evidence, or durable state.

**Why this priority**: Provider switching is safe only when the next provider can reconstruct real repository state.

**Independent Test**: Inspect the handoff checklist and validator requirements for branch, commit, task, ownership, verification, guard, and blocker records.

**Acceptance Scenarios**:

1. **Given** a provider handoff, **When** the current provider stops, **Then** active children are stopped, focused verification runs, and the spec/progress/decisions record the boundary before the next provider begins.
2. **Given** a new provider resumes, **When** it accepts the handoff, **Then** it re-runs root, branch, status, remote, log, and worktree checks and does not recreate completed work.

### Edge Cases

- A configured model is unavailable to the contributor account or provider: preserve the route role, use the documented nearest supported model only when verified, and report the substitution.
- A parent runtime sandbox or permission mode overrides an agent preference: report the effective limitation; never claim the agent restriction was enforced.
- Automatic delegation is not observable or deterministic: support explicit agent invocation and document the limitation rather than claiming automatic selection passed.
- Either CLI is absent, unauthenticated, or blocked by local policy: static validation remains runnable; runtime smoke evidence is recorded as environment-gated.
- Project-local configuration is not trusted: document that provider project configuration may not load and require the contributor to inspect effective settings.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The repository MUST define one provider-neutral Model Routing Gate that follows the Role Gate and precedes Spec Kit.
- **FR-002**: The shared policy MUST preserve the constitution, Role Gate, Spec Kit, Guard Gate, ownership, no-worktree, tests, approvals, and deployment controls.
- **FR-003**: The policy MUST define consistent L, M, H, and V routes, task criteria, escalation, de-escalation, restrictions, and provider mappings.
- **FR-004**: Route L MUST be read-only exploration and map to Codex Luna/low and Claude Haiku/low.
- **FR-005**: Route M MUST map to Codex Terra/medium and Claude Sonnet/medium and keep routine work in an equivalently capable parent unless a bounded handoff has an explicit benefit.
- **FR-006**: Route H MUST map to Codex Sol/high and Claude Opus/high, require a sole writer, and require risk, implementation, verification, and rollback planning before edits.
- **FR-007**: Route V MUST map to Codex Sol/high and Claude Opus/high, be read-only, and review the actual change against correctness, security, compatibility, rollback, tests, and acceptance criteria.
- **FR-008**: Codex project configuration MUST request `gpt-5.6-terra` with medium reasoning and cap agents at three threads and one nesting level.
- **FR-009**: The four Codex project agents MUST have unique names, supported TOML fields, precise routing descriptions, explicit model/reasoning/sandbox settings, and no nested delegation instructions.
- **FR-010**: Claude project settings MUST request `sonnet` and `medium` effort without overwriting unrelated local-only configuration.
- **FR-011**: The four Claude project agents MUST use supported YAML frontmatter, unique names, explicit route model/effort, restricted tools for explorers/reviewers, and no Agent tool.
- **FR-012**: The policy MUST document Codex and Claude configuration precedence, runtime permissions, unavailable-model behavior, environment overrides, effective-settings diagnostics, rollback, and disabling routing.
- **FR-013**: The policy MUST detect and report `CLAUDE_CODE_SUBAGENT_MODEL` and `CLAUDE_CODE_EFFORT_LEVEL` without altering the user environment.
- **FR-014**: `AGENTS.md` and `CLAUDE.md` MUST contain concise synchronized routing instructions, route declarations, route-change declarations, and configured-versus-confirmed model reporting.
- **FR-015**: The policy MUST prohibit duplicate task assignment, simultaneous cross-provider writing, more than one production writer, nested delegation beyond one level, default Ultra/ultracode, and unsupported worktree isolation.
- **FR-016**: The policy MUST define an explicit cross-provider handoff checklist with durable records and incoming-provider audit steps.
- **FR-017**: A local, unauthenticated static command MUST validate routing documentation, model configuration, agent uniqueness/restrictions, routing safety rules, bilingual references, and secret/path hygiene with actionable failures.
- **FR-018**: The validator MUST have positive and negative automated tests and be integrated into an existing lightweight verification command without making product builds depend on AI clients.
- **FR-019**: English workflow documentation and the repository’s Arabic mirror policy MUST be updated consistently.
- **FR-020**: `PROGRESS.md` and `DECISIONS.md` MUST record the routing architecture, defaults, limits, safety decisions, validation design, fallback behavior, and rollback procedure.

### Non-goals

- This feature does not change CoreX/Perego runtime, WordPress plugins, add-ons, themes, client-site functionality, generated `dist/`, or `wp/wp-content/`.
- This feature does not install, authenticate, alter global provider configuration, change a user’s environment variables, force a model entitlement, or measure/claim token savings without evidence.
- This feature does not use Claude worktree isolation, automatically merge pull requests, or replace human approval requirements.

### Key Entities

- **Route**: Shared classification (`L`, `M`, `H`, or `V`) with permitted work, risk triggers, restrictions, and adapter mappings.
- **Provider adapter**: Project configuration and custom-agent definitions that realize a Route in Codex or Claude Code.
- **Routing handoff**: Durable record of branch, commit, scope, ownership, verification, guards, remaining work, and blockers before provider transfer.
- **Validator**: Deterministic local checker for committed routing assets and safety invariants.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A contributor can classify any listed routing scenario into L, M, H, or V using the shared policy, with one unambiguous provider mapping per route.
- **SC-002**: The deterministic validator and its positive/negative tests pass without an authenticated Codex or Claude session.
- **SC-003**: All eight custom agents parse successfully; both low-cost explorers and critical reviewers have no edit capability and none exposes nested-agent capability.
- **SC-004**: Configuration enforces a declared maximum of three Codex threads and one delegation depth, and documentation prohibits simultaneous provider writers.
- **SC-005**: Every listed shared routing safeguard has a corresponding validator check or an explicitly documented environment-gated runtime check.
- **SC-006**: English and Arabic workflow documentation each link to the shared policy, and root guidance references the Model Routing Gate.

## Assumptions

- The repository’s active Perego integration base is `origin/feature/002-home`; no upstream/CoreX remote will be used for this feature.
- The officially supported model IDs are `gpt-5.6-luna`, `gpt-5.6-terra`, and `gpt-5.6-sol`; Claude aliases `haiku`, `sonnet`, and `opus` are used for portability.
- Runtime checks may be environment-gated by account availability, authentication, or client policy; static configuration checks remain mandatory and authoritative for committed assets.
- The Arabic workflow area currently uses documented mirror placeholders; the new Arabic page will preserve that policy while linking to its English canonical page.
