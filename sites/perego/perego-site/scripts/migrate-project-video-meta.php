<?php

/**
 * One-time retrofit (spec 020 round 6): `_perego_video_url` (the ▶ work-card variant) was added
 * after the first projects were seeded, and `seed-projects.php` never touches an existing post.
 * This backfills the placeholder embed on the already-seeded curated projects whose handoff card
 * position is a video variant — ONLY when the field is absent, so an editor-entered URL is never
 * overwritten. AR translations stay empty on purpose (ProjectRepository::videoUrlFor reads the
 * linked EN post's value). Re-running finds every field set and reports 0 changes. Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/migrate-project-video-meta.php";' --path=wp
 *
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\PostTypes\ProjectPostType;

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

require __DIR__ . '/lib-project-variants.php';

// The curated seeds at video-variant positions (video: 1,2 — position 3 is the gallery card;
// motion: 1,2; design: 1,2 — see seed-projects.php ordering).
$videoVariantSlugs = [
    'brand-film-launch-campaign',
    'product-teaser-cut',
    'animated-explainer-series',
    'logo-sting-lower-thirds',
    'visual-identity-system',
    'campaign-key-visual-suite',
];

$migrated = 0;

foreach ($videoVariantSlugs as $slug) {
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
    if ((string) get_post_meta($postId, ProjectPostType::META_VIDEO_URL, true) !== '') {
        continue;
    }

    update_post_meta($postId, ProjectPostType::META_VIDEO_URL, peregoDemoVideoUrl());
    $migrated++;
}

WP_CLI::success("Project video-meta retrofit — {$migrated} post(s) backfilled (idempotent).");
