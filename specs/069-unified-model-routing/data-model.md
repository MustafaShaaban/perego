# Data Model: Unified Model Routing

This feature creates no application data or database schema. Its durable entities are repository configuration and documentation records.

| Entity | Durable fields | Validation rule |
|---|---|---|
| Route | id, purpose, triggers, restrictions, provider mappings | Exactly L, M, H, V; each route maps both providers. |
| Codex agent | name, description, instructions, model, reasoning, sandbox | Names unique; required fields present; scout/reviewer read-only. |
| Claude agent | name, description, model, effort, tools, permission mode, max turns | Names unique; explorer/reviewer cannot write; no Agent capability. |
| Handoff record | branch, commit, spec, task IDs, owners, changes, verification, guards, remaining work, blockers | Required before a provider switch. |
| Validation finding | file, rule, message | Deterministic and actionable; no provider authentication needed. |
