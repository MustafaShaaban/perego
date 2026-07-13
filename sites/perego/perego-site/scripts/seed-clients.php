<?php

/**
 * Seed example clients (spec M6) for the homepage carousels. Idempotent — creates a client only if
 * one with its slug does not already exist, never overwrites later editor changes. Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/seed-clients.php";' --path=wp
 *
 * These are **PLACEHOLDER demo** clients (names clearly marked "Sample", stats are illustrative and
 * marked example) — replace with real, approved client data before launch. **Do not fabricate view
 * counts or real names.** See CONTENT_MODEL.md.
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

/** @var list<array{name: string, type: string, stat: string}> $clients */
$clients = [
    ['name' => 'Sample Corporate Client A', 'type' => 'corporate', 'stat' => ''],
    ['name' => 'Sample Corporate Client B', 'type' => 'corporate', 'stat' => ''],
    ['name' => 'Sample Corporate Client C', 'type' => 'corporate', 'stat' => ''],
    ['name' => 'Sample Corporate Client D', 'type' => 'corporate', 'stat' => ''],
    ['name' => 'Sample Creator One', 'type' => 'individual', 'stat' => 'Example stat'],
    ['name' => 'Sample Creator Two', 'type' => 'individual', 'stat' => 'Example stat'],
    ['name' => 'Sample Creator Three', 'type' => 'individual', 'stat' => 'Example stat'],
    ['name' => 'Sample Creator Four', 'type' => 'individual', 'stat' => 'Example stat'],
];

$created = 0;

foreach ($clients as $client) {
    $slug = sanitize_title($client['name']);

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
    if ($client['stat'] !== '') {
        update_post_meta($id, '_perego_client_stat', $client['stat']);
    }
    if ($pllReady && ! pll_get_post_language($id)) {
        pll_set_post_language($id, 'en');
    }
    $created++;
}

WP_CLI::success("Clients seeded — {$created} new; " . count($clients) . ' defined.');
