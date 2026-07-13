<?php

/**
 * Seed the Contact / "Start a Project" page (spec Phase 7) as an editor-managed WordPress page.
 * Idempotent — creates a page only if its slug does not already exist, never overwrites later
 * editor changes. Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/seed-contact.php";' --path=wp
 *
 * The header nav + the "Start a Project" CTA both link to `/contact`; this gives that route a real
 * page. The page body is editor-managed block content: an intro paragraph the site owner can edit,
 * followed by the perego-project-brief CoreX form block (name, email, phone, company, budget,
 * subject, message + the service chooser that honours `?service=`). The default `page.html` template
 * renders the H1 (post-title) + this content + the footer (whose quick-message + careers columns
 * carry their own forms). English is seeded in full; Arabic is created + linked with translated copy.
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

$formBlock = '<!-- wp:corex/form {"formSlug":"perego-project-brief"} /-->';

$body = [
    'en' => '<!-- wp:paragraph {"className":"contact__lead"} --><p class="contact__lead">'
        . 'Tell us about your project and pick the services you need — we usually reply within one working day.'
        . '</p><!-- /wp:paragraph -->' . "\n\n" . $formBlock,
    'ar' => '<!-- wp:paragraph {"className":"contact__lead"} --><p class="contact__lead">'
        . 'أخبرنا عن مشروعك واختر الخدمات التي تحتاجها — نردّ عادةً خلال يوم عمل واحد.'
        . '</p><!-- /wp:paragraph -->' . "\n\n" . $formBlock,
];

$title = ['en' => 'Start a Project', 'ar' => 'ابدأ مشروعًا'];
$slug = 'contact';

$ensurePage = static function (string $slug, string $title, string $body, string $locale) use ($pllReady): int {
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

    if ($pllReady) {
        pll_set_post_language($postId, $locale);
    }

    WP_CLI::log("Created contact page ({$locale}): {$slug}");

    return $postId;
};

$enId = $ensurePage($slug, $title['en'], $body['en'], 'en');
$linked = 0;

if ($enId !== 0 && $pllReady && (int) pll_get_post($enId, 'ar') === 0) {
    $arId = $ensurePage($slug, $title['ar'], $body['ar'], 'ar');
    if ($arId !== 0) {
        pll_save_post_translations(['en' => $enId, 'ar' => $arId]);
        $linked++;
    }
}

WP_CLI::success("Contact page seeded — EN id {$enId}, EN/AR pairs linked {$linked}.");
