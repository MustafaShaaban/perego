# Perego Codex adapter

This is the Codex adapter for the shared [Model Routing Gate](../docs/en/04-team-workflow/model-routing.md). The shared policy wins if this file conflicts with a root governance file.

## Default and routes

- Normal parent request: `gpt-5.6-terra` with `medium` reasoning.
- L: `perego_scout` — `gpt-5.6-luna` / `low`, read-only.
- M: Terra / medium parent; use `perego_routine_worker` only for a bounded transfer or isolation need.
- H: `perego_deep_worker` — `gpt-5.6-sol` / `high`, sole writer.
- V: `perego_critical_reviewer` — `gpt-5.6-sol` / `high`, read-only.

Start a normal session with `codex -m gpt-5.6-terra` when an explicit override is needed. Once trusted project configuration is loaded, `.codex/config.toml` requests the same default.

## Precedence and diagnostics

Project `.codex/config.toml` is loaded only for trusted projects. Parent runtime choices, explicit `--model` and `--config` overrides, approval policy, and sandbox constraints can override or constrain project and agent requests. An agent file is a configuration layer; its requested sandbox does not prove enforcement when the parent runtime overrides it.

Use `/model`, `/agent`, `codex --strict-config doctor`, and `codex --help` to inspect available behavior. Report **configured model/reasoning** separately from **runtime-confirmed model/reasoning**. If a model is unavailable, do not silently rename it: preserve the route role, select only a verified closest supported alternative, and record the substitution.

## Smoke and rollback

Use the explicit Route L/H/V prompts in the shared policy. Automatic delegation is not proof unless the client visibly reports the selected agent; otherwise record an explicit-agent result only. Disable this adapter by reverting the project `.codex/` routing files in a dedicated commit. Never edit user-level Codex configuration or use a worktree for Perego.
