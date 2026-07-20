<?php

/**
 * Seed the handoff's demo comments (single-post.html:110-136) on the journal posts so the
 * perego-theme/journal-comments block renders the design's "3 Comments" card list — including the
 * one indented reply. Idempotent: only seeds a post that has NO comments yet, so re-runs and later
 * real comments are never disturbed. Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/seed-journal-comments.php";' --path=wp
 *
 * Comments are clearly-demo placeholder content (matching the other journal seeders); replace with
 * real discussion before launch. English posts only (the handoff comments are English copy); the AR
 * posts are left without demo comments rather than machine-translating a conversation.
 *
 * @package PeregoSite
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

$pllReady = function_exists('pll_get_post_language');

// The handoff's three comments; the second is a reply to the first (indented `.comment--reply`).
$demo = [
    [
        'author' => 'Sara Adel',
        'email' => 'sara.adel@example.com',
        'date' => '2026-07-09 10:15:00',
        'content' => 'This lines up exactly with what we saw after adding a loader animation — bounce dropped and people remembered the brand name. Great read.',
        'reply' => false,
    ],
    [
        'author' => 'Mostafa Emam',
        'email' => 'mostafa.emam@example.com',
        'date' => '2026-07-09 12:40:00',
        'content' => 'Thanks Sara! The loader is almost always the highest-leverage first moment. Happy to share the timing curves we used.',
        'reply' => true, // reply to the first comment
    ],
    [
        'author' => 'Karim Hassan',
        'email' => 'karim.hassan@example.com',
        'date' => '2026-07-08 09:05:00',
        'content' => 'The “survive a mute button” test is such a useful filter. Stealing that for our next review.',
        'reply' => false,
    ],
];

$seeded = 0;
foreach (get_posts(['post_type' => 'post', 'numberposts' => 200, 'post_status' => 'publish', 'lang' => '']) as $post) {
    // English posts only.
    if ($pllReady && pll_get_post_language($post->ID, 'slug') === 'ar') {
        continue;
    }

    // Idempotent: never add to a post that already has comments (demo or real).
    if ((int) get_comments_number($post->ID) > 0) {
        continue;
    }

    $parentId = 0;
    foreach ($demo as $comment) {
        $data = [
            'comment_post_ID' => $post->ID,
            'comment_author' => $comment['author'],
            'comment_author_email' => $comment['email'],
            'comment_content' => $comment['content'],
            'comment_date' => $comment['date'],
            'comment_approved' => 1,
            'comment_type' => 'comment',
            'comment_parent' => $comment['reply'] ? $parentId : 0,
        ];

        $id = wp_insert_comment($data);
        if ($id === false) {
            WP_CLI::warning("Post {$post->ID}: could not insert comment by {$comment['author']}.");
            continue;
        }

        // The first (non-reply) comment is the parent the reply threads under.
        if (! $comment['reply']) {
            $parentId = (int) $id;
        }
    }

    $seeded++;
}

WP_CLI::success("Journal demo comments seeded — {$seeded} post(s) seeded (idempotent).");
