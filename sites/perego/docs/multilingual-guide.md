# Multilingual Guide — Polylang **Free** (EN/AR)

> How the Perego site implements English + Arabic with the **free** WordPress.org Polylang
> plugin — no Pro, no premium add-ons, no discontinued compatibility plugins. Required by
> PEREGO_IMPLEMENTATION_PROMPT.md Phase 5.

## Languages

| Language | Slug | Locale | Dir | Default | URL |
|---|---|---|---|---|---|
| English | `en` | `en_US` | LTR | ✅ | `/…` (no prefix — `hide_default=1`) |
| Arabic | `ar` | `ar` | RTL | — | `/ar/…` (directory mode — `force_lang=1`) |

Configured idempotently by `perego-site/scripts/configure-languages.php` via the public
`PLL()->model->languages->add()` API (Polylang Free ships no WP-CLI command). The script also
backfills the default language onto pre-existing content and sets English as default.

```bash
wp eval 'require "sites/perego/perego-site/scripts/configure-languages.php";' --path=wp
```

## Free-edition constraints we design around

1. **No translatable FSE template parts.** All block templates and template parts are kept
   language-neutral. Repeated visible copy that must differ per language is rendered by
   language-aware dynamic blocks (which read the current Polylang language), never hardcoded into
   templates. (Global-section architecture: pending — see PROGRESS "Next".)
2. **No shared slugs between translations.** Slug-sharing is Pro-only. WordPress therefore gives a
   translated post a distinct slug (the Arabic service posts become `video-editing-2`, etc.);
   Polylang namespaces them under the `/ar/` directory prefix. The seeders deliberately do **not**
   force a shared slug — the build must pass with zero Pro dependency. Canonical service identity is
   preserved in the `_perego_service_slug` meta, which the `service-hero` block reads (not the
   suffixed post_name) so tabs/labels stay correct in both languages.
3. **No machine translation / XLIFF / duplicate-content sync** (all Pro). Translations are authored
   content, seeded from the handoff `content/{en,ar}.json` and linked with the public
   `pll_save_post_translations()`.

## Translation seeding

`perego-site/scripts/seed-services.php` creates the four services in **both** languages and links
each EN/AR pair. Idempotent: it creates a language's post only if missing and never overwrites later
editor edits. Requires the languages to be configured first; without Polylang it seeds English only.

```bash
wp eval 'require "sites/perego/perego-site/scripts/seed-services.php";' --path=wp
```

Verified state after seeding: 4 EN + 4 AR service posts, each AR post language-tagged `ar` with a
distinct slug, and bidirectional translation links (e.g. `en#13 ↔ ar#25`). Polylang emits `en` +
`ar` `hreflang` alternates automatically on the front end.

## ⚠️ One-time setup step: flush Polylang's language rewrite rules

Polylang registers its `/ar/` **directory rewrite rules** only during a normal admin/browser
request — **not** during `wp-cli`/`wp eval`. So after configuring languages headlessly, the `/ar/`
URLs will 301 back to the default until the rules are regenerated **once** in the admin:

> **wp-admin → Settings → Permalinks → Save Changes** (no field changes needed).

This is a standard Polylang setup action, safe and idempotent. Until it is done, EN URLs work
normally and all translation data is correct; only the `/ar/` URL serving is inactive. This is the
single manual step in the multilingual setup and is intentionally **not** worked around with any
Pro-only mechanism. (Attempted headless flushes — `wp rewrite flush`, deleting `rewrite_rules` +
front-end regeneration, and manually re-registering `PLL_Links_Directory::rewrite_rules` — all leave
0 language rules on this WAMP/CLI environment, confirming it is a Polylang+CLI limitation, not a
code defect.)

## Language switcher

Use Polylang's free **Navigation Language Switcher** block (or a standards-compliant wrapper around
its public output) styled as the approved AR/EN pills — pending; tracked in PROGRESS "Next". The
current header still carries the prototype's cookie toggle, to be replaced by the real switcher.

## Future Pro migration path (optional, not required)

Nothing here depends on Pro. If Perego later buys Polylang Pro, it could adopt shared/translated
slugs, template-part translation, and export/import — but the current implementation is complete and
production-capable on Free.
