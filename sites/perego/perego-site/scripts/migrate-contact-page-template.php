<?php

/**
 * One-time retrofit (spec 008 T008): the Contact page's custom layout (`page-contact.html`) relied
 * on WordPress's implicit `page-{slug}.html` template-hierarchy match instead of an explicit
 * `_wp_page_template` assignment. That worked for the English page (slug `contact`) but Polylang
 * appends `-2` to a translation's slug to avoid a global collision (`contact` -> `contact-2`), so the
 * AR page's slug never matched `page-contact.html` and silently fell back to the generic `page.html`
 * template — no error, just a completely unstyled page. `page-contact` is now registered in
 * `theme.json`'s `customTemplates` (matching how `legal.html` is already registered and assigned on
 * the terms/privacy pairs); this migrates any existing Contact-page post that doesn't yet have it
 * explicitly assigned, so environments seeded before this fix (staging/prod) self-heal on deploy. Run
 * with:
 *   wp eval 'require "sites/perego/perego-site/scripts/migrate-contact-page-template.php";' --path=wp
 *
 * @package PeregoSite
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

$migrated = 0;

foreach (get_posts(['post_type' => 'page', 'numberposts' => 100, 'post_status' => 'publish']) as $post) {
    if ($post->post_name !== 'contact' && $post->post_name !== 'contact-2') {
        continue;
    }

    if (get_post_meta($post->ID, '_wp_page_template', true) === 'page-contact') {
        continue; // Already migrated.
    }

    update_post_meta($post->ID, '_wp_page_template', 'page-contact');
    $migrated++;
}

WP_CLI::success("Contact page template retrofit — {$migrated} post(s) migrated (idempotent).");
