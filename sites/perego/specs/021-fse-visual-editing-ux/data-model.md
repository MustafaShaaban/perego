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

## Client behaviour model (owner, 2026-07-28)

A client card's action is stored, not inferred.

| Meta | Shape | Default | Notes |
|---|---|---|---|
| `_perego_client_behavior` | `none` \| `lightbox` \| `link` | `none` | An unrecognised value sanitizes to `none` — never to an action. |
| `_perego_client_gallery` | `[{type: image\|video, id: int, url: string}]` | `[]` | One ordered list for both client types. An image row is an attachment id; a video row is a URL that may also carry the attachment it came from. |
| `_perego_client_link_{url,kind,post_type,post_id,new_tab}` | the flat `LinkTarget` set | — | Same contract as every editable link (T036), so a client may link to an internal record and survive a rename. |
| `_perego_client_hide_play_icon` | boolean | `false` | Stored **inverted**. WordPress writes a `false` boolean as `''`, indistinguishable from unset, so a default-true `show_play_icon` could never be switched off. The UI still shows a positive "Show play icon" switch. |
| `_perego_client_stat` | string, inline `<strong>` only, ≤ `SUBTITLE_MAX_CHARS` visible chars | `''` | Unchanged key; relabelled **Subtitle** in the editor. Capped at 30 visible characters (markup is free) so a long value cannot grow the card — measured from the rendered box, which fits 15 characters per line at its narrowest desktop breakpoint. |

Retired: `_perego_client_sub` (→ the post's own content), `_perego_client_video_url` and
`_perego_client_video_type` (→ behaviour + gallery, or behaviour + link).

### Client migration rules

`scripts/migrate-client-behavior.php`, idempotent and `--dry-run`-able, sweeping EN and AR:

1. Snapshot the retired meta **and** `post_content` into `_perego_client_migration_backup` before the
   first write; the presence of `_perego_client_behavior` is the "already migrated" marker.
2. Derive, in order: an `external` video → `link` (new tab, as before); any other video → `lightbox` with
   that video first in the gallery; otherwise a non-empty gallery → `lightbox`; otherwise → `none`.
3. Move a non-empty `_perego_client_sub` into the post body as a paragraph block, skipping a post whose
   content already contains the text.
4. Delete the retired keys only after the new ones are in place.

**Accepted behaviour change**: a corporate tile with no gallery previously opened its own logo in the
lightbox. That implicit fallback is removed — such tiles become static, which is what `No actions` means.

## Project tile model (owner, 2026-07-28)

| Meta | Shape | Notes |
|---|---|---|
| `_perego_project_icon` | `none` \| `play` \| `gallery` | What the tile advertises, in both grids. No registered default — see below. |
| `_perego_thumb_hero` | attachment id, 578×332 (1.74:1) | Mosaic `m1`/`m11`/`m12`, and every tile ≤560px |
| `_perego_thumb_banner` | attachment id, 380×158 (2.41:1) | `m2`/`m4`/`m6`/`m10`/`m15` |
| `_perego_thumb_tall` | attachment id, 182×332 (0.55:1) | `m3`/`m7`/`m14` |
| `_perego_thumb_card` | attachment id, 406×254 (1.60:1) | `m5`/`m8`/`m9`/`m13`, the load-more grid, the work grid, and the tablet band |

**No `default` on any of them.** Since WP 5.5 a registered default is returned by `get_post_meta()` for
a key that was never written, so "unset" becomes indistinguishable from "explicitly none/zero" — which
breaks the migration's already-done marker and stops an Arabic project inheriting its English record's
icon and crops. Established the hard way on the client behaviour field; pinned by a test on both.

**Resolution order** (`PostTypes\ProjectThumbnails::idFor()`): the shape's own crop → the nearest
shape by aspect ratio that the editor did upload → the featured image. Each step follows the linked
English record. Integer metas are read directly rather than through `TranslatedMeta`, because an unset
one answers `''` but a stored zero answers `"0"` and only the first should fall through.

**Rendering** (`Blocks\ProjectTileImage`): a `<picture>` with `<source media="(max-width: 560px)">` and
`(max-width: 900px)` over an `<img>` carrying the desktop slot's crop. When one crop serves every band
the wrapper is omitted entirely, so an un-cropped project emits the markup it always did.

### Project migration rules

`scripts/migrate-project-icon.php`: video → `play`; 2+ gallery images → `gallery`; else `none`. A
derived `none` is left **unwritten** so translations keep inheriting. Idempotent via
`metadata_exists()`, snapshots into `_perego_project_migration_backup`, sweeps EN and AR.
