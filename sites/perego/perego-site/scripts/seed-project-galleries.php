<?php

/**
 * Seeds each demo project's gallery with exactly three already-imported, approved handoff stills.
 * The post-meta array is editor-owned after creation: re-running never overwrites a non-empty
 * gallery. Run the featured-media seed first, then:
 * wp eval 'require "sites/perego/perego-site/scripts/seed-project-galleries.php";' --path=wp
 *
 * Demo-fill projects whose handoff card variant is "single image" (lib-project-variants) are
 * skipped on purpose: with the video > gallery > image card rule, a >1-image gallery would turn
 * their designed `data-image` card into a gallery card. Trade-off: those demo singles show no
 * gallery grid — acceptable for placeholder data, and an editor adding a real gallery re-types
 * the card automatically.
 */

declare(strict_types=1);

use PeregoSite\PostTypes\ProjectPostType;

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

require __DIR__ . '/lib-project-variants.php';

$projects = get_posts([
    'post_type' => ProjectPostType::POST_TYPE,
    'post_status' => 'any',
    'numberposts' => 200,
]);
$featuredIds = array_values(array_filter(array_map(
    static fn ($project): int => (int) get_post_thumbnail_id($project->ID),
    $projects,
)));
$assigned = 0;

foreach ($projects as $project) {
    $existing = get_post_meta($project->ID, '_perego_gallery_attachment_ids', true);
    if (is_array($existing) && $existing !== []) {
        continue;
    }

    // Resolve the EN slug (AR translations share the EN project's variant) and skip the designed
    // single-image demo cards.
    $enSlug = $project->post_name;
    if (function_exists('pll_get_post') && function_exists('pll_get_post_language')) {
        $lang = pll_get_post_language($project->ID);
        if ($lang && $lang !== 'en') {
            $enId = pll_get_post($project->ID, 'en');
            if ($enId) {
                $enSlug = get_post_field('post_name', $enId);
            }
        }
    }
    if (preg_match('/-example-(\d+)$/', $enSlug, $m) && peregoProjectVariant((int) $m[1]) === 'image') {
        continue;
    }

    $current = (int) get_post_thumbnail_id($project->ID);
    if ($current === 0 || count($featuredIds) < 3) {
        continue;
    }

    $gallery = [$current];
    foreach ($featuredIds as $candidate) {
        if ($candidate !== $current) {
            $gallery[] = $candidate;
        }
        if (count($gallery) === 3) {
            break;
        }
    }
    update_post_meta($project->ID, '_perego_gallery_attachment_ids', $gallery);
    $assigned++;
}

WP_CLI::success("Project galleries seeded: {$assigned} record(s) updated (idempotent).");
