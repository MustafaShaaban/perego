<?php

/**
 * Headless test bootstrap for perego-site — no WordPress ABSPATH here (contrast
 * `perego-site.php`, which requires it). Loads the root Composer autoloader (Pest itself, its
 * dependencies) and registers the same PeregoSite\ -> src/ autoloading perego-site.php registers
 * at runtime, so pure unit tests never need WordPress booted.
 *
 * @package PeregoSite
 */

declare(strict_types=1);

require dirname(__DIR__, 4) . '/vendor/autoload.php';

// PeregoSite src class files carry a `defined('ABSPATH') || exit;` direct-access guard, same
// convention as Corex's own (DECISIONS #20 at the repo root): without this, requiring any class
// file here silently `exit`s the whole PHP process (exit code 0, zero output — costly to trace
// the first time). Define it before any PeregoSite\ class loads.
if (! defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'PeregoSite\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file     = dirname(__DIR__) . '/src/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// Shared WP_Post test double. Mirrors WordPress core's `#[AllowDynamicProperties] class WP_Post`
// (declared `post_content` avoids the PHP 8.2 dynamic-property deprecation when block-renderer tests
// set a body). Defined here once so every test uses the same shape regardless of file load order.
if (! class_exists('WP_Post')) {
    #[AllowDynamicProperties]
    class WP_Post
    {
        public int $ID = 0;

        public string $post_content = '';
    }
}
