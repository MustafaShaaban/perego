<?php

/**
 * Seed the four fixed services (spec 003 / M3, US3) as editor-managed `perego_service` posts, in
 * **both** English and Arabic, linked as Polylang translations. Idempotent — creates a language's
 * post only if it does not already exist, never overwrites later editor changes, and links the
 * EN/AR pair without duplicating. Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/seed-services.php";' --path=wp
 *
 * The editorial "What we do" (sub-line + two paragraphs) and "Our Process" (four steps) copy is
 * written as real block markup into each post's `post_content`, so it is fully editable on the
 * block-editor canvas (the editor-canvas rule) — ServiceContent is only the default seed source.
 * Real Perego marketing copy from the handoff (not placeholder) — see CONTENT_MODEL.md.
 *
 * Requires Polylang languages to be configured first (scripts/configure-languages.php). Without
 * Polylang the EN posts are still seeded; the AR posts + linking are skipped with a notice.
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

$pllReady = function_exists('pll_set_post_language')
    && function_exists('pll_get_post')
    && function_exists('pll_save_post_translations')
    && in_array('ar', function_exists('pll_languages_list') ? pll_languages_list() : [], true);

if (! $pllReady) {
    WP_CLI::warning('Polylang EN/AR not fully configured — seeding English only, no translation links.');
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

/**
 * Find an existing service post for a slug in a given language (falls back to any language when
 * Polylang is off). Returns the post ID or 0.
 */
$findPost = static function (string $slug, string $locale) use ($pllReady): int {
    // Query across ALL languages (Polylang's `lang` query var is unreliable in a raw eval context),
    // then match on the post's actual assigned language. EN and AR can share the same slug.
    $ids = get_posts([
        'post_type' => ServicePostType::POST_TYPE,
        'name' => $slug,
        'post_status' => 'any',
        'numberposts' => -1,
        'fields' => 'ids',
        'lang' => '',
        'suppress_filters' => true,
    ]);

    foreach ($ids as $id) {
        if (! $pllReady) {
            return (int) $id;
        }
        if (pll_get_post_language((int) $id) === $locale) {
            return (int) $id;
        }
    }

    return 0;
};

/**
 * Ensure a service post exists for (slug, locale); create it (with editable content + language) if
 * missing. Returns the post ID, or 0 on failure.
 */
$ensurePost = static function (string $slug, string $locale) use ($buildContent, $findPost, $pllReady): int {
    $existing = $findPost($slug, $locale);
    if ($existing !== 0) {
        return $existing;
    }

    $content = new ServiceContent($locale);

    $postId = wp_insert_post([
        'post_type' => ServicePostType::POST_TYPE,
        'post_status' => 'publish',
        'post_title' => $content->fullName($slug),
        'post_name' => $slug,
        'post_excerpt' => $content->subline($slug),
        'post_content' => $buildContent($content, $slug),
        'menu_order' => array_search($slug, $content->slugs(), true) + 1,
    ], true);

    if (is_wp_error($postId)) {
        WP_CLI::warning("Failed ({$locale}) {$slug}: " . $postId->get_error_message());

        return 0;
    }

    $postId = (int) $postId;

    if ($pllReady) {
        // Assign the post's language. Polylang **Free** cannot share a slug between translations
        // (that is a Pro-only feature), so WP's wp_unique_post_slug correctly gives the non-first
        // language a distinct slug (e.g. the AR post becomes "<slug>-2"); its URL is still cleanly
        // namespaced by the /ar/ directory prefix. We deliberately do NOT force a shared slug — the
        // implementation must pass with no Pro dependency. See docs/multilingual-guide.md.
        pll_set_post_language($postId, $locale);
    }

    update_post_meta($postId, '_perego_service_slug', $slug);
    WP_CLI::log("Created service ({$locale}): {$slug}");

    return $postId;
};

$content = new ServiceContent('en');
$createdEn = 0;
$createdAr = 0;
$linked = 0;

foreach ($content->slugs() as $slug) {
    $enBefore = $findPost($slug, 'en');
    $enId = $ensurePost($slug, 'en');
    if ($enId !== 0 && $enBefore === 0) {
        $createdEn++;
    }

    if (! $pllReady || $enId === 0) {
        continue;
    }

    // Only create/link AR when no AR translation is linked yet (idempotent).
    $arLinked = (int) pll_get_post($enId, 'ar');
    if ($arLinked !== 0) {
        continue;
    }

    $arBefore = $findPost($slug, 'ar');
    $arId = $ensurePost($slug, 'ar');
    if ($arId === 0) {
        continue;
    }
    if ($arBefore === 0) {
        $createdAr++;
    }

    pll_save_post_translations(['en' => $enId, 'ar' => $arId]);
    $linked++;
}

WP_CLI::success(
    "Services seeded — EN created: {$createdEn}, AR created: {$createdAr}, EN/AR pairs linked: {$linked}."
);
