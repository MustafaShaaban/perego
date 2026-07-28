# Current editor and render inventory

## Verified sources

| Component | Editor source | Public renderer | Present defect / migration target |
|---|---|---|---|
| Header | `perego-site/src/Blocks/site-header/index.js` | `SiteHeaderRenderer.php` | Inspector/form note rather than the `.site-header` DOM; nav is a locale JSON attribute with a hardcoded Services seed. |
| Footer | `perego-site/src/Blocks/site-footer/index.js` | `SiteFooterRenderer.php` | Fieldset/repeater form rather than `.site-footer` DOM; logo, legal/copyright and form/careers composition lack controlled visual treatment. |
| Hero | `perego-site/src/Blocks/hero-slider/index.js` | `HeroSliderRenderer.php` | Fixed three-slide field/switcher editor rather than parent/child visual composition. |
| Services teaser | `perego-site/src/Blocks/services-teaser/index.js` | `ServicesTeaserRenderer.php` | Text/config editing must be replaced by entity-driven visual card selection. |
| Clients | `perego-site/src/Blocks/clients-carousel/index.js` | `ClientsCarouselRenderer.php` | Current editor needs explicit query/manual/hybrid content model and stable carousel preview. |
| Client Type | `PostTypes/ClientPostType.php` | Client queries | `hierarchical` is false. |
| Project Service | `PostTypes/ProjectPostType.php` | Portfolio/service queries | `hierarchical` is false. |
| Client editor | `Admin/ClientMediaMetaBox.php` | Client renderers | Media picker exists but gallery uses hidden JSON, inline styles, prompt dialogs, and raw technical meta field names. |

## Invariants for implementation

1. The PHP renderers and handoff classes define public DOM/behavior; editor code must use those contracts or editor-only equivalents.
2. Current empty/invalid attributes fall back to seeds. All migrations preserve that read fallback until accepted.
3. The public header has localized URLs and optional Polylang behavior through `LanguageService`; no editor contract may hardcode a locale route.
4. Header, footer, hero, and portfolio public interactivity stays in existing view modules; editor previews use safe non-navigating state only.
5. `theme.json` tokens and the existing reference stylesheet remain the only visual source; editor-specific CSS must be scoped to `.editor-styles-wrapper`.
