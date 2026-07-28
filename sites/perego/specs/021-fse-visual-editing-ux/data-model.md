# Data model and migration contract

## Versioned display configuration

Every new display attribute/meta payload has a `version`, a validated schema, defaults equivalent to current output, and a legacy fallback reader until acceptance. IDs are stored internally only; UI exposes selected entity labels and previews.

## Relationships

- `perego_client_type` and `perego_project_category` become hierarchical without changing existing term IDs or assignments.
- A Service template uses an explicit canonical template key (`default` or `website-making`) rather than a slug.
- Service portfolio configuration stores ordered project references, exclusions, query mode, and supported placement keys; invalid/deleted references are omitted safely.
- Localized records resolve through `LanguageDriver`; absent localized media falls back using the existing approved strategy.

## Migration rules

1. Snapshot/read old value; validate target value; do not delete the source before successful verification.
2. Preserve current header/footer/home defaults as initial display configuration.
3. Convert taxonomy registration only; existing term records and object-term rows remain intact.
4. Backfill explicit Service template key from existing approved identity once, with an overridable safe default.
5. Expose a reversible migration marker and retain legacy render fallback for one release cycle.
