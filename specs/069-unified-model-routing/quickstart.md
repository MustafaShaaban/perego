# Quickstart: Unified Model Routing

## Normal sessions

1. Start in the Perego repository and declare the route:

   `MODEL ROUTE — <provider> · <L|M|H|V> · <parent-or-agent> · <configured model/effort> · <reason>`

2. Apply the Role Gate, then the Model Routing Gate, then Spec Kit and the Guard Gate.
3. For routine work, remain in the balanced parent. Delegate only a bounded, non-overlapping need.
4. For H, assign one sole writer; for V, assign one read-only reviewer after critical changes.

## Verify committed configuration

```powershell
npm run verify:model-routing
node --test scripts/test/verify-model-routing.test.mjs
codex.cmd --strict-config doctor
claude.cmd doctor
```

The last two commands may be environment-gated. Record their actual output; do not claim that they validate models, agent selection, or permissions if they do not.

## Smoke prompts

- L: “Use `perego_scout` / `@perego-explorer` to locate login-protection persistence. Do not edit.”
- M: “Classify this bounded documentation correction as Route M; keep the parent as the only writer unless isolation is needed.”
- H: “Use `perego_deep_worker` / `@perego-deep-worker` to plan a hypothetical authorization-schema change. Do not implement.”
- V: “Use `perego_critical_reviewer` / `@perego-critical-reviewer` to review this security-sensitive diff without writes.”

Configured model values are not runtime proof. Record agent discovery, runtime model identity, and environment blockers separately.
