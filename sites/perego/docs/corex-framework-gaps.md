# CoreX Framework Gaps (found from the Perego client site)

Gaps in the CoreX framework surfaced while building Perego. Per the working rules, client code does **not**
patch a private duplicate for these — they are recorded here for a separate **CoreX Framework Mode**
task/spec. Perego continues with all work not blocked by each gap.

## GAP-1 — No public seam to register a CPT-backed Data Model source (spec 010)

**Found:** 2026-07-14. **Blocks:** Perego Projects/Services/Clients appearing in CoreX → Data Models.

`Corex\Config\Data\DataRegistry` is populated only inside the `corex-config` container singleton factory
(`ConfigServiceProvider`), with the framework `SubmissionsSource` plus `TableDataSource` instances for
**custom managed DB tables** (`ManagedTables`). There is **no `do_action`/`apply_filters` seam** and no
public adapter for registering a **CPT-backed** data source, so a client plugin cannot contribute
Projects/Services/Clients (which are WordPress CPTs, not managed tables) to the Data Models screen without
coupling to framework internals.

**Needed in CoreX:** either (a) a public registration hook, e.g. `do_action('corex/data/register_sources',
$registry)` fired after the `DataRegistry` singleton is built, or (b) a public `PostTypeDataSource` adapter
(read/query/schema over `WP_Query`) that add-ons can register. Capabilities must stay truthful (read-only
unless a real mutation adapter exists).

**Perego stance:** do NOT build a fake Data Models clone or couple to the internal singleton. Projects/
Services/Clients remain fully editable via their native post editors + registered meta (spec 010 Phase 4).
They surface in Data Models once CoreX ships the seam.

## GAP-2 — CoreX admin JS bundles are not built by the deployment (spec 010)

**Found:** 2026-07-14. The unified CoreX admin app (`plugins/corex-config/build/admin/index.js`) 404'd
because `build/` was never produced in this environment — so the admin dashboard rendered PHP heading
shells but no interactive Forms/Submissions/Data-Models UI. Built locally with the repo's hoisted
`wp-scripts` (gitignored output; no source edit). **Deployment must build CoreX admin assets**
(`npm run build --workspaces` or per-plugin `build`) or the admin app 404s on every fresh deploy. See
DECISIONS 2026-07-14 "CoreX admin bundle build gap".

## GAP-3 — CoreX form rendering emits native browser validation bubbles (pre-existing, Decision 19)

Recorded earlier (see `DECISIONS.md`): CoreX form rendering does not add `novalidate`, so the browser's
native validation bubbles appear. Perego works around it client-side; the durable fix (`novalidate` in the
public CoreX form render/config path) belongs in CoreX. Relevant to spec 016.

## Notes

- **ACF** (`advanced-custom-fields`) is active on the site but is **not** used by Perego and **not** required
  by any CoreX plugin/addon (verified 2026-07-14). Perego registers its own metadata natively — no ACF
  dependency. Whether ACF stays active is an owner decision; it is not a framework gap.
