<?php

/**
 * Plugin Name:       Perego Site
 * Description:       Application code for the Perego website (a Corex client site, with the --starter example).
 * Version:           0.1.0
 * Requires at least: 7.0
 * Requires PHP:      8.3
 * Author:            Perego
 * License:           GPL-2.0-or-later
 * Text Domain:       perego-site
 * Domain Path:       /languages
 *
 * @package PeregoSite
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

// PSR-4 autoloader for PeregoSite\ -> src/ (replace with Composer's autoloader when you add one).
spl_autoload_register(static function (string $class): void {
    $prefix = 'PeregoSite\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file     = __DIR__ . '/src/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// Load the UI-string translations (spec 005). Polylang sets the `ar` locale on `/ar/` routes; this
// makes WordPress pick `languages/perego-site-ar.mo` so the `__()`-wrapped header nav, form labels,
// and buttons render in Arabic instead of falling through to their English source. On `init` (not
// `plugins_loaded`) so the locale Polylang resolves per-request is already in effect.
add_action('init', static function (): void {
    load_plugin_textdomain('perego-site', false, dirname(plugin_basename(__FILE__)) . '/languages');
    (new PeregoSite\I18n\FrameworkFormStrings())->register();
});

// Boot the site provider, which wires the --starter example (REST + block + options page)
// and the spec-001 language service. When you remove the example (see REMOVE-EXAMPLE.md),
// keep this boot — it's your site's entry.
add_action('plugins_loaded', static function (): void {
    perego_site(new PeregoSite\PeregoSiteServiceProvider());
    perego_site()->register();
    perego_site()->boot();
});

/**
 * Site-level accessor for block render callbacks (site-header, site-footer, …) to reach the
 * composition root without reaching into the framework's container — this is site-level wiring,
 * not framework wiring. Set once, on `plugins_loaded`; reads before that point return null.
 */
function perego_site(?PeregoSite\PeregoSiteServiceProvider $set = null): ?PeregoSite\PeregoSiteServiceProvider
{
    static $provider = null;

    if ($set !== null) {
        $provider = $set;
    }

    return $provider;
}
