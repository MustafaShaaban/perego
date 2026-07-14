<?php

/**
 * Seeds the homepage services-teaser presentation meta (label + alt) onto each `perego_service`
 * post from the HomeContent handoff seed, keyed by `_perego_service_slug` and the post's actual
 * Polylang language. Idempotent (re-running writes the same values). The card image intentionally
 * stays the theme `card-*.png` asset — the renderer falls back to it unless an owner sets
 * `_perego_teaser_image_id` to a media-library attachment, so this seeder never touches the image.
 *
 * Non-destructive: only ever writes the two teaser text fields, never deletes owner content. Dry-run
 * by default; set SEED_TEASER_MODE=apply to write. Run:
 *   SEED_TEASER_MODE=dryrun wp eval 'require "sites/perego/perego-site/scripts/seed-service-teaser-meta.php";' --user=admin
 *   SEED_TEASER_MODE=apply  wp eval 'require "sites/perego/perego-site/scripts/seed-service-teaser-meta.php";' --user=admin
 *
 * @package PeregoSite
 */

use PeregoSite\Content\HomeContent;
use PeregoSite\PostTypes\ServicePostType;

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Must run inside WordPress (wp eval).\n");

    return;
}

$mode  = getenv('SEED_TEASER_MODE') ?: 'dryrun';
$apply = $mode === 'apply';

// Seed cards keyed by locale then canonical slug.
$seedByLocale = [];
foreach (['en', 'ar'] as $locale) {
    $bySlug = [];
    foreach ((new HomeContent($locale))->services() as $card) {
        $bySlug[$card['slug']] = $card;
    }
    $seedByLocale[$locale] = $bySlug;
}

// All published services across every language ('lang' => '' asks Polylang for all).
$query = new WP_Query([
    'post_type'      => ServicePostType::POST_TYPE,
    'post_status'    => 'publish',
    'posts_per_page' => 50,
    'no_found_rows'  => true,
    'lang'           => '',
]);

$lines   = [];
$written = 0;

foreach ($query->posts as $post) {
    $locale = function_exists('pll_get_post_language') ? (string) pll_get_post_language($post->ID) : 'en';
    if (! isset($seedByLocale[$locale])) {
        $locale = 'en';
    }

    $slug = (string) get_post_meta($post->ID, ServicePostType::META_SERVICE_SLUG, true);
    $card = $seedByLocale[$locale][$slug] ?? null;
    if ($card === null) {
        $lines[] = sprintf('SKIP post=%d locale=%s slug=%s (no seed match)', $post->ID, $locale, $slug);
        continue;
    }

    $curLabel = (string) get_post_meta($post->ID, ServicePostType::META_TEASER_LABEL, true);
    $curAlt   = (string) get_post_meta($post->ID, ServicePostType::META_TEASER_ALT, true);
    $setLabel = $curLabel !== $card['name'];
    $setAlt   = $curAlt !== $card['alt'];

    $lines[] = sprintf(
        'post=%d %s/%s label="%s"%s alt="%s"%s',
        $post->ID,
        $locale,
        $slug,
        $card['name'],
        $setLabel ? ' [SET]' : ' [ok]',
        $card['alt'],
        $setAlt ? ' [SET]' : ' [ok]'
    );

    if ($apply) {
        if ($setLabel) {
            update_post_meta($post->ID, ServicePostType::META_TEASER_LABEL, $card['name']);
            $written++;
        }
        if ($setAlt) {
            update_post_meta($post->ID, ServicePostType::META_TEASER_ALT, $card['alt']);
            $written++;
        }
    }
}
wp_reset_postdata();

echo "SEED-TEASER MODE={$mode}\n";
echo implode("\n", $lines) . "\n";
echo $apply ? "Applied {$written} meta writes.\n" : "Dry-run only — no writes. Set SEED_TEASER_MODE=apply to write.\n";
