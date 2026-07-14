<?php

/**
 * One-time retrofit (spec 008 T006): `seed-services.php` originally built "Our Process" as a plain
 * `wp:list` ("Label — desc" per item) with zero active CSS backing it — the same "plain text list,
 * not designed cards/icons/arrows" defect the completion contract flagged on the Services archive.
 * The archive's own render was fixed directly (ServicesOverviewRenderer::renderProcess()) and the
 * seed script now writes the same icon/card/arrow contract for future seeds, but the four EN + four
 * AR service posts already seeded from the *old* script keep their original content (the seed script
 * is deliberately idempotent and never touches an existing post). This migrates only those
 * pre-existing, still-seed-authored posts — detected by the absence of the `process-list` class in
 * their content, so it is a no-op once every post has been migrated (or hand-edited by an editor,
 * which also removes the old signature and makes this script leave it alone). Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/migrate-service-process.php";' --path=wp
 *
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\Content\ServiceContent;
use PeregoSite\PostTypes\ServicePostType;

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

require __DIR__ . '/lib-service-process-blocks.php';

$migrated = 0;

foreach (get_posts(['post_type' => ServicePostType::POST_TYPE, 'numberposts' => 50]) as $post) {
    $body = (string) $post->post_content;
    if (str_contains($body, 'process-list')) {
        continue; // Already migrated (or hand-edited into a different shape) — leave it alone.
    }

    if (! preg_match('/<!-- wp:group \{"align":"full","className":"svc-process".*?<!-- \/wp:group -->/s', $body, $m)) {
        continue; // Not the expected old seed shape — don't touch it.
    }

    // Resolve this post's own slug from its Polylang-independent service key: the seeded post_name
    // differs by language (e.g. video-editing / video-editing-2), but both map to the same key.
    $baseSlug = null;
    foreach (array_keys(ServicePostType::SERVICES) as $slug) {
        if ($post->post_name === $slug || $post->post_name === $slug . '-2') {
            $baseSlug = $slug;
            break;
        }
    }
    if ($baseSlug === null) {
        continue;
    }

    $locale = function_exists('pll_get_post_language') ? (pll_get_post_language($post->ID) ?: 'en') : 'en';
    $content = new ServiceContent($locale);

    $newProcess = buildProcessBlocks($content, $baseSlug);
    $newBody = str_replace($m[0], rtrim($newProcess), $body);

    wp_update_post(['ID' => $post->ID, 'post_content' => $newBody]);
    $migrated++;
}

WP_CLI::success("Service process retrofit — {$migrated} post(s) migrated (idempotent).");
