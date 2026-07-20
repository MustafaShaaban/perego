<?php

/**
 * One-time retrofit (spec 020 service-page parity): `lib-service-process-blocks.php` originally
 * seeded the SAME icon set (clapper/film-l/star/film-h) on all four service pages, but the handoff's
 * design matrix (docs/sections/service-pages.md lines 21–24) gives each service its own step
 * iconography — only video-editing actually uses the clapper/film set. The seed lib now carries the
 * per-service map for future seeds; this migrates the already-seeded EN + AR posts.
 *
 * Safe by construction: it only rewrites an <img> src that (a) sits inside a `process-step__icon`
 * figure and (b) still points at a theme `assets/images/icon-*.png` file — an icon an editor swapped
 * for a media-library image has a different URL shape and is left alone. Re-running finds every src
 * already equal to its target and reports 0 changes (idempotent). Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/migrate-service-process-icons.php";' --path=wp
 *
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\PostTypes\ServicePostType;

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

require __DIR__ . '/lib-service-process-blocks.php';

$migrated = 0;

$posts = get_posts([
    'post_type' => ServicePostType::POST_TYPE,
    'numberposts' => 50,
    'post_status' => 'any',
    'lang' => '', // All Polylang languages — EN and AR posts both carry the seeded icons.
]);

foreach ($posts as $post) {
    $slug = (string) get_post_meta($post->ID, ServicePostType::META_SERVICE_SLUG, true);
    if ($slug === '' || ! array_key_exists($slug, ServicePostType::SERVICES)) {
        continue; // Not a canonical seeded service — don't touch it.
    }

    $icons = serviceProcessIcons($slug);
    $step = 0;
    $changed = false;

    // Visit each process-step icon figure in step order and point its still-theme-owned src at the
    // per-service icon for that step. Editor-chosen media (different URL shape) never matches.
    $newBody = preg_replace_callback(
        '/(<figure class="[^"]*process-step__icon[^"]*"><img src=")([^"]*\/assets\/images\/icon-[a-z-]+\.png)(")/',
        function (array $m) use ($icons, &$step, &$changed): string {
            $target = $icons[$step] ?? null;
            $step++;
            if ($target === null) {
                return $m[0]; // More figures than designed steps — leave the extras alone.
            }

            $newSrc = preg_replace('/icon-[a-z-]+\.png$/', $target, $m[2]);
            if ($newSrc === $m[2]) {
                return $m[0]; // Already the designed icon.
            }

            $changed = true;

            return $m[1] . $newSrc . $m[3];
        },
        (string) $post->post_content,
    );

    if (! $changed || ! is_string($newBody)) {
        continue;
    }

    wp_update_post(['ID' => $post->ID, 'post_content' => $newBody]);
    $migrated++;
}

WP_CLI::success("Service process icon retrofit — {$migrated} post(s) migrated (idempotent).");
