<?php

/**
 * Seed example projects for the portfolio grid (spec 003 / M3). Idempotent. Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/seed-projects.php";' --path=wp
 * (the direct `wp eval-file` path trips a wp-cli quirk with this script's use/WP_CLI ordering; the
 * `require` form works — see DECISIONS.md).
 *
 * These are the handoff's PLACEHOLDER projects (client shown as "Sample Client") — replace with
 * Perego's real work before launch. Do not invent results/metrics. See CONTENT_MODEL.md.
 *
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\PostTypes\ProjectPostType;

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval-file.\n");
    exit(1);
}

$categories = ProjectPostType::CATEGORIES; // slug => label

foreach ($categories as $slug => $label) {
    if (! term_exists($slug, ProjectPostType::TAXONOMY)) {
        wp_insert_term($label, ProjectPostType::TAXONOMY, ['slug' => $slug]);
        WP_CLI::log("Created category: {$slug}");
    }
}

/** @var list<array{title: string, cat: string, year: string}> $projects */
$projects = [
    ['title' => 'Brand Film — Launch Campaign', 'cat' => 'video', 'year' => '2026'],
    ['title' => 'Product Teaser Cut', 'cat' => 'video', 'year' => '2025'],
    ['title' => 'Event Recap Edit', 'cat' => 'video', 'year' => '2026'],
    ['title' => 'Animated Explainer Series', 'cat' => 'motion', 'year' => '2026'],
    ['title' => 'Logo Sting & Lower Thirds', 'cat' => 'motion', 'year' => '2025'],
    ['title' => 'Visual Identity System', 'cat' => 'design', 'year' => '2025'],
    ['title' => 'Campaign Key Visual Suite', 'cat' => 'design', 'year' => '2026'],
    ['title' => 'Multi-page Marketing Site', 'cat' => 'web', 'year' => '2026'],
    ['title' => 'Landing Page & Microsite', 'cat' => 'web', 'year' => '2025'],
];

$created = 0;

foreach ($projects as $project) {
    $slug = sanitize_title($project['title']);

    $existing = get_posts([
        'post_type' => ProjectPostType::POST_TYPE,
        'name' => $slug,
        'post_status' => 'any',
        'numberposts' => 1,
        'fields' => 'ids',
    ]);

    if ($existing !== []) {
        continue; // idempotent
    }

    $postId = wp_insert_post([
        'post_type' => ProjectPostType::POST_TYPE,
        'post_status' => 'publish',
        'post_title' => $project['title'],
        'post_name' => $slug,
        'post_content' => 'Example project — replace with Perego\'s real work. Do not invent results or metrics.',
        'post_excerpt' => 'Client: Sample Client · ' . $project['year'],
    ], true);

    if (is_wp_error($postId)) {
        WP_CLI::warning("Failed: {$project['title']} — " . $postId->get_error_message());
        continue;
    }

    wp_set_object_terms($postId, $project['cat'], ProjectPostType::TAXONOMY);
    update_post_meta($postId, '_perego_client', 'Sample Client');
    update_post_meta($postId, '_perego_year', $project['year']);
    $created++;
}

WP_CLI::success("Seeded {$created} example project(s); " . count($projects) . ' total defined.');
