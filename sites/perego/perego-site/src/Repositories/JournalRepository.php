<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Repositories;

defined('ABSPATH') || exit;

use WP_Post;
use WP_Query;

/**
 * Reads native journal posts for the single-post "Related articles" section (spec 020 round 6,
 * handoff single-post.html:150-181). Same-category first, back-filled with other current-language
 * posts when the category is thin — the same fill discipline ProjectRepository::relatedFor() uses
 * for project singles, applied to `post` + `category`.
 */
final class JournalRepository
{
    /**
     * @return list<WP_Post>
     */
    public function relatedFor(WP_Post $post, int $limit = 3): array
    {
        $args = $this->localeQueryArgs($post) + [
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'post__not_in' => [$post->ID],
            'no_found_rows' => true,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $terms = get_the_terms($post->ID, 'category');
        if (is_array($terms) && $terms !== []) {
            $args['category__in'] = [(int) $terms[0]->term_id];
        }

        $related = (new WP_Query($args))->posts;
        if (count($related) >= $limit) {
            return $related;
        }

        // Thin category: keep the three-card layout by filling with other current-language posts,
        // never duplicating the current post or an already selected one.
        unset($args['category__in']);
        $args['post__not_in'] = array_merge([$post->ID], array_map(static fn (WP_Post $item): int => $item->ID, $related));
        $args['posts_per_page'] = $limit - count($related);

        return array_merge($related, (new WP_Query($args))->posts);
    }

    /** @return array<string, string> */
    private function localeQueryArgs(WP_Post $post): array
    {
        if (! function_exists('pll_get_post_language')) {
            return [];
        }

        $language = pll_get_post_language($post->ID, 'slug');

        return is_string($language) && $language !== '' ? ['lang' => $language] : [];
    }
}
