<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Services\LanguageService;

/**
 * Server-renders the perego/site-header block: logo, primary nav (with the Services dropdown),
 * the "Start a Project" CTA, the language toggle, and the mobile hamburger. All interactive
 * behaviour (sticky scroll, mobile slide-in panel, dropdown, language switch) is wired by the
 * block's own view.js via WordPress Interactivity API directives emitted here
 * (`data-wp-interactive`, `data-wp-on--*`, `data-wp-class--*`, …) — this class only emits markup.
 */
final class SiteHeaderRenderer
{
    public function __construct(private readonly LanguageService $languageService)
    {
    }

    /**
     * The primary nav items. Labels are `__()`-wrapped with literal strings (not stored in a const) so
     * they are both extractable by `wp i18n make-pot` and translated at render time — the AR `.mo`
     * (spec 005) then localizes the header nav that otherwise rendered in English on `/ar/`.
     *
     * @return list<array{label: string, href: string, children?: list<array{label: string, href: string}>}>
     */
    private function navItems(): array
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
            ['label' => __('Work', 'perego-site'), 'href' => '/work'],
            ['label' => __('Journal', 'perego-site'), 'href' => '/journal'],
            ['label' => __('Clients', 'perego-site'), 'href' => '/#clients'],
            ['label' => __('Contact Us', 'perego-site'), 'href' => '/contact'],
        ];
    }

    public function render(string $currentPath = '/'): string
    {
        $driver = $this->languageService->driver();

        $context = esc_attr((string) wp_json_encode(['isScrolled' => false, 'isMenuOpen' => false]));

        $html = '<a class="skip-link" href="#main">' . esc_html__('Skip to content', 'perego-site') . '</a>';
        $html .= '<header id="siteHeader" class="site-header" data-wp-interactive="perego/site-header" '
            . "data-wp-context='" . $context . "' "
            . 'data-wp-class--is-scrolled="context.isScrolled" '
            . 'data-wp-class--is-menu-open="context.isMenuOpen" '
            . 'data-wp-init="callbacks.init">';

        $html .= '<div class="container site-header__inner">';
        $html .= '<a class="logo" href="' . esc_url(home_url('/')) . '" '
            . 'aria-label="' . esc_attr__('Perego — home', 'perego-site') . '">'
            . '<img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/logo-full.png') . '" '
            . 'alt="" class="logo__img" />'
            . '</a>';

        $html .= '<nav id="mainNav" class="main-nav" '
            . 'aria-label="' . esc_attr__('Primary', 'perego-site') . '" '
            . 'data-wp-on--keydown="actions.handleMenuKeydown">';
        $html .= '<ul class="main-nav__list">' . $this->renderNavItems($currentPath) . '</ul>';
        $html .= '</nav>';

        $html .= '<a class="btn btn--accent header-cta" href="' . esc_url(home_url('/contact')) . '">'
            . esc_html__('Start a Project', 'perego-site') . '</a>';

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

    private function renderNavItems(string $currentPath): string
    {
        $html = '';

        foreach ($this->navItems() as $item) {
            $isActive  = $this->isActive($item['href'], $currentPath);
            $hasChildren = ! empty($item['children']);
            $classes   = ($hasChildren ? 'has-dropdown' : '') . ($isActive ? ' is-active' : '');
            $ariaCurrent = $isActive ? ' aria-current="page"' : '';

            $html .= '<li' . ($classes !== '' ? ' class="' . esc_attr($classes) . '"' : '') . ($hasChildren ? ' data-wp-interactive="perego/site-header"' : '') . '>';
            $html .= '<a class="main-nav__link' . ($isActive ? ' is-active' : '') . '" href="' . esc_url(home_url($item['href'])) . '"' . $ariaCurrent
                . ($hasChildren ? ' aria-haspopup="true" aria-expanded="false" data-wp-on--click="actions.toggleMobileDropdown"' : '') . '>'
                . esc_html($item['label'])
                . ($hasChildren ? ' <svg class="nav-caret" width="12" height="8" viewBox="0 0 12 8" aria-hidden="true"><path d="M1 1l5 5 5-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>' : '')
                . '</a>';

            if ($hasChildren) {
                $html .= '<ul class="dropdown">';
                foreach ($item['children'] as $child) {
                    $html .= '<li><a href="' . esc_url(home_url($child['href'])) . '">'
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

        return $normalizedHref === $normalizedPath;
    }
}
