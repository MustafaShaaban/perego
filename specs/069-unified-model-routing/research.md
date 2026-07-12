# Research: Unified Model Routing

## Decision 1: One shared policy with provider adapters

- **Decision**: Put route definitions, escalation, single-writer, handoff, and rollback in `docs/en/04-team-workflow/model-routing.md`; keep provider-specific operational detail in `.codex/MODEL-ROUTING.md` and `.claude/MODEL-ROUTING.md`.
- **Rationale**: A shared policy prevents Codex and Claude behavior from drifting while keeping client-specific configuration readable.
- **Rejected**: Independent provider policies. They duplicate safety rules and make parity untestable.

## Decision 2: Use supported project-scoped configuration surfaces

- **Codex**: `.codex/config.toml` is loaded for trusted projects. Standalone files in `.codex/agents/` require `name`, `description`, and `developer_instructions`; they can also define `model`, `model_reasoning_effort`, and `sandbox_mode`.
- **Claude Code**: `.claude/settings.json` is the committed project configuration. `.claude/agents/*.md` uses YAML frontmatter; supported fields include `name`, `description`, `tools`, `disallowedTools`, `model`, `permissionMode`, `maxTurns`, and `effort`.
- **Rationale**: These are currently documented and validated by the installed Codex CLI and Claude Code versions.

## Decision 3: Explicit models and bounded delegation

| Route | Codex | Claude Code | Write permission |
|---|---|---|---|
| L | `gpt-5.6-luna` / `low` | `haiku` / `low` | No |
| M | `gpt-5.6-terra` / `medium` | `sonnet` / `medium` | Parent or one bounded worker |
| H | `gpt-5.6-sol` / `high` | `opus` / `high` | One sole writer |
| V | `gpt-5.6-sol` / `high` | `opus` / `high` | No |

- **Decision**: Codex defaults to Terra/medium, limits open threads to 3, and limits delegation depth to 1. Claude defaults to Sonnet/medium. Neither Ultra nor ultracode is enabled by default.
- **Rationale**: The balanced parent handles routine work without paying the context and coordination cost of a same-capability child; stronger models are reserved for evidence-based risk.

## Decision 4: Conservative tool restrictions

- **Decision**: Claude explorer and reviewer receive only `Read`, `Grep`, and `Glob`; Claude writers omit `Agent`, preventing nested delegation. Codex explorer and reviewer use `read-only`; writers use `workspace-write` but are constrained by sole-writer instructions.
- **Rationale**: Tool allowlists provide a deterministic static signal without trying to override parent runtime permissions, which can take precedence.

## Decision 5: Static validator first; runtime smoke tests are honest

- **Decision**: Implement `npm run verify:model-routing` with Node built-ins only, plus fixture-driven positive/negative tests. It validates repository assets without credentials. Runtime client diagnostics and explicit-agent smoke prompts are documented and reported separately.
- **Rationale**: Model identity, entitlement, automatic delegation, and parent permission inheritance cannot be conclusively tested in every CI or developer environment.

## Decision 6: Environment precedence and fallback

- **Decision**: Document Claude settings precedence (managed, CLI, local, project, user) and `CLAUDE_CODE_SUBAGENT_MODEL` / `CLAUDE_CODE_EFFORT_LEVEL`. Document Codex project-trust, parent runtime overrides, CLI model overrides, and agent inheritance. Do not commit environment mutation.
- **Rationale**: A configuration can request a model or permission without guaranteeing the runtime will honor it.

## Official evidence

- OpenAI’s current configuration reference documents project `.codex/config.toml`, `agents.max_threads`, `agents.max_depth`, `model_reasoning_effort`, and sandbox modes: https://developers.openai.com/codex/config-reference/
- OpenAI’s current subagent guide documents standalone `.codex/agents/*.toml` files and their required fields: https://learn.chatgpt.com/docs/agent-configuration/subagents
- Anthropic’s subagent guide documents `.claude/agents/`, YAML frontmatter, tool restrictions, models, effort, and permission modes: https://code.claude.com/docs/en/sub-agents
- Anthropic’s settings and environment references document project settings precedence and the two relevant override variables: https://code.claude.com/docs/en/settings and https://code.claude.com/docs/en/env-vars
