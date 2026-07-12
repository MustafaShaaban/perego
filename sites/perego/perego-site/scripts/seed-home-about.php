<?php

/**
 * Seed the homepage "About Us" / "Our mission" prose (spec 004 T012) as real, editable post content
 * on the static front page (`page_on_front`) and its Polylang AR translation, so the editor-canvas rule
 * (FR-005) applies to Home the same way it already does to service singles — the front-page.html
 * template renders these via `wp:post-content` inside `.home-about__panels`; the theme's home-about-bg
 * block supplies only the background image. Idempotent — writes content only into a page whose
 * post_content is currently empty, never overwrites later editor changes. Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/seed-home-about.php";' --path=wp
 *
 * Real Perego marketing copy carried over verbatim from the previous provider-rendered version
 * (HomeContent::about(), removed by this migration) — not placeholder.
 *
 * @package PeregoSite
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

/**
 * @param array{title: string, body: string, missionTitle: string, missionBody: string} $about
 * @param string|null $titleAnchor block anchor for the first heading, so the section's
 *        aria-labelledby (set in front-page.html) resolves; null for the AR page, which does not
 *        need its own landmark id (the EN page already provides it for the shared section).
 */
$panelsBlocks = static function (array $about, ?string $titleAnchor): string {
    $titleAttrs = $titleAnchor !== null
        ? wp_json_encode(['className' => 'panel-title', 'anchor' => $titleAnchor])
        : wp_json_encode(['className' => 'panel-title']);

    $blocks = '<!-- wp:group {"tagName":"article","className":"glass-panel","layout":{"type":"default"}} -->' . "\n";
    $blocks .= '<article class="wp-block-group glass-panel">' . "\n";
    $blocks .= '<!-- wp:heading ' . $titleAttrs . ' -->'
        . '<h2 class="wp-block-heading panel-title"'
        . ($titleAnchor !== null ? ' id="' . esc_attr($titleAnchor) . '"' : '')
        . '>' . esc_html($about['title']) . '</h2><!-- /wp:heading -->' . "\n";
    $blocks .= '<!-- wp:paragraph --><p>' . esc_html($about['body']) . '</p><!-- /wp:paragraph -->' . "\n";
    $blocks .= '</article>' . "\n" . '<!-- /wp:group -->' . "\n\n";

    $blocks .= '<!-- wp:group {"tagName":"article","className":"glass-panel","layout":{"type":"default"}} -->' . "\n";
    $blocks .= '<article class="wp-block-group glass-panel">' . "\n";
    $blocks .= '<!-- wp:heading {"className":"panel-title"} --><h2 class="wp-block-heading panel-title">'
        . esc_html($about['missionTitle']) . '</h2><!-- /wp:heading -->' . "\n";
    $blocks .= '<!-- wp:paragraph --><p>' . esc_html($about['missionBody']) . '</p><!-- /wp:paragraph -->' . "\n";
    $blocks .= '</article>' . "\n" . '<!-- /wp:group -->';

    return $blocks;
};

$en = [
    'title' => 'About Us',
    'body' => 'Perego is a leading creative agency specializing in advertising and digital production, serving a diverse client base across the Arab region. We offer a wide range of innovative services, including brand identity design, motion graphics with full storyboard development, diverse video editing solutions, and much more all crafted to elevate your business or digital presence.',
    'missionTitle' => 'Our mission',
    'missionBody' => 'deliver impactful marketing results that meet your specific needs with exceptional quality and reliable, on-time delivery.',
];

$ar = [
    'title' => 'من نحن',
    'body' => 'بيريجو وكالة إبداعية رائدة متخصصة في الإعلان والإنتاج الرقمي، تخدم قاعدة عملاء متنوعة في جميع أنحاء المنطقة العربية. نقدم مجموعة واسعة من الخدمات المبتكرة، تشمل تصميم الهوية التجارية، والموشن جرافيك مع تطوير كامل للستوري بورد، وحلول متنوعة لمونتاج الفيديو، وغير ذلك الكثير — كل ذلك مصمم للارتقاء بأعمالك أو حضورك الرقمي.',
    'missionTitle' => 'مهمتنا',
    'missionBody' => 'تحقيق نتائج تسويقية مؤثرة تلبي احتياجاتك الخاصة، بجودة استثنائية وتسليم موثوق في الموعد المحدد.',
];

$frontPageId = (int) get_option('page_on_front');
if ($frontPageId === 0) {
    WP_CLI::error('No static front page is configured (page_on_front) — nothing to seed.');
}

$seeded = 0;

if (trim((string) get_post_field('post_content', $frontPageId)) === '') {
    wp_update_post([
        'ID' => $frontPageId,
        'post_content' => $panelsBlocks($en, 'home-about-title'),
        // Explicit excerpt: PeregoMeta's SEO description now sources this page's own excerpt (it's
        // singular content), and the auto-generated one runs the "About Us" heading straight into the
        // body paragraph with no separator. Using the body alone reads naturally when truncated.
        'post_excerpt' => $en['body'],
    ]);
    $seeded++;
} else {
    WP_CLI::log("Front page {$frontPageId} already has content — left untouched.");
}

$arId = function_exists('pll_get_post') ? (int) pll_get_post($frontPageId, 'ar') : 0;

if ($arId > 0) {
    if (trim((string) get_post_field('post_content', $arId)) === '') {
        wp_update_post([
            'ID' => $arId,
            'post_content' => $panelsBlocks($ar, null),
            'post_excerpt' => $ar['body'],
        ]);
        $seeded++;
    } else {
        WP_CLI::log("AR front page {$arId} already has content — left untouched.");
    }
} else {
    WP_CLI::warning('No Arabic translation of the front page found — AR About content not seeded.');
}

WP_CLI::success("Home About content seeded — {$seeded} page(s) updated (idempotent).");
