<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use Corex\Assets\Image;
use PeregoSite\Services\LanguageService;
use PeregoSite\PostTypes\ServicePostType;
use PeregoSite\Theme\SiteRoutes;

/**
 * Server-renders the perego/site-header block: logo, primary nav (with the Services dropdown),
 * the "Start a Project" CTA, the language toggle, and the mobile hamburger. All interactive
 * behaviour (sticky scroll, mobile slide-in panel, dropdown, language switch) is wired by the
 * block's own view.js via WordPress Interactivity API directives emitted here
 * (`data-wp-interactive`, `data-wp-on--*`, `data-wp-class--*`, …) — this class only emits markup.
 *
 * spec 020 round 4 — the nav links, logo, and sticky-vs-static behavior are real block attributes,
 * editable in the block editor's Inspector sidebar (a link repeater + `MediaUpload` + a toggle —
 * previously all three were hardcoded PHP with zero admin UI). This block lives in the shared
 * `header.html` FSE template part, so — matching `footer-careers`'/`clients-carousel`'s pattern for
 * the same problem — nav items are stored as an En/Ar JSON-string attribute pair (`navItemsEn`/
 * `navItemsAr`); logo and the sticky toggle apply to both languages equally. An empty/invalid nav
 * attribute falls back to the original hardcoded seed, so existing pages render unchanged.
 */
final class SiteHeaderRenderer
{
    private ?LinkTarget $linkTarget = null;

    public function __construct(private readonly LanguageService $languageService)
    {
    }

    /**
     * The seed nav items when no block attribute has been set. Labels are `__()`-wrapped with literal
     * strings (not stored in a const) so they are both extractable by `wp i18n make-pot` and
     * translated at render time — the AR `.mo` (spec 005) then localizes the header nav that otherwise
     * rendered in English on `/ar/`.
     *
     * @return list<array{label: string, href: string, children?: list<array{label: string, href: string}>}>
     */
    private function seedNavItems(): array
    {
        return [
            ['label' => __('Home', 'perego-site'), 'href' => '/'],
            ['label' => __('About Us', 'perego-site'), 'href' => '/#about'],
            [
                'label' => __('Services', 'perego-site'),
                'href' => '/#services',
                'children' => [
                    ['label' => __('Video Editing', 'perego-site'), 'href' => '/services/video-editing'],
                    ['label' => __('2D Motion Graphics', 'perego-site'), 'href' => '/services/motion-graphics'],
                    ['label' => __('Graphic Design', 'perego-site'), 'href' => '/services/graphic-design'],
                    ['label' => __('Website Making', 'perego-site'), 'href' => '/services/website-making'],
                ],
            ],
            // Client request 2026-07-26: Work is a home-page section under Clients, not its own page.
            // The archive route still exists and still works — nothing links to it any more.
            ['label' => __('Work', 'perego-site'), 'href' => '/#work'],
            ['label' => __('Journal', 'perego-site'), 'href' => '/journal'],
            ['label' => __('Clients', 'perego-site'), 'href' => '/#clients'],
            // The handoff header's Contact Us is a same-page anchor to the footer (every page's
            // footer carries id="contact"), not a link to the contact page — that page is reached
            // via the "Start a Project" CTAs.
            ['label' => __('Contact Us', 'perego-site'), 'href' => '#contact'],
        ];
    }

    /**
     * The block-attribute nav items for the current locale if a valid, non-empty JSON array was set,
     * else the hardcoded seed.
     *
     * @param array<string,string> $attributes
     * @return list<array{label: string, href: string, children?: list<array{label: string, href: string}>}>
     */
    private function navItems(array $attributes, string $locale): array
    {
        $suffix = $locale === 'ar' ? 'Ar' : 'En';
        $raw = (string) ($attributes['navItems' . $suffix] ?? '');
        $decoded = $raw !== '' ? json_decode($raw, true) : null;
        $items = is_array($decoded) && $decoded !== [] ? $decoded : $this->seedNavItems();

        return $this->applyServicesMenu($items, $attributes, $locale);
    }

