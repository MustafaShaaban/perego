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
    /**
     * @var list<array{label: string, href: string, children?: list<array{label: string, href: string}>}>
     */
    private const NAV_ITEMS = [
        ['label' => 'Home', 'href' => '/'],
        ['label' => 'About Us', 'href' => '/about'],
        [
            'label' => 'Services',
            'href' => '/services',
            'children' => [
                ['label' => 'Video Editing & Post-Production', 'href' => '/services/video-editing'],
                ['label' => '2D Motion Graphics & Animation', 'href' => '/services/motion-graphics'],
                ['label' => 'Graphic Design & Brand Identity', 'href' => '/services/graphic-design'],
                ['label' => 'Website Making', 'href' => '/services/website-making'],
            ],
        ],
        ['label' => 'Work', 'href' => '/work'],
        ['label' => 'Journal', 'href' => '/journal'],
        ['label' => 'Clients', 'href' => '/#clients'],
        ['label' => 'Contact Us', 'href' => '/contact'],
    ];

    public function __construct(private readonly LanguageService $languageService)
    {
    }

    public function render(string $currentPath = '/'): string
    {
        $driver = $this->languageService->driver();

        $context = esc_attr((string) wp_json_encode(['isScrolled' => false, 'isMenuOpen' => false]));

        $html = '<header class="perego-header" data-wp-interactive="perego/site-header" '
            . "data-wp-context='" . $context . "' "
            . 'data-wp-class--is-scrolled="context.isScrolled" '
            . 'data-wp-class--is-menu-open="context.isMenuOpen" '
            . 'data-wp-init="callbacks.init">';

        $html .= '<div class="perego-header__bar">';
        $html .= '<a class="perego-header__logo" href="' . esc_url(home_url('/')) . '">'
            . esc_html__('Perego', 'perego-site') . '</a>';

        $html .= '<div class="perego-header__nav-backdrop"></div>';
        $html .= '<nav id="perego-mobile-nav" class="perego-header__nav" '
            . 'aria-label="' . esc_attr__('Primary', 'perego-site') . '" '
            . 'data-wp-on--keydown="actions.handleMenuKeydown">';
        $html .= '<ul>' . $this->renderNavItems($currentPath) . '</ul>';
        $html .= '</nav>';

        $html .= '<a class="perego-header__cta" href="' . esc_url(home_url('/contact')) . '">'
            . esc_html__('Start a Project', 'perego-site') . '</a>';

        $html .= $this->renderLanguageToggle($driver->currentLocale());

        $html .= '<button type="button" class="perego-header__hamburger" '
            . 'aria-expanded="false" aria-controls="perego-mobile-nav" '
            . 'data-wp-bind--aria-expanded="context.isMenuOpen" '
            . 'data-wp-on--click="actions.toggleMenu">'
            . '<span class="screen-reader-text">' . esc_html__('Menu', 'perego-site') . '</span>'
            . '</button>';

        $html .= '</div>';
        $html .= '</header>';

        return $html;
    }

    private function renderNavItems(string $currentPath): string
    {
        $html = '';

        foreach (self::NAV_ITEMS as $item) {
            $isActive  = $this->isActive($item['href'], $currentPath);
            $classes   = 'perego-header__nav-item' . ($isActive ? ' is-active' : '');
            $ariaCurrent = $isActive ? ' aria-current="page"' : '';
            $hasChildren = ! empty($item['children']);

            $html .= '<li class="' . esc_attr($classes) . '"' . ($hasChildren ? ' data-wp-interactive="perego/site-header"' : '') . '>';
            $html .= '<a href="' . esc_url(home_url($item['href'])) . '"' . $ariaCurrent
                . ($hasChildren ? ' data-wp-on--click="actions.toggleMobileDropdown"' : '') . '>'
                . esc_html($item['label']) . '</a>';

            if ($hasChildren) {
                $html .= '<ul class="perego-header__dropdown">';
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

    private function renderLanguageToggle(string $currentLocale): string
    {
        $html = '<div class="perego-language-toggle" data-wp-interactive="perego/site-header">';

        foreach ($this->languageService->driver()->availableLocales() as $locale) {
            $isCurrent = $locale === $currentLocale;
            $html .= '<button type="button" class="' . ($isCurrent ? 'is-current' : '') . '" '
                . 'data-wp-on--click="actions.switchLanguage" '
                . 'data-locale="' . esc_attr($locale) . '" '
                . 'aria-pressed="' . ($isCurrent ? 'true' : 'false') . '">'
                . esc_html(strtoupper($locale)) . '</button>';
        }

        $html .= '</div>';

        return $html;
    }

    private function isActive(string $href, string $currentPath): bool
    {
        if ($href === '/#clients') {
            return false;
        }

        $normalizedHref = rtrim($href, '/');
        $normalizedPath = rtrim(strtok($currentPath, '?') ?: '/', '/');

        return $normalizedHref === $normalizedPath;
    }
}
