<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Perego theme front-end assets. Registers this theme's CoreX asset base, then enqueues the
 * COMPILED CSS/JS through the CoreX asset helpers (Corex\Assets\*) — never hardcoded paths and
 * never a version string by hand. SCSS is source only (assets/src/scss/); `npm run styles`
 * compiles it to assets/css/. Build everything with `npm run build`.
 */
add_action('wp_enqueue_scripts', static function (): void {
    \Corex\Assets\Assets::registerBase(
        'perego-theme',
        get_stylesheet_directory() . '/assets',
        get_stylesheet_directory_uri() . '/assets',
        (string) wp_get_theme()->get('Version'),
    );

    \Corex\Assets\Style::enqueue('perego-theme-main', 'css/main.css', ['base' => 'perego-theme']);
    \Corex\Assets\Script::enqueue('perego-theme-main', 'js/main.js', [
        'base'      => 'perego-theme',
        'in_footer' => true,
        'defer'     => true,
    ]);
});

/**
 * SEO: keep search-results and 404 pages out of the index (`noindex, follow`) — thin/duplicate pages
 * per the handoff SEO_HANDOFF. Uses core's `wp_robots` filter so it composes with WordPress's own
 * robots meta and Polylang's hreflang. All other routes stay indexable.
 */
add_filter('wp_robots', static function (array $robots): array {
    if (is_search() || is_404()) {
        $robots['noindex'] = true;
        $robots['follow'] = true;
    }

    return $robots;
});
