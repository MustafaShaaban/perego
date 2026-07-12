<?php

/**
 * Fix a real structural bug found during single-post visual acceptance (spec 004 T016): every
 * journal post (EN + AR) has `post_author = 0`, which makes core's `wp:post-author-name` block
 * silently render nothing (render_block_core_post_author_name() early-returns on an empty author
 * ID) — the byline is missing on every single-post page, not a styling gap. Idempotent: only
 * updates posts that still have no author. Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/fix-journal-post-authors.php";' --path=wp
 *
 * @package PeregoSite
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

$admins = get_users(['role' => 'administrator', 'number' => 1, 'orderby' => 'ID', 'order' => 'ASC']);
if (empty($admins)) {
    WP_CLI::error('No administrator account found to assign as post author.');
}
$authorId = $admins[0]->ID;

$fixed = 0;
foreach (get_posts(['post_type' => 'post', 'numberposts' => 50, 'post_status' => 'publish']) as $post) {
    if ((int) $post->post_author !== 0) {
        continue;
    }

    wp_update_post(['ID' => $post->ID, 'post_author' => $authorId]);
    $fixed++;
}

WP_CLI::success("Journal post authors fixed — {$fixed} post(s) updated (idempotent).");