    /**
     * Replaces only the Services dropdown when the editor opts into a Service-post-backed menu.
     * Manual mode with no selected records deliberately retains the proven legacy dropdown.
     *
     * @param list<array{label:string,href:string,children?:list<array{label:string,href:string}>}> $items
     * @param array<string,mixed> $attributes
     * @return list<array{label:string,href:string,children?:list<array{label:string,href:string}>}>
     */
    private function applyServicesMenu(array $items, array $attributes, string $locale): array
    {
        $mode = (string) ($attributes['servicesMenuMode'] ?? 'manual');
        if (! in_array($mode, ['automatic', 'manual'], true)) {
            return $items;
        }

        $configuredIds = $this->positiveIds($attributes['servicesMenuOrder'] ?? []);
        $excludedIds = $this->positiveIds($attributes['servicesMenuExcludeIds'] ?? []);
        if ($mode === 'manual' && $configuredIds === []) {
            return $items;
        }

        $services = $mode === 'automatic'
            ? $this->publishedServices($locale)
            : $this->selectedServices($configuredIds, $locale);
        $services = array_values(array_filter(
            $services,
            static fn (array $service): bool => array_intersect($service['ids'], $excludedIds) === []
        ));
        if ($services === []) {
            return $items;
        }

        foreach ($items as $index => $item) {
            if (($item['href'] ?? '') !== '/#services') {
                continue;
            }
            $items[$index]['children'] = array_map(
                static fn (array $service): array => ['label' => $service['label'], 'href' => $service['href'], 'absoluteUrl' => $service['absoluteUrl']],
                $services
            );
            break;
        }

        return $items;
    }

