<?php

/**
 * Backfill demo media onto the seeded clients (spec 020 round 6) so the homepage clients section
 * behaves like the handoff prototype: every corporate tile opens a 4-item mixed lightbox gallery
 * (3 approved handoff stills + 1 placeholder embed — the prototype's own corporateSlider() pool),
 * and every individual card gets a placeholder embed + thumbnail, i.e. the ▶ lightbox card.
 * Owner-approved interactivity (overrides the handoff docs' C-05 "corporate non-interactive by
 * default"); replace with each client's real, approved media.
 *
 * Set-only-when-absent on every field (never overwrites editor media), covers EN and AR client
 * posts alike (the carousel reads each language's own post meta), and reuses one shared attachment
 * per still (found by its stable title) instead of importing duplicates — so re-running changes
 * nothing. Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/seed-client-media.php";' --path=wp
 *
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\PostTypes\ClientPostType;

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

require __DIR__ . '/lib-project-variants.php';

$themeImagesDir = get_stylesheet_directory() . '/assets/images';

/**
 * The attachment ID for one shared demo asset, importing it once under a stable title.
 *
 * @var array<string, int> $assetCache
 */
$assetCache = [];
$demoAsset = static function (string $fileBase) use (&$assetCache, $themeImagesDir): int {
    if (isset($assetCache[$fileBase])) {
        return $assetCache[$fileBase];
    }

    $title = 'Perego demo media ' . $fileBase;
    $existing = get_posts([
        'post_type' => 'attachment',
        'title' => $title,
        'post_status' => 'inherit',
        'numberposts' => 1,
        'fields' => 'ids',
    ]);
    if ($existing !== []) {
        return $assetCache[$fileBase] = (int) $existing[0];
    }

    $file = $themeImagesDir . '/' . $fileBase . '.png';
    if (! file_exists($file)) {
        WP_CLI::warning("Missing source image: {$file}");

        return $assetCache[$fileBase] = 0;
    }

    $id = (int) WP_CLI::runcommand(
        sprintf('media import %s --title=%s --porcelain', escapeshellarg($file), escapeshellarg($title)),
        ['return' => true],
    );

    return $assetCache[$fileBase] = $id;
};

$clients = get_posts([
    'post_type' => ClientPostType::POST_TYPE,
    'post_status' => 'any',
    'numberposts' => 50,
    'lang' => '', // EN and AR — the carousel reads each language's own post meta.
]);

$galleriesSet = 0;
$videosSet = 0;
$thumbsSet = 0;
$corporateIndex = 0;

foreach ($clients as $client) {
    $terms = wp_get_object_terms($client->ID, ClientPostType::TAXONOMY, ['fields' => 'slugs']);
    $terms = is_wp_error($terms) ? [] : $terms;
    $isCorporate = (bool) array_filter($terms, static fn (string $slug): bool => str_starts_with($slug, 'corporate'));
    $isIndividual = (bool) array_filter($terms, static fn (string $slug): bool => str_starts_with($slug, 'individual'));

    if ($isCorporate) {
        $offset = $corporateIndex++;
        $existing = get_post_meta($client->ID, ClientPostType::META_GALLERY, true);
        if (is_array($existing) && $existing !== []) {
            continue;
        }

        // 3 stills (offset per client so neighbouring tiles differ) + 1 embed — the prototype's mix.
        $gallery = [];
        for ($k = 0; $k < 3; $k++) {
            $id = $demoAsset('portfolio-' . (($offset * 2 + $k) % 9 + 1));
            if ($id > 0) {
                $gallery[] = ['type' => 'image', 'id' => $id, 'url' => ''];
            }
        }
        $gallery[] = ['type' => 'video', 'id' => 0, 'url' => peregoDemoVideoUrl()];

        update_post_meta($client->ID, ClientPostType::META_GALLERY, $gallery);
        $galleriesSet++;
    }

    if (! $isIndividual) {
        continue;
    }

    if ((string) get_post_meta($client->ID, ClientPostType::META_VIDEO_URL, true) === '') {
        update_post_meta($client->ID, ClientPostType::META_VIDEO_URL, peregoDemoVideoUrl());
        update_post_meta($client->ID, ClientPostType::META_VIDEO_TYPE, 'embed');
        $videosSet++;
    }

    if (! has_post_thumbnail($client->ID)) {
        $thumbId = $demoAsset('client-review-crop');
        if ($thumbId > 0) {
            set_post_thumbnail($client->ID, $thumbId);
            $thumbsSet++;
        }
    }
}

WP_CLI::success(
    "Client demo media backfilled — galleries: {$galleriesSet}, videos: {$videosSet}, thumbnails: {$thumbsSet} (idempotent)."
);
