<?php

/**
 * One-time retrofit (spec 020 service-page parity): the website-showcase card fields
 * (`_perego_site_type`, `_perego_site_url`) were added after the first projects were seeded, and
 * `seed-projects.php` is deliberately idempotent (never touches an existing post). This backfills
 * the two fields on already-seeded web-category projects — ONLY when the field is absent, so an
 * editor-entered value is never overwritten. AR translations are left empty on purpose:
 * ProjectRepository::toWebCard() reads the linked EN post's values as a fallback (the live site is
 * language-neutral data). Re-running finds every field already set and reports 0 changes. Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/migrate-project-site-meta.php";' --path=wp
 *
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\PostTypes\ProjectPostType;

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

// EN project slug => showcase card fields, mirroring the seed script's own entries.
$siteMetaBySlug = [
    'multi-page-marketing-site' => ['siteType' => 'corporate', 'siteUrl' => 'https://example.com'],
    'landing-page-microsite' => ['siteType' => 'landing', 'siteUrl' => 'https://example.com'],
];

$migrated = 0;

foreach ($siteMetaBySlug as $slug => $meta) {
    $ids = get_posts([
        'post_type' => ProjectPostType::POST_TYPE,
        'name' => $slug,
        'post_status' => 'any',
        'numberposts' => 1,
        'fields' => 'ids',
        'lang' => '',
    ]);
    if ($ids === []) {
        continue;
    }

    $postId = (int) $ids[0];
    $changed = false;

    if ((string) get_post_meta($postId, ProjectPostType::META_SITE_TYPE, true) === '') {
        update_post_meta($postId, ProjectPostType::META_SITE_TYPE, $meta['siteType']);
        $changed = true;
    }
    if ((string) get_post_meta($postId, ProjectPostType::META_SITE_URL, true) === '') {
        update_post_meta($postId, ProjectPostType::META_SITE_URL, $meta['siteUrl']);
        $changed = true;
    }

    if ($changed) {
        $migrated++;
    }
}

WP_CLI::success("Project site-meta retrofit — {$migrated} post(s) backfilled (idempotent).");