    /** @param mixed $ids @return list<int> */
    private function positiveIds($ids): array
    {
        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(static fn ($id): int => abs((int) $id), $ids))));
    }

    /** @return list<array{sourceId:int,ids:list<int>,label:string,href:string,absoluteUrl:string}> */
    private function publishedServices(string $locale): array
    {
        if (! function_exists('get_posts')) {
            return [];
        }

        $posts = get_posts([
            'post_type' => ServicePostType::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'],
            'suppress_filters' => false,
            'lang' => $locale,
        ]);

        return $this->servicesFromPosts(is_array($posts) ? $posts : []);
    }

    /** @param list<int> $ids @return list<array{sourceId:int,ids:list<int>,label:string,href:string,absoluteUrl:string}> */
    private function selectedServices(array $ids, string $locale): array
    {
        if (! function_exists('get_post')) {
            return [];
        }

        $posts = [];
        $sourceIdsByPostId = [];
        foreach ($ids as $sourceId) {
            $localizedId = function_exists('pll_get_post') ? (int) pll_get_post($sourceId, $locale) : $sourceId;
            $post = get_post($localizedId ?: $sourceId);
            if ($post instanceof \WP_Post && $post->post_type === ServicePostType::POST_TYPE && $post->post_status === 'publish') {
                $posts[] = $post;
                $sourceIdsByPostId[$post->ID] = $sourceId;
            }
        }

        return $this->servicesFromPosts($posts, $sourceIdsByPostId);
    }

    /**
     * @param list<\WP_Post> $posts
     * @param array<int,int> $sourceIdsByPostId
     * @return list<array{sourceId:int,ids:list<int>,label:string,href:string,absoluteUrl:string}>
     */
    private function servicesFromPosts(array $posts, array $sourceIdsByPostId = []): array
    {
        $services = [];
        foreach ($posts as $post) {
            if (! $post instanceof \WP_Post) {
                continue;
            }
            $absoluteUrl = function_exists('get_permalink') ? (string) get_permalink($post) : '';
            if ($absoluteUrl === '') {
                continue;
            }
            $path = (string) parse_url($absoluteUrl, PHP_URL_PATH);
            $englishId = function_exists('pll_get_post') ? (int) pll_get_post($post->ID, 'en') : 0;
            $sourceId = $sourceIdsByPostId[$post->ID] ?? $post->ID;
            $services[] = [
                'sourceId' => $sourceId,
                'ids' => array_values(array_unique(array_filter([$post->ID, $englishId, $sourceId]))),
                'label' => (string) get_the_title($post),
                'href' => $path !== '' ? $path : '/',
                'absoluteUrl' => $absoluteUrl,
            ];
        }

        return $services;
    }

    /** @param array<string,string> $attributes */
    public function render(string $currentPath = '/', array $attributes = []): string
    {
        $driver = $this->languageService->driver();
        $locale = $driver->currentLocale();
        $isSticky = ! array_key_exists('isSticky', $attributes) || (bool) $attributes['isSticky'];

        $context = esc_attr((string) wp_json_encode(['isScrolled' => false, 'isMenuOpen' => false]));

        $html = '<a class="skip-link" href="#main">' . esc_html__('Skip to content', 'perego-site') . '</a>';
        $html .= '<header id="siteHeader" class="site-header" data-wp-interactive="perego/site-header" '
            . ($isSticky ? '' : 'style="position:static" ')
            . "data-wp-context='" . $context . "' "
            . 'data-wp-class--is-scrolled="context.isScrolled" '
            . 'data-wp-class--is-menu-open="context.isMenuOpen" '
            . 'data-wp-init="callbacks.init">';

        $html .= '<div class="container site-header__inner">';
        $logo = $this->logoMarkup($attributes);
        $html .= '<a class="logo" href="' . esc_url($driver->localizedUrl('/')) . '" '
            . 'aria-label="' . esc_attr__('Perego — home', 'perego-site') . '">'
            . $logo
            . '</a>';

        $html .= '<nav id="mainNav" class="main-nav" '
            . 'aria-label="' . esc_attr__('Primary', 'perego-site') . '" '
            . 'data-wp-on--keydown="actions.handleMenuKeydown">';
        // Panel-only logo + CTA (client request 2026-07-26: the mobile menu needs both). Both are
        // display:none above the nav breakpoint, and the header-bar CTA is hidden below it, so exactly
        // one of each is ever rendered to a user or to assistive tech. The logo here is decorative —
        // the panel already lists Home, so a second "Perego — home" link would be redundant noise.
        $html .= '<div class="main-nav__mobile-head" aria-hidden="true">'
            . $logo
            . '</div>';
        $html .= '<ul class="main-nav__list">' . $this->renderNavItems($currentPath, $this->navItems($attributes, $locale)) . '</ul>';
        $html .= '<div class="main-nav__mobile-cta">' . $this->renderCta($attributes, $locale) . '</div>';
        $html .= '</nav>';

        $html .= $this->renderCta($attributes, $locale);

        $html .= $this->renderLanguageToggle($driver->currentLocale());

        $html .= '<button id="navToggle" type="button" class="nav-toggle" '
            . 'aria-label="' . esc_attr__('Menu', 'perego-site') . '" aria-expanded="false" aria-controls="mainNav" '
            . 'data-wp-bind--aria-expanded="context.isMenuOpen" '
            . 'data-wp-on--click="actions.toggleMenu">'
            . '<span><span class="screen-reader-text">' . esc_html__('Menu', 'perego-site') . '</span></span>'
            . '</button>';

        $html .= '</div>';
        $html .= '</header><div id="navBackdrop" class="nav-backdrop" data-wp-on--click="actions.closeMenu"></div>';

        return $html;
    }

    /**
     * The "Start a Project" CTA. Label is an editor-set En/Ar pair (bilingual text), defaulting to the
     * handoff wording; the target is a single editor-set path shared by both locales, defaulting to the
     * contact route. An empty label/URL keeps the exact prior output, so unedited headers are unchanged.
     *
     * @param array<string,mixed> $attributes
     */
    private function renderCta(array $attributes, string $locale): string
    {
        $suffix = $locale === 'ar' ? 'Ar' : 'En';
        $label = trim((string) ($attributes['ctaLabel' . $suffix] ?? ''));
        if ($label === '') {
            $label = __('Start a Project', 'perego-site');
        }

        // spec 021 T036: the CTA may now name a page instead of a URL. `ctaUrl` stays the custom-URL
        // value, so a header saved before the picker existed resolves through exactly the old path.
        $cta = LinkTarget::fromAttributes($attributes);

        return '<a class="btn btn--accent header-cta" href="' . esc_url($this->linkTarget()->href($cta, SiteRoutes::START_PROJECT)) . '"'
            . $this->linkTarget()->targetAttributes($cta) . '>'
            . esc_html($label) . '</a>';
    }

    /**
     * The shared link resolver, built from this renderer's language driver. Held lazily because the
     * driver is resolved per request and a renderer may render several links.
     */
    private function linkTarget(): LinkTarget
    {
        return $this->linkTarget ??= new LinkTarget($this->languageService->driver());
    }

    /** @param list<array{label: string, href: string, children?: list<array{label: string, href: string}>}> $items */
    private function renderNavItems(string $currentPath, array $items): string
    {
        $driver = $this->languageService->driver();
        $html = '';

        foreach ($items as $item) {
            $isActive  = $this->isActive($item['href'], $currentPath);
            $hasChildren = ! empty($item['children']);
            $classes   = ($hasChildren ? 'has-dropdown' : '') . ($isActive ? ' is-active' : '');
            $ariaCurrent = $isActive ? ' aria-current="page"' : '';
            // A pure fragment ("#contact") is a same-page anchor and language-neutral — localizing
            // it would turn it into an absolute homepage URL and break the anchor on every subpage.
            // Anything else goes through LinkTarget, which also resolves a picked page (spec 021 T036).
            $url = $this->linkTarget()->href($item, $item['href'] ?? '/');
            $newTab = $this->linkTarget()->targetAttributes($item);

            $html .= '<li' . ($classes !== '' ? ' class="' . esc_attr($classes) . '"' : '') . ($hasChildren ? ' data-wp-interactive="perego/site-header"' : '') . '>';
            $html .= '<a class="main-nav__link' . ($isActive ? ' is-active' : '') . '" href="' . esc_url($url) . '"' . $ariaCurrent . $newTab
                . ($hasChildren ? ' aria-haspopup="true" aria-expanded="false" data-wp-on--click="actions.toggleMobileDropdown"' : '') . '>'
                . esc_html($item['label'])
                . ($hasChildren ? ' <svg class="nav-caret" width="12" height="8" viewBox="0 0 12 8" aria-hidden="true"><path d="M1 1l5 5 5-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>' : '')
                . '</a>';

            if ($hasChildren) {
                $html .= '<ul class="dropdown">';
                foreach ($item['children'] as $child) {
                    // A Services-menu child resolved from a real Service post already carries its
                    // absolute URL; anything else is an editor-configured link.
                    $childUrl = (string) ($child['absoluteUrl'] ?? '');
                    $childUrl = $childUrl !== '' ? $childUrl : $this->linkTarget()->href($child, $child['href'] ?? '/');
                    $html .= '<li><a href="' . esc_url($childUrl) . '"' . $this->linkTarget()->targetAttributes($child) . '>'
                        . esc_html($child['label']) . '</a></li>';
                }
                $html .= '</ul>';
            }

            $html .= '</li>';
        }

        return $html;
    }

    /**
     * The language switcher as real navigation (spec Phase 5 — never the prototype's JS-only toggle):
     * the current language is a non-link aria-current marker; each other language is an anchor to that
     * locale's actual URL (Polylang's real translated URL, or the fallback's `?lang=` switch). The
     * `data-lang-url-managed` flag tells the view-script whether the driver's URLs already carry the
     * language (Polylang → the client stays out of it) or the choice needs client-side persistence.
     */
    private function renderLanguageToggle(string $currentLocale): string
    {
        $driver = $this->languageService->driver();
        $urlManaged = $driver->managesLanguageViaUrl() ? '1' : '0';

        $html = '<div class="lang-toggle" role="group" aria-label="' . esc_attr__('Language', 'perego-site') . '" data-wp-interactive="perego/site-header" '
            . 'data-lang-url-managed="' . $urlManaged . '">';

        $availableLocales = $driver->availableLocales();
        foreach (array_values(array_intersect(['ar', 'en'], $availableLocales)) as $locale) {
            $label = esc_html(strtoupper($locale));

            if ($locale === $currentLocale) {
                $html .= '<span class="lang-toggle__btn is-active" '
                    . 'aria-current="true" lang="' . esc_attr($locale) . '">' . $label . '</span>';
                continue;
            }

            $html .= '<a class="lang-toggle__btn" '
                . 'href="' . esc_url($this->switchUrl($locale)) . '" '
                . 'data-locale="' . esc_attr($locale) . '" '
                . 'hreflang="' . esc_attr($locale) . '" lang="' . esc_attr($locale) . '">'
                . $label . '</a>';
        }

        $html .= '</div>';

        return $html;
    }

    /** The `logoId` attachment markup when set, else the CoreX-managed handoff logo. */
    private function logoMarkup(array $attributes): string
    {
        $attachment = $this->attachmentLogo((int) ($attributes['logoId'] ?? 0));
        if ($attachment !== '') {
            return $attachment;
        }

        return Image::picture('images/logo-full.png', [
            'base' => 'perego-theme',
            'alt' => '',
            'class' => 'logo__img',
            'width' => 552,
            'height' => 170,
            'loading' => 'eager',
        ]);
    }

    private function attachmentLogo(int $logoId): string
    {
        if ($logoId <= 0) {
            return '';
        }

        return (string) wp_get_attachment_image($logoId, 'full', false, [
            'class' => 'logo__img',
            'alt' => '',
            'loading' => 'eager',
            'decoding' => 'async',
        ]);
    }

    /**
     * The switch URL for a locale, degrading to the home page if the driver cannot resolve one
     * (e.g. Polylang has no translation for the current entity) — never a broken or mixed link.
     */
    private function switchUrl(string $locale): string
    {
        try {
            return $this->languageService->driver()->urlFor($locale);
        } catch (\Throwable) {
            return (string) home_url('/');
        }
    }

    private function isActive(string $href, string $currentPath): bool
    {
        // Homepage-anchor items (About, Services, Clients: `/#…`) are in-page jumps, never a
        // route match — they must not paint an active/aria-current state on any page.
        if (str_contains($href, '#')) {
            return false;
        }

        $normalizedHref = rtrim($href, '/');
        $normalizedPath = rtrim(strtok($currentPath, '?') ?: '/', '/');

        if ($normalizedHref === $normalizedPath) {
            return true;
        }

        // Ancestor match: light the parent nav item on descendant routes (e.g. a single project at
        // `/work/<slug>` keeps `/work` active). Guard against the home item (`''` after trim) so it
        // does not match every path.
        return $normalizedHref !== '' && str_starts_with($normalizedPath, $normalizedHref . '/');
    }
}
