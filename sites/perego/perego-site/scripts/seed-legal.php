<?php

/**
 * Seed the legal pages (Terms & Conditions, Privacy Policy) as editor-managed WordPress pages
 * (spec M4). Idempotent — creates a page only if its slug does not already exist, never overwrites
 * later editor changes. Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/seed-legal.php";' --path=wp
 *
 * The bodies are **layout DRAFTS** (numbered sections as editable block content, each H2 anchored so
 * the perego-theme/legal-toc block can build the table of contents from the page's own headings).
 * Marked draft; the review notice + "last updated" render via the legal-toc block. **Must be reviewed
 * by legal counsel before publication** — the copy here is placeholder guidance, not legal advice.
 *
 * English is seeded with the full section structure. Arabic is created + linked with the translated
 * title and an explicit "pending professional Arabic legal translation" note (Arabic legal prose is
 * not fabricated). Both pages use the `legal` custom template.
 *
 * @package PeregoSite
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

$pllReady = function_exists('pll_set_post_language') && function_exists('pll_get_post')
    && function_exists('pll_save_post_translations')
    && in_array('ar', function_exists('pll_languages_list') ? pll_languages_list() : [], true);

/** EN section headings (drive the anchored H2s + the generated TOC). */
$documents = [
    'terms' => [
        'title' => ['en' => 'Terms & Conditions', 'ar' => 'الشروط والأحكام'],
        'sections' => [
            'Acceptance of Terms', 'Services We Provide', 'Client Responsibilities',
            'Intellectual Property', 'Payments & Refunds', 'Limitation of Liability',
            'Changes to These Terms', 'Contact',
        ],
    ],
    'privacy' => [
        'title' => ['en' => 'Privacy Policy', 'ar' => 'سياسة الخصوصية'],
        'sections' => [
            'Information We Collect', 'How We Use Your Information', 'Cookies & Analytics',
            'Data Sharing', 'Data Retention', 'Your Rights', 'Contact',
        ],
    ],
];

$buildEnBody = static function (array $sections): string {
    $blocks = '<!-- wp:paragraph {"className":"legal__lead"} --><p class="legal__lead"><em>Draft for design/layout purposes — the section bodies below are placeholder guidance and must be written and reviewed by legal counsel before publication.</em></p><!-- /wp:paragraph -->' . "\n\n";

    foreach ($sections as $i => $heading) {
        $num = $i + 1;
        $anchor = 's' . $num;
        $blocks .= '<!-- wp:heading {"anchor":"' . $anchor . '"} --><h2 class="wp-block-heading" id="' . $anchor . '">'
            . esc_html($num . '. ' . $heading) . '</h2><!-- /wp:heading -->' . "\n";
        $blocks .= '<!-- wp:paragraph --><p>Draft section — replace with the reviewed legal text for "'
            . esc_html($heading) . '". Do not publish placeholder legal copy.</p><!-- /wp:paragraph -->' . "\n\n";
    }

    return $blocks;
};

$buildArBody = static function (string $titleAr): string {
    return '<!-- wp:paragraph {"className":"legal__lead"} --><p class="legal__lead"><em>مسودة تصميم — '
        . 'النص القانوني العربي الكامل لـ«' . esc_html($titleAr) . '» قيد الترجمة الاحترافية والمراجعة القانونية قبل النشر.</em></p><!-- /wp:paragraph -->';
};

$ensurePage = static function (string $slug, string $title, string $body, string $locale) use ($pllReady): int {
    // Find an existing page with this slug in this language (query all langs, match actual language).
    $ids = get_posts([
        'post_type' => 'page',
        'name' => $slug,
        'post_status' => 'any',
        'numberposts' => -1,
        'fields' => 'ids',
        'lang' => '',
        'suppress_filters' => true,
    ]);
    foreach ($ids as $id) {
        if (! $pllReady || pll_get_post_language((int) $id) === $locale) {
            return (int) $id;
        }
    }

    $postId = wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => $title,
        'post_name' => $slug,
        'post_content' => $body,
    ], true);

    if (is_wp_error($postId)) {
        WP_CLI::warning("Failed ({$locale}) {$slug}: " . $postId->get_error_message());

        return 0;
    }

    $postId = (int) $postId;
    update_post_meta($postId, '_wp_page_template', 'legal');
    update_post_meta($postId, '_perego_last_updated', 'July 1, 2026');

    if ($pllReady) {
        pll_set_post_language($postId, $locale);
    }

    WP_CLI::log("Created legal page ({$locale}): {$slug}");

    return $postId;
};

$created = 0;
$linked = 0;

foreach ($documents as $slug => $doc) {
    $enId = $ensurePage($slug, $doc['title']['en'], $buildEnBody($doc['sections']), 'en');
    if ($enId === 0) {
        continue;
    }
    $created++;

    if (! $pllReady || (int) pll_get_post($enId, 'ar') !== 0) {
        continue;
    }

    $arId = $ensurePage($slug, $doc['title']['ar'], $buildArBody($doc['title']['ar']), 'ar');
    if ($arId !== 0) {
        pll_save_post_translations(['en' => $enId, 'ar' => $arId]);
        $linked++;
    }
}

WP_CLI::success("Legal pages seeded — created/verified {$created}, EN/AR pairs linked {$linked}.");
