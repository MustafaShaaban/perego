<?php

/**
 * Seed **Arabic demo translations** of the EN demo content (spec Phase 6 — "import EN and AR records;
 * connect Polylang translations") and link them via Polylang Free. Covers the project (Work) CPT, the
 * Journal posts, their taxonomy terms, and the Journal/Home pages, so `/ar/work/`, AR project singles,
 * and `/ar/journal/` resolve as real Arabic entities. Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/seed-ar-content.php";' --path=wp
 *
 * Idempotent: creates an AR counterpart only when the EN item has no AR translation yet; never touches
 * later editor changes. All copy is **clearly-marked demo** mirroring the EN placeholders — no invented
 * business claims or metrics (same discipline as seed-projects.php / seed-journal.php).
 *
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\Content\PortfolioContent;
use PeregoSite\PostTypes\ProjectPostType;

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

if (! function_exists('pll_set_post_language') || ! function_exists('pll_save_post_translations')) {
    WP_CLI::error('Polylang is inactive — AR translations need Polylang.');
}
if (! in_array('ar', pll_languages_list(), true)) {
    WP_CLI::error('Arabic is not configured in Polylang.');
}

/** Link an EN post to a (new) AR post as translations of each other. */
$linkPosts = static function (int $enId, int $arId): void {
    pll_set_post_language($arId, 'ar');
    pll_save_post_translations(['en' => $enId, 'ar' => $arId]);
};

/** Ensure an AR term translation of an EN term exists; return the AR term id. */
$ensureArTerm = static function (string $taxonomy, string $enSlug, string $arName): int {
    $enTerm = get_term_by('slug', $enSlug, $taxonomy);
    if (! $enTerm) {
        return 0;
    }
    if (function_exists('pll_set_term_language') && ! pll_get_term_language($enTerm->term_id)) {
        pll_set_term_language((int) $enTerm->term_id, 'en');
    }

    $existingAr = function_exists('pll_get_term') ? pll_get_term((int) $enTerm->term_id, 'ar') : 0;
    if ($existingAr) {
        return (int) $existingAr;
    }

    $created = wp_insert_term($arName, $taxonomy, ['slug' => $enSlug . '-ar']);
    if (is_wp_error($created)) {
        return 0;
    }
    $arId = (int) $created['term_id'];
    pll_set_term_language($arId, 'ar');
    pll_save_term_translations(['en' => (int) $enTerm->term_id, 'ar' => $arId]);

    return $arId;
};

// ── Journal category terms (EN slug => AR name) ───────────────────────────────
$postCatAr = ['studio-notes' => 'ملاحظات الاستوديو', 'craft' => 'الحرفة', 'behind-the-scenes' => 'الكواليس'];
foreach ($postCatAr as $slug => $name) {
    $ensureArTerm('category', $slug, $name);
}

// ── Project category terms ────────────────────────────────────────────────────
$projCatAr = ['video' => 'مونتاج الفيديو', 'motion' => 'موشن جرافيك', 'design' => 'تصميم جرافيكي', 'web' => 'إنشاء المواقع'];
$projCatArId = [];
foreach ($projCatAr as $slug => $name) {
    $projCatArId[$slug] = $ensureArTerm(ProjectPostType::TAXONOMY, $slug, $name);
}

$arLabels = (new PortfolioContent('ar'))->projectLabels();
$arNarrative = '<!-- wp:paragraph {"className":"project-demo-note"} --><p class="project-demo-note"><em>مثال دراسة حالة — استبدل كل تفاصيل المشروع والوسائط والنتائج ببيانات مشاريع بيريجو الحقيقية.</em></p><!-- /wp:paragraph -->' . "\n\n";
foreach (['overviewTitle', 'challengeTitle', 'approachTitle', 'solutionTitle', 'resultTitle'] as $key) {
    $arNarrative .= '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html($arLabels[$key] ?? $key) . '</h2><!-- /wp:heading -->' . "\n";
    $arNarrative .= '<!-- wp:paragraph --><p>نص توضيحي قابل للتحرير على المحرّر. استبدله بالمحتوى الحقيقي.</p><!-- /wp:paragraph -->' . "\n\n";
}

// EN project slug => AR title.
$projectAr = [
    'brand-film-launch-campaign' => 'فيلم العلامة — حملة إطلاق',
    'product-teaser-cut' => 'تشويقة منتج',
    'event-recap-edit' => 'مونتاج ملخّص فعالية',
    'animated-explainer-series' => 'سلسلة فيديوهات توضيحية متحركة',
    'logo-sting-lower-thirds' => 'لوغو متحرك وعناصر سفلية',
    'visual-identity-system' => 'نظام هوية بصرية',
    'campaign-key-visual-suite' => 'مجموعة مرئيات حملة',
    'multi-page-marketing-site' => 'موقع تسويقي متعدد الصفحات',
    'landing-page-microsite' => 'صفحة هبوط وموقع مصغّر',
];

