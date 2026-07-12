<?php

/**
 * Seed journal post featured images (spec 004 T016) from the approved handoff's own generic
 * portfolio stills, the same images the handoff's own journal archive (`archive.html`) reuses for
 * its post cards. Idempotent: only imports for a post that has no featured image yet, via WP-CLI's
 * `media import`. Each EN post and its AR translation get the same image. Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/seed-journal-media.php";' --path=wp
 *
 * @package PeregoSite
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

// EN post slug => portfolio still (matches the handoff's own archive.html reuse of these stills).
const JOURNAL_IMAGE = [
    'how-we-storyboard-a-motion-piece-example' => 'portfolio-1',
    'colour-grading-notes-from-the-edit-bay-example' => 'portfolio-3',
    'behind-the-scenes-of-a-brand-film-example' => 'portfolio-6',
];

$themeImagesDir = get_stylesheet_directory() . '/assets/images';
$imported = 0;

foreach (get_posts(['post_type' => 'post', 'numberposts' => 50, 'post_status' => 'publish']) as $post) {
    if (has_post_thumbnail($post->ID)) {
        continue;
    }

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

    if (! isset(JOURNAL_IMAGE[$enSlug])) {
        continue;
    }

    $file = $themeImagesDir . '/' . JOURNAL_IMAGE[$enSlug] . '.png';
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

WP_CLI::success("Journal featured images seeded — {$imported} post(s) updated (idempotent).");
