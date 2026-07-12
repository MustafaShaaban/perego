<?php

/**
 * Seed the "Open Application" job (spec Phase 7) that the footer "Join us" / CV form submits against.
 * Idempotent — creates the job only if its slug does not already exist. Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/seed-careers.php";' --path=wp
 *
 * The handoff "Join us" form is a general talent/CV intake, not a per-vacancy application, so it needs
 * one standing job record to attach applications to. Provided by CoreX Careers (corex_job).
 *
 * @package PeregoSite
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

if (! post_type_exists('corex_job')) {
    WP_CLI::warning('CoreX Careers (corex_job) is not active — activate corex-careers first.');

    return;
}

$existing = get_posts([
    'post_type' => 'corex_job',
    'name' => 'open-application',
    'post_status' => 'any',
    'numberposts' => 1,
    'fields' => 'ids',
]);

if ($existing !== []) {
    WP_CLI::success('Open Application job already present (id ' . (int) $existing[0] . ').');

    return;
}

$id = wp_insert_post([
    'post_type' => 'corex_job',
    'post_status' => 'publish',
    'post_title' => 'Open Application',
    'post_name' => 'open-application',
    'post_content' => '<!-- wp:paragraph --><p>Don\'t see a role that fits? Send us your portfolio and CV — we\'re always keen to meet talented creators.</p><!-- /wp:paragraph -->',
], true);

if (is_wp_error($id)) {
    WP_CLI::warning('Failed to create Open Application job: ' . $id->get_error_message());

    return;
}

WP_CLI::success('Open Application job seeded (id ' . (int) $id . ').');
