<?php

/**
 * Seed project featured images (spec 004 T015) from the approved handoff's own generic portfolio
 * stills (`assets/images/portfolio-1.png` … `portfolio-9.png`) — the same images the handoff itself
 * reuses across multiple projects' cards and galleries, not unique per-project photography (no such
 * assets exist). Idempotent: only imports for a post that has no featured image yet, via WP-CLI's own
 * `media import` (handles the copy, mime type, and metadata generation). Each EN project and its AR
 * translation get the SAME image, cycling through the nine stills by the project's fixed seed order.
 * Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/seed-project-media.php";' --path=wp
 *
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\PostTypes\ProjectPostType;

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

// EN project slug => portfolio still (fixed pairing, stable across re-runs).
const PROJECT_IMAGE = [
    'brand-film-launch-campaign' => 'portfolio-1',
    'product-teaser-cut' => 'portfolio-2',
    'event-recap-edit' => 'portfolio-3',
    'animated-explainer-series' => 'portfolio-4',
    'logo-sting-lower-thirds' => 'portfolio-5',
    'visual-identity-system' => 'portfolio-6',
    'campaign-key-visual-suite' => 'portfolio-7',
    'multi-page-marketing-site' => 'portfolio-8',
    'landing-page-microsite' => 'portfolio-9',
];

$themeImagesDir = get_stylesheet_directory() . '/assets/images';
$imported = 0;

foreach (get_posts(['post_type' => ProjectPostType::POST_TYPE, 'numberposts' => 50]) as $post) {
    if (has_post_thumbnail($post->ID)) {
        continue;
    }

    // Resolve the EN counterpart's slug for AR posts (translation-linked), else use the post's own.
    $enSlug = $post->post_name;
    if (function_exists('pll_get_post') && function_exists('pll_get_post_language')) {
        $lang = pll_get_post_language($post->ID);
        if ($lang && $lang !== 'en') {
            $enId = pll_get_post($post->ID, 'en');
            if ($enId) {
                $enSlug = get_post_field('post_name', $enId);
            }
        }
    }

    if (! isset(PROJECT_IMAGE[$enSlug])) {
        continue;
    }

    $file = $themeImagesDir . '/' . PROJECT_IMAGE[$enSlug] . '.png';
    if (! file_exists($file)) {
        WP_CLI::warning("Missing source image for {$enSlug}: {$file}");
        continue;
    }

    WP_CLI::runcommand(sprintf(
        'media import %s --post_id=%d --featured_image --title=%s',
        escapeshellarg($file),
        $post->ID,
        escapeshellarg(get_the_title($post))
    ));
    $imported++;
}

WP_CLI::success("Project featured images seeded — {$imported} post(s) updated (idempotent).");
