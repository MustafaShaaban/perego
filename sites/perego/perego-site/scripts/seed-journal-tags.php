<?php

/**
 * Seed post tags on the demo journal posts so the single-post template's tag row
 * (perego-theme/templates/single.html → wp:post-terms{term:post_tag}) matches the handoff's
 * `#motion-design #branding …` pill row (single-post.html). Without tags that block renders nothing,
 * so the single page looked "not identical" to the design. Idempotent — only tags a post that has
 * none yet, so it never fights later editor changes. Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/seed-journal-tags.php";' --path=wp
 *
 * Tags are demo content chosen per category from a small handoff-style pool. Under Polylang every
 * language keeps its own `post_tag` terms; this seeds each published post in its own language using
 * that post's own (locale-appropriate) tag labels, so EN and AR posts are tagged natively without
 * cross-linking work.
 *
 * @package PeregoSite
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

/**
 * Category slug → demo tag labels. English and Arabic pools mirror each other so a post is tagged
 * in its own language. Slugs are matched case-insensitively against the post's category.
 *
 * @var array<string, array{en: list<string>, ar: list<string>}>
 */
$tagPool = [
    'craft' => [
        'en' => ['Motion Design', 'Branding', 'Animation'],
        'ar' => ['تصميم موشن', 'الهوية البصرية', 'أنيميشن'],
    ],
    'studio-notes' => [
        'en' => ['Studio Notes', 'Process', 'Web'],
        'ar' => ['ملاحظات الاستوديو', 'العملية', 'الويب'],
    ],
    'behind-the-scenes' => [
        'en' => ['Behind the Scenes', 'Video', 'Production'],
        'ar' => ['من الكواليس', 'فيديو', 'الإنتاج'],
    ],
];

$defaultTags = ['en' => ['Studio Notes', 'Craft'], 'ar' => ['ملاحظات الاستوديو', 'الحرفة']];

$pllReady = function_exists('pll_get_post_language');

$tagged = 0;
// Bounded (not -1): the demo journal is a handful of posts; 200 covers EN + AR with headroom.
foreach (get_posts(['post_type' => 'post', 'numberposts' => 200, 'post_status' => 'publish', 'lang' => '']) as $post) {
    $existing = wp_get_post_terms($post->ID, 'post_tag', ['fields' => 'ids']);
    if (is_array($existing) && $existing !== []) {
        continue; // Never overwrite tags an editor may have set.
    }

    $locale = $pllReady ? (pll_get_post_language($post->ID, 'slug') ?: 'en') : 'en';
    $lang = $locale === 'ar' ? 'ar' : 'en';

    $cats = wp_get_post_categories($post->ID, ['fields' => 'slugs']);
    $labels = $defaultTags[$lang];
    foreach ($cats as $slug) {
        if (isset($tagPool[$slug])) {
            $labels = $tagPool[$slug][$lang];
            break;
        }
    }

    // wp_set_post_tags accepts term names; new terms are created in the post's language below.
    $result = wp_set_post_terms($post->ID, $labels, 'post_tag', false);
    if (is_wp_error($result) || $result === false) {
        WP_CLI::warning("Post {$post->ID}: could not set tags.");
        continue;
    }

    // Give any newly-created tag terms the post's language so Polylang keeps EN/AR separate.
    if ($pllReady && function_exists('pll_set_term_language')) {
        foreach (wp_get_post_terms($post->ID, 'post_tag', ['fields' => 'ids']) as $termId) {
            if (! pll_get_term_language((int) $termId)) {
                pll_set_term_language((int) $termId, $lang);
            }
        }
    }

    $tagged++;
}

WP_CLI::success("Journal post tags seeded — {$tagged} post(s) tagged (idempotent).");
