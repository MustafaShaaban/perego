<?php

/**
 * Marks the home page's featured shortlist (owner, 2026-07-28).
 *
 * The home grid renders `portfolio-grid` with `featuredOnly`, so without this every home visit shows
 * an empty Work section — there is deliberately no "fall back to everything" rule, because a silent
 * fallback would make the flag look broken rather than unset.
 *
 * Spread across all four categories on purpose: the home grid keeps its full filter chip row whatever
 * is featured, so a shortlist drawn from only one service leaves three chips resolving to nothing.
 *
 * Only English posts are touched. An Arabic project holds no meta of its own and reads its English
 * record through {@see ProjectRepository::isFeatured()}, so writing both would create two places to
 * disagree. Idempotent, and additive only: it never clears a flag an editor set.
 *
 * wp eval 'require "sites/perego/perego-site/scripts/seed-featured-projects.php";' --path=wp
 *
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\PostTypes\ProjectPostType;

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

/** Four categories × three = a full first page of the home grid (PortfolioGridRenderer::PER_PAGE is 9). */
const PEREGO_FEATURED_PER_CATEGORY = 3;

$featured = 0;
$skipped = 0;

foreach (array_keys(ProjectPostType::CATEGORIES) as $slug) {
    $posts = get_posts([
        'post_type' => ProjectPostType::POST_TYPE,
        'post_status' => 'publish',
        'numberposts' => PEREGO_FEATURED_PER_CATEGORY,
        // The same ordering the grid itself uses, so the shortlist is the top of what visitors
        // would have seen anyway rather than an unrelated selection.
        'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
        'lang' => 'en',
        'tax_query' => [[
            'taxonomy' => ProjectPostType::TAXONOMY,
            'field' => 'slug',
            'terms' => $slug,
        ]],
    ]);

    if ($posts === []) {
        WP_CLI::warning("No published projects in category '{$slug}' — that filter chip will be empty.");
        continue;
    }

    foreach ($posts as $post) {
        if ((int) get_post_meta($post->ID, ProjectPostType::META_FEATURED, true) > 0) {
            $skipped++;
            continue;
        }

        update_post_meta($post->ID, ProjectPostType::META_FEATURED, 1);
        $featured++;
    }
}

WP_CLI::success("Featured shortlist seeded — {$featured} newly featured, {$skipped} already were (idempotent).");
