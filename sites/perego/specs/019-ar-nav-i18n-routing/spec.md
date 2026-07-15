# Spec 019 — AR primary-nav i18n routing

**Branch:** `feature/019-ar-nav-i18n-routing`
**Mode:** Client Site Mode
**Status:** Complete (2026-07-15).
**Depends on:** 011 (global shell), Polylang EN/AR.

## Goal

Close the one documented residual from spec 018: the primary nav (header + footer) built every link with
`home_url($path)`, so on `/ar/` pages the nav pointed at the **English** base URLs (`/services`, `/work`,
`/journal`, `/contact`, …). Make every nav link resolve to the **current locale's** URL, honouring
Polylang's translated slugs (`contact` → `contact-2`, the blog → `/ar/المدونة/`, home → `/ar/الرئيسية/`).

## Design

Extend the existing language abstraction rather than call Polylang from the renderers (constitution IX —
consumers depend only on `LanguageDriver`). New interface method:

```php
public function localizedUrl(string $path): string;
```

- **`FallbackLanguageDriver`** (no Polylang): `home_url($path)` — one URL set, language swapped
  client-side; unchanged behaviour.
- **`PolylangLanguageDriver`** resolves four target shapes, always degrading to the plain URL on failure:
  1. home / in-page anchors (`/`, `/#about`) → `pll_home_url($locale)` (+ preserved fragment);
  2. a real page / CPT single (`/contact`, `/services/<slug>`) → `url_to_postid` → `pll_get_post` →
     translation permalink;
  3. the blog index (`/journal`, the `page_for_posts`) → the posts page **normalised to the default
     language** (Polylang filters `page_for_posts`/`get_permalink` on non-default requests) then
     translated → e.g. `/ar/المدونة/`;
  4. a CPT archive (`/work`) → the path under the language directory prefix → `/ar/work/`.

`SiteHeaderRenderer` (logo, CTA, nav items, dropdown children) and `SiteFooterRenderer` (logo, Terms,
Privacy) now call `$driver->localizedUrl(...)` instead of `home_url(...)`. `isActive()` still compares the
raw path, so active-state is unaffected.

## Acceptance

- [x] EN nav unchanged (canonical permalinks, all 200; no `/ar/` leakage).
- [x] AR nav fully localized and every target resolves **200** (not a 301-back-to-EN):
  home `/ar/الرئيسية/`, work `/ar/work/`, journal `/ar/المدونة/`, contact `/ar/contact-2/`, service
  singles `/ar/services/<slug>-2/`; footer Terms `/ar/terms-2/`, Privacy `/ar/privacy-2/`.
- [x] No console errors (`output/playwright/019-ar-home-nav.png`).
- [x] Pest green (268/268); clean-code-guard + wp-guard pass.

## Notes

The language *switcher* and per-page content were already correct; this was purely the nav-href routing.
No plugin becomes a hard dependency — the Polylang driver is only instantiated when Polylang is active,
and the fallback keeps single-URL behaviour.
