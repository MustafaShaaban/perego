<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Content;

defined('ABSPATH') || exit;

use WP_Post;

/**
 * Applies an editor's manual project order to a set of posts (owner, 2026-07-28).
 *
 * WHY AN ID LIST RATHER THAN `menu_order`. The order is stored per block instance, because home and
 * `/work` render the same `portfolio-grid` block and now show genuinely different sets — one leads
 * with a featured shortlist, the other lists the portfolio — so a single global position cannot
 * express both. `menu_order` also already has a job: it carries the client's deliberate 29-site
 * website sequence written by `scripts/import-portfolio-logos.php`, which a canvas drag would
 * overwrite. It stays the *fallback* order; this is the override laid on top.
 *
 * TWO INDEXES, NOT ONE. A saved order is a list of post ids, and which ids depends on where it was
 * authored: the grids live in language-neutral FSE templates so their order is authored against the
 * English posts, while each Service post owns its own block and an Arabic service saves Arabic ids.
 * Resolving by the canonical English id *and* by the raw id means one implementation serves both, and
 * degrades correctly to raw ids when Polylang is inactive (constitution IX).
 *
 * WHAT AN UNKNOWN POST DOES. Anything the order does not name is appended, keeping the order the query
 * returned it in — never promoted to the front. Publishing a project must not silently disturb an
 * arrangement someone made deliberately; the editor is told the newcomers are waiting at the end.
 */
final class ProjectOrder
{
    /**
     * Reorder `$posts` to match `$order`, appending anything unnamed.
     *
     * An empty `$order` means "no manual order" and returns the posts untouched, which is what makes
     * adding this to an existing block a no-op until someone actually drags something.
     *
     * @param list<WP_Post> $posts
     * @param list<int>     $order post ids, in the order they should render
     * @return list<WP_Post>
     */
    public static function apply(array $posts, array $order): array
    {
        if ($order === [] || $posts === []) {
            return $posts;
        }

        $byId = [];
        foreach ($posts as $post) {
            // The raw id first so it always wins for the post it names, then the English id as an
            // alias. Both point at the same post, so an order authored in either language resolves.
            $byId[(int) $post->ID] = $post;

            $englishId = TranslatedMeta::englishId($post);
            if ($englishId !== 0 && ! isset($byId[$englishId])) {
                $byId[$englishId] = $post;
            }
        }

        $ordered = [];
        $taken = [];

        foreach ($order as $id) {
            $id = (int) $id;
            $post = $byId[$id] ?? null;

            // An id that no longer resolves — unpublished, deleted, filtered out by `featuredOnly` —
            // is simply skipped. A stale entry must not leave a hole or an empty tile.
            if ($post === null || isset($taken[(int) $post->ID])) {
                continue;
            }

            $ordered[] = $post;
            $taken[(int) $post->ID] = true;
        }

        foreach ($posts as $post) {
            if (! isset($taken[(int) $post->ID])) {
                $ordered[] = $post;
            }
        }

        return $ordered;
    }

    /**
     * How many of `$posts` the order does not yet name — what the editor is told is waiting at the end.
     *
     * @param list<WP_Post> $posts
     * @param list<int>     $order
     */
    public static function unplacedCount(array $posts, array $order): int
    {
        if ($order === []) {
            return 0;
        }

        $named = [];
        foreach ($order as $id) {
            $named[(int) $id] = true;
        }

        $unplaced = 0;
        foreach ($posts as $post) {
            $englishId = TranslatedMeta::englishId($post);

            if (! isset($named[(int) $post->ID]) && ! ($englishId !== 0 && isset($named[$englishId]))) {
                $unplaced++;
            }
        }

        return $unplaced;
    }
}
