<?php

/**
 * One-time retrofit (spec 004 T014/T015): `seed-services.php` originally built the "What we do"
 * canvas section without the handoff's media image / two-column grid; that gap was fixed in the
 * script, but the four EN + four AR service posts already seeded from the *old* version keep their
 * original content (the seed script is deliberately idempotent and never touches an existing post).
 * This migrates only those pre-existing, still-seed-authored posts to the corrected structure —
 * detected by the absence of `svc-whatwedo__grid` in their content, so it is a no-op once every post
 * has been migrated (or hand-edited by an editor, which also removes the old signature and makes this
 * script leave it alone). Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/migrate-service-whatwedo-media.php";' --path=wp
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

/** @var array<string, string> */
const WHATWEDO_IMAGE = [
    'video-editing' => 'ui-video-editing',
    'motion-graphics' => 'ui-motion-graphics',
    'graphic-design' => 'ui-graphic-design',
    'website-making' => 'ui-graphic-design',
];

$buildWhatWeDo = static function (ServiceContent $content, string $slug): string {
    [$p1, $p2] = $content->intro($slug);
    $imageUrl = get_stylesheet_directory_uri() . '/assets/images/' . (WHATWEDO_IMAGE[$slug] ?? 'ui-video-editing') . '.png';

    $blocks = '<!-- wp:group {"align":"full","className":"svc-whatwedo","layout":{"type":"constrained"}} -->' . "\n";
    $blocks .= '<div class="wp-block-group alignfull svc-whatwedo">' . "\n";
    $blocks .= '<!-- wp:group {"className":"svc-whatwedo__grid","layout":{"type":"default"}} -->' . "\n";
    $blocks .= '<div class="wp-block-group svc-whatwedo__grid">' . "\n";
    $blocks .= '<!-- wp:group {"className":"svc-whatwedo__text","layout":{"type":"default"}} -->' . "\n";
    $blocks .= '<div class="wp-block-group svc-whatwedo__text">' . "\n";
    $blocks .= '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html($content->label('whatWeDo')) . '</h2><!-- /wp:heading -->' . "\n";
    $blocks .= '<!-- wp:paragraph --><p><strong>' . esc_html($content->subline($slug)) . '</strong></p><!-- /wp:paragraph -->' . "\n";
    $blocks .= '<!-- wp:paragraph --><p>' . esc_html($p1) . '</p><!-- /wp:paragraph -->' . "\n";
    $blocks .= '<!-- wp:paragraph --><p>' . esc_html($p2) . '</p><!-- /wp:paragraph -->' . "\n";
    $blocks .= '</div>' . "\n" . '<!-- /wp:group -->' . "\n";
    $blocks .= '<!-- wp:group {"className":"svc-whatwedo__media","layout":{"type":"default"}} -->' . "\n";
    $blocks .= '<div class="wp-block-group svc-whatwedo__media">' . "\n";
    $blocks .= '<!-- wp:image {"sizeSlug":"large"} --><figure class="wp-block-image size-large">'
        . '<img src="' . esc_url($imageUrl) . '" alt="' . esc_attr($content->name($slug)) . '" loading="lazy"/></figure><!-- /wp:image -->' . "\n";
    $blocks .= '</div>' . "\n" . '<!-- /wp:group -->' . "\n";
    $blocks .= '</div>' . "\n" . '<!-- /wp:group -->' . "\n";
    $blocks .= '</div>' . "\n";
    $blocks .= '<!-- /wp:group -->';

    return $blocks;
};

$migrated = 0;

foreach (get_posts(['post_type' => ServicePostType::POST_TYPE, 'numberposts' => 50]) as $post) {
    $body = (string) $post->post_content;
    if (str_contains($body, 'svc-whatwedo__grid')) {
        continue; // Already migrated (or hand-edited into a different shape) — leave it alone.
    }

    if (! preg_match('/<!-- wp:group \{"align":"full","className":"svc-whatwedo".*?<!-- \/wp:group -->/s', $body, $m)) {
        continue; // Not the expected old seed shape — don't touch it.
    }

    // Resolve this post's own slug from its Polylang-independent service key: the seeded post_name
    // differs by language (e.g. video-editing / video-editing-2), but both map to the same key.
    $baseSlug = null;
    foreach (array_keys(ServicePostType::SERVICES) as $slug) {
        if ($post->post_name === $slug || $post->post_name === $slug . '-2') {
            $baseSlug = $slug;
            break;
        }
    }
    if ($baseSlug === null) {
        continue;
    }

    $locale = function_exists('pll_get_post_language') ? (pll_get_post_language($post->ID) ?: 'en') : 'en';
    $content = new ServiceContent($locale);

    $newWhatWeDo = $buildWhatWeDo($content, $baseSlug);
    $newBody = str_replace($m[0], $newWhatWeDo, $body);

    wp_update_post(['ID' => $post->ID, 'post_content' => $newBody]);
    $migrated++;
}

WP_CLI::success("Service whatwedo media retrofit — {$migrated} post(s) migrated (idempotent).");
