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
