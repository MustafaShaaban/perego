<?php

/**
 * Tops each mosaic service up to a full six tiles, so the per-shape crop system is visible (owner,
 * 2026-07-28).
 *
 * WHY THIS EXISTS SEPARATELY FROM `seed-projects.php`. That script defines twenty-three demo projects
 * per category for the "Load more" overflow. Re-running it here would create around sixty-five posts
 * and turn a 33-project archive into a 98-project one, which is not what "show me the mosaic working"
 * asks for. This creates only the shortfall — enough to fill the six tiles a service single actually
 * renders ({@see ServiceSelectedWorkRenderer::WORK_CAP_DEFAULT}), which is exactly the span where the
 * four crop shapes appear: m1 hero, m2 banner, m3 tall, m4 banner, m5 card, m6 banner.
 *
 * Website Making is deliberately absent: it branches to `WebShowcaseRenderer`, one uniform card shape,
 * and per the owner's rule the several-shapes crop set does not apply to it.
 *
 * Naming follows `seed-projects.php`'s "<Label> Example NN" convention and skips numbers already
 * taken, so a later full run of that script finds these by slug and does not duplicate them.
 *
 * Idempotent: a service already holding six published projects is left alone.
 *
 *   wp eval 'require "sites/perego/perego-site/scripts/seed-mosaic-demo-projects.php";' --path=wp --url=http://perego.local
 *
 * Then give them crops:
 *   SEED_CROPS_MODE=manifest wp eval 'require ".../seed-project-crops.php";' --path=wp
 *   node sites/perego/perego-site/scripts/generate-project-crops.mjs --manifest <printed path>
 *   SEED_CROPS_MODE=import   wp eval 'require ".../seed-project-crops.php";' --path=wp
 *
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\Blocks\ServiceSelectedWorkRenderer;
use PeregoSite\PostTypes\ProjectPostType;

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

/** The three categories the mosaic renders; `web` takes the showcase instead. */
const PEREGO_MOSAIC_CATEGORIES = [
    'video' => 'Video Editing',
    'motion' => '2D Motion Graphics',
    'design' => 'Graphic Design',
];

/**
 * The affordance each position advertises, cycled so the grid shows all three.
 *
 * The icon is an editorial choice rather than an inference, so a seed has to state one; cycling means
 * the demo shows the ▶, the gallery badge and a plain tile side by side.
 */
const PEREGO_DEMO_ICONS = ['none', 'play', 'gallery'];

$target = ServiceSelectedWorkRenderer::WORK_CAP_DEFAULT;
$themeImages = get_stylesheet_directory() . '/assets/images';
$created = 0;
$skipped = 0;

foreach (PEREGO_MOSAIC_CATEGORIES as $slug => $label) {
    $existing = get_posts([
        'post_type' => ProjectPostType::POST_TYPE,
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids',
        'lang' => 'en',
        'tax_query' => [[
            'taxonomy' => ProjectPostType::TAXONOMY,
            'field' => 'slug',
            'terms' => $slug,
        ]],
    ]);

    $shortfall = $target - count($existing);
    if ($shortfall <= 0) {
        WP_CLI::log(sprintf('%-22s already has %d — nothing to add.', $label, count($existing)));
        $skipped += count($existing);
        continue;
    }

    $number = 1;
    for ($made = 0; $made < $shortfall; $number++) {
        $title = sprintf('%s Example %02d', $label, $number);
        $postSlug = sanitize_title($title);

        // Skip a number already used — including by an unpublished or Arabic post, so the slug
        // namespace stays the one `seed-projects.php` would find.
        if (get_posts(['post_type' => ProjectPostType::POST_TYPE, 'name' => $postSlug, 'post_status' => 'any', 'numberposts' => 1, 'lang' => ''])) {
            continue;
        }

        $postId = wp_insert_post([
            'post_type' => ProjectPostType::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => $title,
            'post_name' => $postSlug,
            'post_content' => '<!-- wp:paragraph {"className":"project-demo-note"} -->'
                . '<p class="project-demo-note"><em>Example project — replace with Perego’s real work.</em></p>'
                . '<!-- /wp:paragraph -->',
        ], true);

        if (is_wp_error($postId)) {
            WP_CLI::warning("{$title}: " . $postId->get_error_message());
            continue;
        }

        wp_set_object_terms($postId, $slug, ProjectPostType::TAXONOMY);
        update_post_meta($postId, '_perego_client', 'Sample Client');
        update_post_meta($postId, '_perego_year', ($number % 2 === 0) ? '2025' : '2026');
        update_post_meta($postId, '_perego_role', 'Example role');
        update_post_meta($postId, '_perego_deliverables', 'Example deliverables');
        update_post_meta($postId, ProjectPostType::META_ICON, PEREGO_DEMO_ICONS[$made % count(PEREGO_DEMO_ICONS)]);

        if (function_exists('pll_set_post_language')) {
            pll_set_post_language($postId, 'en');
        }

        // A featured image is the precondition for a crop, and for the tile appearing at all —
        // `masonry()` drops any project with no usable media. The nine handoff stills are cycled.
        $still = $themeImages . '/portfolio-' . (($made % 9) + 1) . '.png';
        if (file_exists($still)) {
            WP_CLI::runcommand(sprintf(
                'media import %s --post_id=%d --featured_image --title=%s',
                escapeshellarg($still),
                $postId,
                escapeshellarg($title)
            ), ['exit_error' => false]);
        } else {
            WP_CLI::warning("Missing still for {$title}: {$still}");
        }

        $created++;
        $made++;
    }
}

WP_CLI::success("Mosaic demo projects seeded — {$created} created (idempotent; target {$target} per service).");
WP_CLI::log('Next: run the three crop steps in this file’s docblock, then reload a service page.');
