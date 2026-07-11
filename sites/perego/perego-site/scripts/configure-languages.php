<?php

/**
 * Configure the two Polylang **Free** languages for Perego — English (default, LTR) and Arabic
 * (RTL). Idempotent: adds a language only if its slug is not already registered, and never changes
 * an existing language. Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/configure-languages.php";' --path=wp
 *
 * Polylang Free ships no WP-CLI command, so this uses the public model API
 * (`PLL()->model->languages->add()`, the 3.7+ location of the former PLL_Admin_Model::add_language).
 * Fails clearly if Polylang is not active — the constitution keeps Polylang optional, so this is a
 * setup step, not a runtime dependency. See docs/multilingual-guide.md.
 *
 * @package PeregoSite
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

if (! function_exists('PLL') || ! function_exists('pll_languages_list')) {
    WP_CLI::error('Polylang is not active — cannot configure languages.');
}

$model = PLL()->model;

if (! isset($model->languages) || ! method_exists($model->languages, 'add')) {
    WP_CLI::error('This Polylang version does not expose PLL()->model->languages->add().');
}

/** @var list<array{locale: string, name: string, slug: string, rtl: bool, flag: string, term_group: int}> $languages */
$languages = [
    ['locale' => 'en_US', 'name' => 'English', 'slug' => 'en', 'rtl' => false, 'flag' => 'us', 'term_group' => 0],
    ['locale' => 'ar', 'name' => 'العربية', 'slug' => 'ar', 'rtl' => true, 'flag' => 'sa', 'term_group' => 1],
];

$existing = pll_languages_list(); // slugs
$added = 0;

foreach ($languages as $lang) {
    if (in_array($lang['slug'], $existing, true)) {
        WP_CLI::log("Language already present: {$lang['slug']}");
        continue;
    }

    $result = $model->languages->add($lang);

    if (is_wp_error($result)) {
        WP_CLI::warning("Failed to add {$lang['slug']}: " . $result->get_error_message());
        continue;
    }

    $added++;
    WP_CLI::log("Added language: {$lang['slug']} ({$lang['locale']})");
}

// Ensure English is the default (Polylang stores this in its option).
if (function_exists('pll_default_language') && pll_default_language() !== 'en') {
    $options = get_option('polylang');
    if (is_array($options)) {
        $options['default_lang'] = 'en';
        update_option('polylang', $options);
        WP_CLI::log('Set default language: en');
    }
}

// Backfill: assign the default language (en) to any pre-existing content that has none — the same
// operation Polylang's admin offers after the first languages are created. Idempotent: only touches
// posts/terms that currently have no language. Later AR translations are linked by the seeders.
if (function_exists('pll_set_post_language') && function_exists('pll_get_post_language')) {
    $postTypes = ['perego_service', 'perego_project', 'page', 'post'];
    $assignedPosts = 0;

    foreach ($postTypes as $postType) {
        $ids = get_posts([
            'post_type' => $postType,
            'numberposts' => -1,
            'post_status' => 'any',
            'fields' => 'ids',
        ]);

        foreach ($ids as $id) {
            if (! pll_get_post_language($id)) {
                pll_set_post_language($id, 'en');
                $assignedPosts++;
            }
        }
    }

    WP_CLI::log("Backfilled default language on {$assignedPosts} post(s).");
}

if (function_exists('pll_set_term_language') && function_exists('pll_get_term_language')) {
    $assignedTerms = 0;
    $terms = get_terms(['taxonomy' => 'perego_project_category', 'hide_empty' => false]);

    if (is_array($terms)) {
        foreach ($terms as $term) {
            if (! pll_get_term_language($term->term_id)) {
                pll_set_term_language($term->term_id, 'en');
                $assignedTerms++;
            }
        }
    }

    WP_CLI::log("Backfilled default language on {$assignedTerms} term(s).");
}

WP_CLI::success("Language configuration complete; added {$added} new language(s).");
