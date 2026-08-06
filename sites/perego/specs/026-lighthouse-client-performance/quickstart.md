# Quickstart: Lighthouse Client Performance

## Prerequisites

- WAMP services running for `http://perego.local/`
- Node dependencies installed in `sites/perego/perego-theme`
- PHP dependencies installed in `sites/perego/perego-site`
- A clean automation browser profile for Lighthouse and responsive checks

## Build

From `sites/perego/perego-theme`:

```powershell
npm.cmd run build
```

The image step reads canonical inputs from `assets/src/images` and writes the
original-format fallback plus a same-stem WebP sibling to `assets/images`.

## Focused Verification

From `sites/perego/perego-site`:

```powershell
php ../../../vendor/bin/pest tests/Blocks/ClientsCarouselRenderTest.php
php ../../../vendor/bin/pest tests/Blocks/HeroSliderRenderTest.php
php ../../../vendor/bin/pest tests/Blocks/PreloaderRenderTest.php
php ../../../vendor/bin/pest tests/Blocks/SiteHeaderRenderTest.php
php ../../../vendor/bin/pest tests/Blocks/SiteFooterRenderTest.php
```

From `sites/perego/perego-theme`:

```powershell
npm.cmd run verify:images
```

Run the repository's existing JavaScript and browser suites after the focused
checks pass.

## Manual Checks

1. Open the English and Arabic homepages.
2. Inspect the hero, client background, wave ribbon, header logo, preloader logo,
   and footer logo.
3. Confirm a supporting browser selects `.webp` while each fallback URL remains
   valid.
4. Confirm the client carousel's lightbox controls are announced as buttons and
   linked clients are announced as links.
5. Check 375, 768, and 1440 CSS-pixel viewports for overflow or visual changes.
6. Inspect a Perego-owned static response for an explicit non-zero cache
   lifetime.
7. Run Lighthouse in the clean automation profile and compare it with the
   supplied baseline.

## Rollback

Revert the feature branch commit. No database migration or content rollback is
required. If a production host does not accept the theme-level Apache rule,
remove only that deployment rule and configure the equivalent finite cache
lifetime at the host/CDN layer.
