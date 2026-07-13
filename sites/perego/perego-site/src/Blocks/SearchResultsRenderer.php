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
 * Server-renders the perego-theme/search-results block (M4): the search page — a breadcrumb, the
 * "Search results" H1, a "Showing results for <q> — N matches found" line, a search form, the result
 * cards (featured image / placeholder, kicker tag, title, excerpt, date) in a grid, pagination, and
 * the empty state. Language-aware via GlobalContent. Real WordPress search, bounded query, results
 * server-rendered (crawlable, works without JS). Ported from the handoff `search.html`.
 */
final class SearchResultsRenderer
{
    private const PER_PAGE = 12;

    public function __construct(private readonly GlobalContent $content)
    {
    }

    public function render(): string
    {
        $s = $this->content->search();
        $query = function_exists('get_search_query') ? (string) get_search_query() : '';

        $html = '<section class="search-page" aria-labelledby="search-title">';
        $html .= $this->breadcrumb($s);
        $html .= '<h1 class="search-page__title" id="search-title">' . esc_html($s['h1']) . '</h1>';

        $html .= $this->searchForm($s, $query);

        if ($query === '') {
            $html .= '<p class="search-page__hint">' . esc_html($s['emptyHint']) . '</p>';

            return $html . '</section>';
        }

        $paged = max(1, (int) (get_query_var('paged') ?: get_query_var('page') ?: 1));
        $results = new WP_Query([
            's' => $query,
            'posts_per_page' => self::PER_PAGE,
            'paged' => $paged,
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

        $html .= '<div class="post-cards blog-grid search-page__results">';
        foreach ($results->posts as $post) {
            $html .= $this->resultCard($post);
        }
        $html .= '</div>';

        $html .= $this->pagination((int) $results->max_num_pages, $paged);

        wp_reset_postdata();

        return $html . '</section>';
    }

    /** One result rendered with the shared `.post-card` treatment used by the work/journal archives. */
    private function resultCard(\WP_Post $post): string
    {
        $url = esc_url((string) get_permalink($post));

        $card = '<article class="post-card">';
        $card .= '<a class="post-card__media" href="' . $url . '" tabindex="-1" aria-hidden="true">';
        if (has_post_thumbnail($post)) {
            $card .= get_the_post_thumbnail($post, 'medium_large', ['alt' => '', 'loading' => 'lazy']);
        } else {
            $card .= '<span class="post-card__media-placeholder" aria-hidden="true"></span>';
        }
        $card .= '</a>';

        $card .= '<div class="post-card__body">';
        $kicker = $this->kicker($post);
        if ($kicker !== '') {
            $card .= '<p class="post-card__cat">' . esc_html($kicker) . '</p>';
        }
        $card .= '<h2 class="post-card__title"><a href="' . $url . '">'
            . esc_html((string) get_the_title($post)) . '</a></h2>';

        $excerpt = wp_strip_all_tags((string) get_the_excerpt($post));
        if ($excerpt !== '') {
            $card .= '<p class="post-card__excerpt">' . esc_html(wp_trim_words($excerpt, 24)) . '</p>';
        }

        $date = (string) get_the_date('', $post);
        if ($date !== '') {
            $card .= '<p class="post-card__meta">' . esc_html($date) . '</p>';
        }
        $card .= '</div></article>';

        return $card;
    }

    /**
     * Short kicker tag for a result: the post's first category term for posts, otherwise the post
     * type's singular label (Journal / Service / Project / Page) — the handoff shows a short kicker
     * above each result title.
     */
    private function kicker(\WP_Post $post): string
    {
        $terms = get_the_category($post->ID);
        if (! empty($terms) && $terms[0] instanceof \WP_Term) {
            return $terms[0]->name;
        }

        $obj = get_post_type_object((string) get_post_type($post));

        return $obj !== null ? (string) $obj->labels->singular_name : '';
    }

    /** Prev / numbered / Next pagination, rendered only when there is more than one page. */
    private function pagination(int $totalPages, int $current): string
    {
        if ($totalPages < 2) {
            return '';
        }

        $links = paginate_links([
            'total' => $totalPages,
            'current' => $current,
            'type' => 'array',
            'prev_text' => esc_html__('Prev', 'perego-site'),
            'next_text' => esc_html__('Next', 'perego-site'),
        ]);

        if (empty($links) || ! is_array($links)) {
            return '';
        }

        return '<nav class="search-page__pagination" aria-label="'
            . esc_attr__('Search results pages', 'perego-site') . '">'
            . implode('', $links) . '</nav>';
    }

    /** @param array<string, string> $s */
    private function breadcrumb(array $s): string
    {
        return '<nav class="page-crumb" aria-label="' . esc_attr__('Breadcrumb', 'perego-site') . '">'
            . '<a href="' . esc_url(home_url('/')) . '">' . esc_html($this->content->uiHome()) . '</a>'
            . '<span aria-hidden="true">/</span>'
            . '<span aria-current="page">' . esc_html($s['h1']) . '</span>'
            . '</nav>';
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
            . '<button type="submit" class="perego-btn perego-btn--accent">' . esc_html($s['button']) . '</button>'
            . '</form>';
    }
}
