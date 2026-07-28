<?php

/**
 * Moves the Selected-work block from the service template into each Service post's own content
 * (spec 023; owner 2026-07-28).
 *
 * WHY. The block used to live in `single-perego_service.html` and resolve its service from
 * `get_queried_object()`. That works on the front end and fails in the editor: the Site Editor never
 * provides a queried object while editing a template, so the preview resolved no service and rendered
 * nothing — and a block in a shared template has no service to save a per-service order against. In
 * post content each Service owns its own block instance, `useEntityProp` reaches that service's meta,
 * and the order is the instance's own attribute. Arabic gets its own order for free, because the
 * Arabic Service post is its own row.
 *
 * Three modes, via SEED-style env var (default is the safe one):
 *
 *   MIGRATE_SSW_MODE=dry-run wp eval 'require ".../migrate-service-selected-work-block.php";' --path=wp
 *   MIGRATE_SSW_MODE=verify  …   inventory + the template-override check
 *   MIGRATE_SSW_MODE=apply   …   writes
 *
 * Run `verify` BEFORE `apply`. If anyone has saved `single-perego_service.html` in the Site Editor, a
 * `wp_template` row shadows the theme file — removing the block from the theme file then does nothing
 * and the section renders TWICE. Nothing here can detect that after the fact.
 *
 * The template edit is deliberately NOT done by this script: it is a source file, it belongs in the
 * commit, and it must land only after `apply` has succeeded so there is never a window where the
 * section is missing.
 *
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\PostTypes\ServicePostType;

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

const PEREGO_SSW_BLOCK = '<!-- wp:perego-theme/service-selected-work /-->';
const PEREGO_SSW_SIGNATURE = 'wp:perego-theme/service-selected-work';

/**
 * The already-done marker.
 *
 * A content signature alone is not enough: an editor who deliberately deletes the block from one
 * service would have it resurrected by the next run. The marker records that this post has been
 * migrated once, which is a different question from whether it currently holds the block.
 */
const PEREGO_SSW_MARKER = '_perego_ssw_block_migrated';

$mode = getenv('MIGRATE_SSW_MODE') ?: 'dry-run';
if (! in_array($mode, ['dry-run', 'verify', 'apply'], true)) {
    WP_CLI::error("Unknown MIGRATE_SSW_MODE '{$mode}' — expected 'dry-run', 'verify' or 'apply'.");
}

/**
 * Every Service post, in every language.
 *
 * `'lang' => ''` is load-bearing: without it Polylang filters the query to the current language and
 * only half the posts are found. The same idiom appears in nine other scripts in this directory.
 */
$services = get_posts([
    'post_type' => ServicePostType::POST_TYPE,
    'post_status' => 'any',
    'numberposts' => 50,
    'lang' => '',
]);

if ($services === []) {
    WP_CLI::warning('No Service posts found — nothing to migrate.');

    return;
}

$language = static function (int $postId): string {
    return function_exists('pll_get_post_language')
        ? (string) (pll_get_post_language($postId) ?: '—')
        : '—';
};

// A Site Editor override of the service template would make the section render twice after this runs.
$overrides = get_posts([
    'post_type' => 'wp_template',
    'post_status' => 'any',
    'numberposts' => 50,
    'name' => 'single-perego_service',
]);

if ($mode === 'dry-run' || $mode === 'verify') {
    WP_CLI::log(str_pad('POST', 8) . str_pad('LANG', 6) . str_pad('SLUG', 22) . str_pad('HAS BLOCK', 11) . 'MIGRATED');

    foreach ($services as $service) {
        $slug = (string) get_post_meta($service->ID, ServicePostType::META_SERVICE_SLUG, true);
        WP_CLI::log(
            str_pad('#' . $service->ID, 8)
            . str_pad($language($service->ID), 6)
            . str_pad($slug !== '' ? $slug : $service->post_name, 22)
            . str_pad(str_contains($service->post_content, PEREGO_SSW_SIGNATURE) ? 'yes' : 'no', 11)
            . (get_post_meta($service->ID, PEREGO_SSW_MARKER, true) ? 'yes' : 'no')
        );
    }

    if ($overrides !== []) {
        WP_CLI::warning(sprintf(
            'A saved Site Editor override of single-perego_service exists (%d). Clear it before removing '
            . 'the block from the theme template, or the section will render twice.',
            count($overrides)
        ));
    } else {
        WP_CLI::log('No Site Editor override of single-perego_service — the theme file is authoritative.');
    }

    if ($mode === 'verify') {
        $without = array_values(array_filter(
            $services,
            static fn (\WP_Post $service): bool => ! str_contains($service->post_content, PEREGO_SSW_SIGNATURE)
        ));

        foreach ($without as $service) {
            WP_CLI::warning("#{$service->ID} ({$language($service->ID)}) has no Selected-work block.");
        }
    }

    WP_CLI::success(sprintf('%s complete — %d service(s) inspected, nothing written.', ucfirst($mode), count($services)));

    return;
}

$migrated = 0;
$skipped = 0;

foreach ($services as $service) {
    if (get_post_meta($service->ID, PEREGO_SSW_MARKER, true)) {
        $skipped++;
        continue;
    }

    if (str_contains($service->post_content, PEREGO_SSW_SIGNATURE)) {
        // Already carries it — an editor may have placed it by hand. Mark it so the marker and the
        // content agree from here on.
        update_post_meta($service->ID, PEREGO_SSW_MARKER, 1);
        $skipped++;
        continue;
    }

    // Appended, because the template rendered it after `wp:post-content` — so the visual order on the
    // page is unchanged by the move.
    $content = rtrim($service->post_content) . "\n\n" . PEREGO_SSW_BLOCK;
    $result = wp_update_post(['ID' => $service->ID, 'post_content' => $content], true);

    if (is_wp_error($result)) {
        WP_CLI::warning("#{$service->ID}: " . $result->get_error_message());
        continue;
    }

    update_post_meta($service->ID, PEREGO_SSW_MARKER, 1);
    $migrated++;
}

/*
 * The block is added with an EMPTY `projectOrder` on purpose. Empty means "no manual order", which is
 * byte-identical to what the template rendered — and it keeps the behaviour where a newly published
 * project appears in the mosaic automatically. Seeding the current order would freeze that silently.
 */
WP_CLI::success("Selected-work block migrated into post content — {$migrated} updated, {$skipped} already done (idempotent).");
WP_CLI::log('Next: remove the block from perego-theme/templates/single-perego_service.html, then re-run with MIGRATE_SSW_MODE=verify.');
