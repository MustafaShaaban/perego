<?php

/**
 * Seeds each demo project's gallery with exactly three already-imported, approved handoff stills.
 * The post-meta array is editor-owned after creation: re-running never overwrites a non-empty
 * gallery. Run the featured-media seed first, then:
 * wp eval 'require "sites/perego/perego-site/scripts/seed-project-galleries.php";' --path=wp
 */

declare(strict_types=1);

use PeregoSite\PostTypes\ProjectPostType;

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

$projects = get_posts([
    'post_type' => ProjectPostType::POST_TYPE,
    'post_status' => 'any',
    'numberposts' => 50,
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
