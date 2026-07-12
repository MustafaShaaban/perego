# Perego Claude Code adapter

This is the Claude Code adapter for the shared [Model Routing Gate](../docs/en/04-team-workflow/model-routing.md). The shared policy wins if this file conflicts with a root governance file.

## Default and routes

- Normal parent request: `sonnet` with `medium` effort.
- L: `@perego-explorer` — `haiku` / `low`, read-only.
- M: Sonnet / medium parent; use `@perego-routine-worker` only for a bounded transfer or isolation need.
- H: `@perego-deep-worker` — `opus` / `high`, sole writer.
- V: `@perego-critical-reviewer` — `opus` / `high`, read-only.

Start a normal session with `claude --model sonnet --effort medium` when an explicit override is needed. Once project settings load, `.claude/settings.json` requests the same default.

## Precedence and environment safeguards

Claude applies managed settings first, then command-line choices, local settings, project settings, and user settings. Parent permission mode can override an agent `permissionMode`; `plan` in frontmatter is a requested restriction, not proof against a stronger parent mode.

At session start inspect and report these variables if present, without changing them:

- `CLAUDE_CODE_SUBAGENT_MODEL`: can force a subagent model; leave unset (or use supported `inherit`) when per-agent selection should apply.
- `CLAUDE_CODE_EFFORT_LEVEL`: overrides `/effort` and `effortLevel`; leave unset when project and agent effort should apply.

Use `/model`, `/effort`, `/agents`, `/doctor`, `claude agents`, and `claude doctor` to inspect the actual client. Report **configured model/effort**, **selected agent**, and **runtime-confirmed model/effort** separately. If automatic selection is not observable, test an explicit `@agent` invocation and label it as such.

## Fallback, disabling, and rollback

If a configured alias is unavailable, preserve the route role and document a verified substitution; do not silently pin an unverified full model ID. Disable this adapter by reverting the project `.claude/` routing files in a dedicated commit. Do not alter global user settings, environment variables, credentials, or Claude worktree isolation.
