<?php

/**
 * Seed the four fixed services (spec 003 / M3, US3) as editor-managed `perego_service` posts.
 * Idempotent — creates a service only if a post with its slug does not already exist, and never
 * overwrites later editor changes. Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/seed-services.php";' --path=wp
 *
 * The editorial "What we do" (sub-line + two paragraphs) and "Our Process" (four steps) copy is
 * written as real block markup into each post's `post_content`, so it is fully editable on the
 * block-editor canvas (the editor-canvas rule) — not locked in PHP. ServiceContent is only the
 * default seed source. English is seeded here; the Arabic translations + Polylang linkage are a
 * separate seeding step (see docs/multilingual-guide.md once Polylang languages are configured).
 *
 * This is real Perego marketing copy from the handoff (not placeholder) — see CONTENT_MODEL.md.
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

/**
 * Build the editable block-editor post_content for one service from its seed copy.
 */
$buildContent = static function (ServiceContent $content, string $slug): string {
    [$p1, $p2] = $content->intro($slug);

    $blocks = '<!-- wp:group {"align":"full","className":"svc-whatwedo","layout":{"type":"constrained"}} -->' . "\n";
    $blocks .= '<div class="wp-block-group alignfull svc-whatwedo">' . "\n";
    $blocks .= '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html($content->label('whatWeDo')) . '</h2><!-- /wp:heading -->' . "\n";
    $blocks .= '<!-- wp:paragraph --><p><strong>' . esc_html($content->subline($slug)) . '</strong></p><!-- /wp:paragraph -->' . "\n";
    $blocks .= '<!-- wp:paragraph --><p>' . esc_html($p1) . '</p><!-- /wp:paragraph -->' . "\n";
    $blocks .= '<!-- wp:paragraph --><p>' . esc_html($p2) . '</p><!-- /wp:paragraph -->' . "\n";
    $blocks .= '</div>' . "\n";
    $blocks .= '<!-- /wp:group -->' . "\n\n";

    $blocks .= '<!-- wp:group {"align":"full","className":"svc-process","layout":{"type":"constrained"}} -->' . "\n";
    $blocks .= '<div class="wp-block-group alignfull svc-process">' . "\n";
    $blocks .= '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html($content->label('ourProcess')) . '</h2><!-- /wp:heading -->' . "\n";
    $blocks .= '<!-- wp:list {"ordered":true} --><ol class="wp-block-list">' . "\n";
    foreach ($content->processSteps($slug) as $step) {
        $blocks .= '<!-- wp:list-item --><li><strong>' . esc_html($step['label']) . '</strong> — '
            . esc_html($step['desc']) . '</li><!-- /wp:list-item -->' . "\n";
    }
    $blocks .= '</ol><!-- /wp:list -->' . "\n";
    $blocks .= '</div>' . "\n";
    $blocks .= '<!-- /wp:group -->' . "\n";

    return $blocks;
};

$content = new ServiceContent('en');
$order = 0;
$created = 0;

foreach ($content->slugs() as $slug) {
    $order++;

    $existing = get_posts([
        'post_type' => ServicePostType::POST_TYPE,
        'name' => $slug,
        'post_status' => 'any',
        'numberposts' => 1,
        'fields' => 'ids',
    ]);

    if ($existing !== []) {
        continue; // idempotent — never overwrite later editor changes
    }

    $postId = wp_insert_post([
        'post_type' => ServicePostType::POST_TYPE,
        'post_status' => 'publish',
        'post_title' => $content->fullName($slug),
        'post_name' => $slug,
        'post_excerpt' => $content->subline($slug),
        'post_content' => $buildContent($content, $slug),
        'menu_order' => $order,
    ], true);

    if (is_wp_error($postId)) {
        WP_CLI::warning("Failed: {$slug} — " . $postId->get_error_message());
        continue;
    }

    update_post_meta($postId, '_perego_service_slug', $slug);
    $created++;
    WP_CLI::log("Created service: {$slug}");
}

WP_CLI::success("Seeded {$created} service(s); " . count($content->slugs()) . ' total defined.');
