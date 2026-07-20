<?php

/**
 * Seed example clients (spec M6 / spec 020) for the homepage carousels. Idempotent — creates a client
 * only if one with its slug does not already exist, never overwrites later editor changes. Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/seed-clients.php";' --path=wp
 *
 * These are **PLACEHOLDER demo** clients staged for spec 020 home visual parity: 20 icon-only
 * corporate tiles plus three individual "REVIEW EL ETNEN" showreel cards. The individual cards carry
 * an owner-approved *illustrative* stat ("+1M views") purely so the section matches the reference
 * layout in demo — real, approved client names/logos/stats/videos still replace all of this before
 * launch (FR-006). See CONTENT_MODEL.md and DECISIONS.md.
 *
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\PostTypes\ClientPostType;

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

$pllReady = function_exists('pll_set_post_language') && function_exists('pll_get_post_language')
    && in_array('en', function_exists('pll_languages_list') ? pll_languages_list() : [], true);

foreach (ClientPostType::TYPES as $slug => $name) {
    if (! term_exists($slug, ClientPostType::TAXONOMY)) {
        $term = wp_insert_term($name, ClientPostType::TAXONOMY, ['slug' => $slug]);
        if (! is_wp_error($term) && $pllReady && function_exists('pll_set_term_language')) {
            pll_set_term_language((int) $term['term_id'], 'en');
        }
    }
}

// Retire the earlier sparse placeholder set (4 corporate + 4 creators) so it does not inflate the
// carousels next to the new demo tiles. Demo seed data only — never editor-authored content.
$legacySlugs = [
    'sample-corporate-client-a', 'sample-corporate-client-b',
    'sample-corporate-client-c', 'sample-corporate-client-d',
    'sample-creator-one', 'sample-creator-two', 'sample-creator-three', 'sample-creator-four',
];
$removed = 0;
foreach ($legacySlugs as $legacySlug) {
    foreach (get_posts([
        'post_type' => ClientPostType::POST_TYPE,
        'name' => $legacySlug,
        'post_status' => 'any',
        'numberposts' => 1,
        'fields' => 'ids',
    ]) as $legacyId) {
        wp_delete_post((int) $legacyId, true);
        $removed++;
    }
}

/**
 * @var list<array{name:string, slug:string, type:string, sub?:string, stat?:string, video_url?:string, video_type?:string}> $clients
 */
$clients = [];

// 20 icon-only corporate tiles (names are aria-label only; the tile shows the equalizer glyph).
for ($i = 1; $i <= 20; $i++) {
    $label = sprintf('Sample Corporate Client %02d', $i);
    $clients[] = ['name' => $label, 'slug' => sanitize_title($label), 'type' => 'corporate'];
}

// Three individual showreel cards — same display title, distinct slugs so all three are created.
for ($i = 1; $i <= 3; $i++) {
    $clients[] = [
        'name' => 'REVIEW EL ETNEN',
        'slug' => 'review-el-etnen-' . $i,
        'type' => 'individual',
        'sub' => 'intertainment show',
        'stat' => '<strong>+1M</strong> views',
        // Neutral, embeddable placeholder clip (Big Buck Bunny, CC) — replace with the real showreel.
        'video_url' => 'https://www.youtube.com/embed/aqz-KE-bpKQ',
        'video_type' => 'embed',
    ];
}

$created = 0;

foreach ($clients as $client) {
    $slug = $client['slug'];

    $existing = get_posts([
        'post_type' => ClientPostType::POST_TYPE,
        'name' => $slug,
        'post_status' => 'any',
        'numberposts' => 1,
        'fields' => 'ids',
    ]);
    if ($existing !== []) {
        continue;
    }

    $id = wp_insert_post([
        'post_type' => ClientPostType::POST_TYPE,
        'post_status' => 'publish',
        'post_title' => $client['name'],
        'post_name' => $slug,
        'post_content' => '<!-- wp:paragraph --><p>Example client — replace with a real, approved client and media.</p><!-- /wp:paragraph -->',
    ], true);

    if (is_wp_error($id)) {
        WP_CLI::warning("Client {$slug}: " . $id->get_error_message());
        continue;
    }

    $id = (int) $id;
    wp_set_object_terms($id, $client['type'], ClientPostType::TAXONOMY);

    if (($client['sub'] ?? '') !== '') {
        update_post_meta($id, ClientPostType::META_SUB, $client['sub']);
    }
    if (($client['stat'] ?? '') !== '') {
        update_post_meta($id, ClientPostType::META_STAT, $client['stat']);
    }
    if (($client['video_url'] ?? '') !== '') {
        update_post_meta($id, ClientPostType::META_VIDEO_URL, $client['video_url']);
        update_post_meta($id, ClientPostType::META_VIDEO_TYPE, $client['video_type'] ?? 'embed');
    }

    if ($pllReady && ! pll_get_post_language($id)) {
        pll_set_post_language($id, 'en');
    }
    $created++;
}

WP_CLI::success("Clients seeded — {$created} new, {$removed} legacy removed; " . count($clients) . ' defined.');
