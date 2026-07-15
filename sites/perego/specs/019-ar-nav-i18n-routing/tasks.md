# Spec 019 — Tasks

- [x] T001 **Diagnose.** Confirmed header + footer nav built links with `home_url($path)`, so AR pages
    linked to EN base URLs. Mapped each nav target's correct AR URL via Polylang (`url_to_postid` +
    `pll_get_post` for pages/singles; `pll_home_url` for home/anchors; posts-page translation for
    `/journal` → `/ar/المدونة/`; language prefix for the `/work` archive → `/ar/work/`).
- [x] T002 **Abstraction.** Added `localizedUrl(string $path): string` to `LanguageDriver` (consumers stay
    off Polylang, constitution IX). Fallback → `home_url($path)`; Polylang → the four-shape resolver with
    default-language normalisation for the blog index and degrade-to-plain-URL on any failure.
- [x] T003 **Renderers.** `SiteHeaderRenderer` (logo, CTA, nav items, dropdown children) and
    `SiteFooterRenderer` (logo, Terms, Privacy) call `$driver->localizedUrl(...)`. `isActive()` still uses
    the raw path — active state unaffected.
- [x] T004 **Tests.** +5 `PolylangLanguageDriverTest` (home/anchor, entity, blog index, archive, default
    passthrough) + 1 `FallbackLanguageDriverTest`. Existing header/footer render tests unchanged (fallback
    `localizedUrl` == old `home_url` behaviour). Pest 268/268 (812 assertions).
- [x] T005 **Live verification.** AR nav every target 200 (home `/ar/الرئيسية/`, work `/ar/work/`, journal
    `/ar/المدونة/`, contact `/ar/contact-2/`, service singles, footer terms/privacy); EN unchanged (all
    200, no `/ar/` leakage); no console errors (`output/playwright/019-ar-home-nav.png`).
- [x] T006 **Done.** clean-code-guard + wp-guard pass (hrefs esc_url'd, no plugin hard-dep, no new
    query/AJAX/REST). Durable memory updated; PR opened.
