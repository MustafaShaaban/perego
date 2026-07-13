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

    // theme.json declares Open Sans (Latin) / Cairo (Arabic) as the brand typefaces, but no font file
    // was ever loaded — every route silently fell back to the browser's system-ui font, which reads
    // visibly smaller/lighter than the approved handoff at the same declared font-size. Loads the exact
    // family/weight set the handoff itself uses (an external Google Fonts request, matching the
    // handoff's own choice, not a self-hosting change).
    wp_enqueue_style(
        'perego-theme-fonts',
        'https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&family=Cairo:wght@400;600;700&display=swap',
        [],
        null
    );
});

/**
 * Preconnect to the Google Fonts hosts so the font request in perego-theme-fonts (above) doesn't pay
 * a full DNS+TLS round trip before it can start. Core's own resource-hints API, not a hand-echoed
 * <link rel="preconnect"> tag.
 */
add_filter('wp_resource_hints', static function (array $urls, string $relationType): array {
    if ($relationType === 'preconnect') {
        $urls[] = 'https://fonts.googleapis.com';
        $urls[] = ['href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous'];
    }

    return $urls;
}, 10, 2);

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
