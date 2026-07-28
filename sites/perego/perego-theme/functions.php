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

    // The strings the form toasts render themselves; the message body is whatever the form already
    // produced. Localized here rather than through wp.i18n because this theme ships no JS translation
    // file, so a `wp.i18n.__()` call would stay English on the Arabic pages — the same trap that left
    // the validation messages untranslated.
    //
    // The domain is `perego-site`, which is what style.css declares and what has a .po/.mo. An
    // earlier `perego-theme` here was a domain that exists nowhere, so the string could never
    // translate however complete the catalogue was.
    wp_localize_script('perego-theme-main', 'peregoTheme', [
        'toastDismiss' => __('Dismiss', 'perego-site'),
        'toastSuccessTitle' => __('Message sent', 'perego-site'),
        'toastErrorTitle' => __('Not sent', 'perego-site'),
    ]);

    // main.css declares the exact Open Sans/Cairo family-and-weight set from the locked handoff through
    // local WOFF2 assets. Keeping those faces in the theme avoids a render-blocking third-party request.
});


/**
 * Load the compiled front-end design CSS into the block editor canvas so the live-canvas block
 * previews (spec 021) render with the real Perego design instead of bare editor defaults. WordPress
 * scopes these rules under `.editor-styles-wrapper`; `assets/css/main.css` is the same stylesheet the
 * front end enqueues, kept as the single source of truth for the design (no editor-only fork).
 */
add_action('after_setup_theme', static function (): void {
    add_theme_support('editor-styles');
    add_editor_style('assets/css/main.css');
});


/**
 * Style the block Inspector sidebar (spec 021). The sidebar renders OUTSIDE the editor canvas iframe, so
 * `add_editor_style()` above never reaches it — the shared `.perego-editor-*` control primitives
 * (perego-site) are styled by this separately enqueued sheet. `editor-inspector.css` is compiled from
 * `assets/src/scss/editor-inspector.scss` by `npm run styles`.
 */
add_action('enqueue_block_editor_assets', static function (): void {
    \Corex\Assets\Assets::registerBase(
        'perego-theme',
        get_stylesheet_directory() . '/assets',
        get_stylesheet_directory_uri() . '/assets',
        (string) wp_get_theme()->get('Version'),
    );

    \Corex\Assets\Style::enqueue('perego-theme-editor-inspector', 'css/editor-inspector.css', ['base' => 'perego-theme']);
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
