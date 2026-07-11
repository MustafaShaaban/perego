<?php

/**
 * Seed the Journal (native WordPress posts) + the static-front-page / posts-page wiring so the blog
 * index lives at /journal (spec M5). Idempotent — creates missing items only, never overwrites later
 * editor changes. Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/seed-journal.php";' --path=wp
 *
 * Posts are **PLACEHOLDER demo** content (titles/bodies clearly marked "example") — replace with real
 * articles before launch. The homepage keeps rendering front-page.html (the Home page is an empty
 * shell the block template fills). English is seeded; AR journal content ships with real articles.
 *
 * @package PeregoSite
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

$pllReady = function_exists('pll_set_post_language') && function_exists('pll_get_post_language')
    && in_array('en', function_exists('pll_languages_list') ? pll_languages_list() : [], true);

$setLang = static function (int $id) use ($pllReady): void {
    if ($pllReady && ! pll_get_post_language($id)) {
        pll_set_post_language($id, 'en');
    }
};

/** Ensure a page exists by slug; return its ID. */
$ensurePage = static function (string $slug, string $title) use ($setLang): int {
    $found = get_posts(['post_type' => 'page', 'name' => $slug, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids']);
    if ($found !== []) {
        $setLang((int) $found[0]);

        return (int) $found[0];
    }

    $id = wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => $title,
        'post_name' => $slug,
        'post_content' => '',
    ], true);

    if (is_wp_error($id)) {
        WP_CLI::warning("Page {$slug}: " . $id->get_error_message());

        return 0;
    }

    $setLang((int) $id);
    WP_CLI::log("Created page: {$slug}");

    return (int) $id;
};

// Static front page (front-page.html renders it) + a Journal posts page → /journal.
$homeId = $ensurePage('home', 'Home');
$journalId = $ensurePage('journal', 'Journal');

if ($homeId !== 0 && $journalId !== 0) {
    if (get_option('show_on_front') !== 'page') {
        update_option('show_on_front', 'page');
    }
    if ((int) get_option('page_on_front') !== $homeId) {
        update_option('page_on_front', $homeId);
    }
    if ((int) get_option('page_for_posts') !== $journalId) {
        update_option('page_for_posts', $journalId);
    }
    WP_CLI::log('Wired static front page + /journal posts page.');
}

// Journal categories.
$categories = ['studio-notes' => 'Studio Notes', 'craft' => 'Craft', 'behind-the-scenes' => 'Behind the Scenes'];
foreach ($categories as $slug => $name) {
    if (! term_exists($slug, 'category')) {
        $term = wp_insert_term($name, 'category', ['slug' => $slug]);
        if (! is_wp_error($term) && $pllReady && function_exists('pll_set_term_language')) {
            pll_set_term_language((int) $term['term_id'], 'en');
        }
    }
}

/** @var list<array{title: string, cat: string, excerpt: string}> $posts */
$posts = [
    ['title' => 'How we storyboard a motion piece (example)', 'cat' => 'craft', 'excerpt' => 'Example article — replace with a real studio post.'],
    ['title' => 'Colour grading notes from the edit bay (example)', 'cat' => 'studio-notes', 'excerpt' => 'Example article — replace with a real studio post.'],
    ['title' => 'Behind the scenes of a brand film (example)', 'cat' => 'behind-the-scenes', 'excerpt' => 'Example article — replace with a real studio post.'],
];

$body = '<!-- wp:paragraph --><p><em>Example journal article — replace with a real Perego post. No business claims or metrics are asserted here.</em></p><!-- /wp:paragraph -->' . "\n\n"
    . '<!-- wp:heading --><h2 class="wp-block-heading">A section heading</h2><!-- /wp:heading -->' . "\n"
    . '<!-- wp:paragraph --><p>Placeholder body copy for the article. Editable on the canvas.</p><!-- /wp:paragraph -->';

$created = 0;

foreach ($posts as $post) {
    $slug = sanitize_title($post['title']);
    $found = get_posts(['post_type' => 'post', 'name' => $slug, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids']);
    if ($found !== []) {
        continue;
    }

    $id = wp_insert_post([
        'post_type' => 'post',
        'post_status' => 'publish',
        'post_title' => $post['title'],
        'post_name' => $slug,
        'post_excerpt' => $post['excerpt'],
        'post_content' => $body,
    ], true);

    if (is_wp_error($id)) {
        WP_CLI::warning("Post {$slug}: " . $id->get_error_message());
        continue;
    }

    $id = (int) $id;
    $term = get_term_by('slug', $post['cat'], 'category');
    if ($term) {
        wp_set_object_terms($id, [(int) $term->term_id], 'category');
    }
    $setLang($id);
    $created++;
    WP_CLI::log("Created post: {$slug}");
}

WP_CLI::success("Journal seeded — {$created} new post(s); " . count($posts) . ' defined.');