$projectsDone = 0;
foreach ($projectAr as $enSlug => $arTitle) {
    $en = get_posts(['post_type' => ProjectPostType::POST_TYPE, 'name' => $enSlug, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids']);
    if ($en === []) {
        continue;
    }
    $enId = (int) $en[0];
    if (pll_get_post($enId, 'ar')) {
        continue; // already translated
    }

    $arId = wp_insert_post([
        'post_type' => ProjectPostType::POST_TYPE,
        'post_status' => 'publish',
        'post_title' => $arTitle,
        'post_content' => $arNarrative,
        'post_excerpt' => 'العميل: عميل تجريبي',
    ], true);
    if (is_wp_error($arId)) {
        WP_CLI::warning("AR project {$enSlug}: " . $arId->get_error_message());
        continue;
    }
    $arId = (int) $arId;

    // Mirror the EN meta + assign the AR category term.
    foreach (['_perego_client', '_perego_year', '_perego_role', '_perego_deliverables'] as $metaKey) {
        $val = get_post_meta($enId, $metaKey, true);
        if ($val !== '') {
            update_post_meta($arId, $metaKey, $val);
        }
    }
    $enCat = wp_get_object_terms($enId, ProjectPostType::TAXONOMY, ['fields' => 'slugs']);
    if ($enCat !== [] && ! is_wp_error($enCat) && isset($projCatArId[$enCat[0]]) && $projCatArId[$enCat[0]] > 0) {
        wp_set_object_terms($arId, [$projCatArId[$enCat[0]]], ProjectPostType::TAXONOMY);
    }
    $linkPosts($enId, $arId);
    $projectsDone++;
}

// ── Journal posts (EN slug => AR title) ──────────────────────────────────────
$postAr = [
    'how-we-storyboard-a-motion-piece-example' => 'كيف نضع ستوري بورد لعمل موشن (مثال)',
    'colour-grading-notes-from-the-edit-bay-example' => 'ملاحظات تصحيح الألوان من غرفة المونتاج (مثال)',
    'behind-the-scenes-of-a-brand-film-example' => 'من كواليس فيلم علامة تجارية (مثال)',
];
$arPostBody = '<!-- wp:paragraph --><p><em>مقال تجريبي — استبدله بمقال حقيقي من بيريجو. لا تُذكر أي ادعاءات أو أرقام.</em></p><!-- /wp:paragraph -->' . "\n\n"
    . '<!-- wp:heading --><h2 class="wp-block-heading">عنوان قسم</h2><!-- /wp:heading -->' . "\n"
    . '<!-- wp:paragraph --><p>نص توضيحي للمقال. قابل للتحرير على المحرّر.</p><!-- /wp:paragraph -->';

$postsDone = 0;
foreach ($postAr as $enSlug => $arTitle) {
    $en = get_posts(['post_type' => 'post', 'name' => $enSlug, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids']);
    if ($en === []) {
        continue;
    }
    $enId = (int) $en[0];
    if (pll_get_post($enId, 'ar')) {
        continue;
    }

    $arId = wp_insert_post([
        'post_type' => 'post',
        'post_status' => 'publish',
        'post_title' => $arTitle,
        'post_content' => $arPostBody,
        'post_excerpt' => 'مقال تجريبي — للاستبدال بمحتوى حقيقي.',
    ], true);
    if (is_wp_error($arId)) {
        WP_CLI::warning("AR post {$enSlug}: " . $arId->get_error_message());
        continue;
    }
    $arId = (int) $arId;

    $enCat = wp_get_object_terms($enId, 'category', ['fields' => 'slugs']);
    if ($enCat !== [] && ! is_wp_error($enCat)) {
        $arTerm = function_exists('pll_get_term') ? pll_get_term((int) get_term_by('slug', $enCat[0], 'category')->term_id, 'ar') : 0;
        if ($arTerm) {
            wp_set_object_terms($arId, [(int) $arTerm], 'category');
        }
    }
    $linkPosts($enId, $arId);
    $postsDone++;
}

// ── Journal + Home pages (so /ar/journal/ resolves as the AR posts page) ─────
$pagesAr = ['journal' => 'المدونة', 'home' => 'الرئيسية'];
$pagesDone = 0;
foreach ($pagesAr as $enSlug => $arTitle) {
    $en = get_posts(['post_type' => 'page', 'name' => $enSlug, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids']);
    if ($en === []) {
        continue;
    }
    $enId = (int) $en[0];
    if (pll_get_post($enId, 'ar')) {
        continue;
    }
    $arId = wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => $arTitle,
        'post_content' => get_post_field('post_content', $enId),
    ], true);
    if (is_wp_error($arId)) {
        WP_CLI::warning("AR page {$enSlug}: " . $arId->get_error_message());
        continue;
    }
    $linkPosts($enId, (int) $arId);
    $pagesDone++;
}

WP_CLI::success("AR content seeded — projects: {$projectsDone}, posts: {$postsDone}, pages: {$pagesDone} (idempotent).");
