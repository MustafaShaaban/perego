<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Content\GlobalContent;
use WP_Query;

/**
 * Server-renders the perego-theme/search-results block (M4): the search page — the "Search results"
 * H1, a "Showing results for <q> — N matches found" line, a search form, the result list (title link
 * + excerpt), and the empty state. Language-aware via GlobalContent. Real WordPress search, bounded
 * query, results server-rendered (crawlable, works without JS). Ported from the handoff `search.html`.
 */
final class SearchResultsRenderer
{
    private const PER_PAGE = 20;

    public function __construct(private readonly GlobalContent $content)
    {
    }

    public function render(): string
    {
        $s = $this->content->search();
        $query = function_exists('get_search_query') ? (string) get_search_query() : '';

        $html = '<section class="search-page" aria-labelledby="search-title">';
        $html .= '<h1 class="search-page__title" id="search-title">' . esc_html($s['h1']) . '</h1>';

        $html .= $this->searchForm($s, $query);

        if ($query === '') {
            $html .= '<p class="search-page__hint">' . esc_html($s['emptyHint']) . '</p>';

            return $html . '</section>';
        }

        $results = new WP_Query([
            's' => $query,
            'posts_per_page' => self::PER_PAGE,
            'no_found_rows' => false,
            'post_status' => 'publish',
            'ignore_sticky_posts' => true,
        ]);

        $count = (int) $results->found_posts;
        $html .= '<p class="search-page__summary">'
            . esc_html($s['resultsFor']) . ' <strong>' . esc_html($query) . '</strong> — '
            . esc_html((string) $count) . ' ' . esc_html($s['matchesFound']) . '</p>';

        if (! $results->have_posts()) {
            $html .= '<p class="search-page__empty" role="status">' . esc_html($s['empty']) . '</p>';
            $html .= '<p class="search-page__hint">' . esc_html($s['emptyHint']) . '</p>';

            return $html . '</section>';
        }

        $html .= '<ul class="search-page__list">';
        foreach ($results->posts as $post) {
            $html .= '<li class="search-result">';
            $html .= '<a class="search-result__title" href="' . esc_url((string) get_permalink($post)) . '">'
                . esc_html((string) get_the_title($post)) . '</a>';
            $excerpt = wp_strip_all_tags((string) get_the_excerpt($post));
            if ($excerpt !== '') {
                $html .= '<p class="search-result__excerpt">' . esc_html(wp_trim_words($excerpt, 30)) . '</p>';
            }
            $html .= '</li>';
        }
        $html .= '</ul>';

        wp_reset_postdata();

        return $html . '</section>';
    }

    /**
     * @param array<string, string> $s
     */
    private function searchForm(array $s, string $query): string
    {
        return '<form class="search-page__form" role="search" method="get" action="' . esc_url(home_url('/')) . '">'
            . '<label class="screen-reader-text" for="perego-search-field">' . esc_html($s['h1']) . '</label>'
            . '<input type="search" id="perego-search-field" name="s" value="' . esc_attr($query) . '" '
            . 'placeholder="' . esc_attr($s['placeholder']) . '" />'
            . '<button type="submit" class="btn btn--accent">' . esc_html($s['button']) . '</button>'
            . '</form>';
    }
}
