<?php

/** @package PeregoSite */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Content\GlobalContent;
use WP_Query;

/**
 * Renders the real WordPress search query with the locked handoff's presentation structure.
 * Results remain crawlable and editor-managed; only the public DOM contract is handoff-specific.
 */
final class SearchResultsRenderer
{
    private const PER_PAGE = 12;

    public function __construct(private readonly GlobalContent $content)
    {
    }

    public function render(): string
    {
        $copy = $this->content->search();
        $query = function_exists('get_search_query') ? (string) get_search_query() : '';

        $html = '<section class="page-section" aria-labelledby="search-title"><div class="container">';
        $html .= '<div class="post-hero__inner" style="text-align:center;">';
        $html .= $this->breadcrumb($copy);
        $html .= '<h1 class="post-title" id="search-title" style="font-size:clamp(30px,4vw,52px);margin-top:14px;">'
            . esc_html($copy['h1']) . '</h1>';

        if ($query === '') {
            $html .= '<p class="section-lead">' . esc_html($copy['emptyHint']) . '</p>';
            $html .= $this->searchForm($copy, $query);

            return $html . '</div></div></section>';
        }

        $paged = max(1, (int) (get_query_var('paged') ?: get_query_var('page') ?: 1));
        $queryArgs = [
            's' => $query,
            'posts_per_page' => self::PER_PAGE,
            'paged' => $paged,
            'no_found_rows' => false,
            'post_status' => 'publish',
            'ignore_sticky_posts' => true,
            // The handoff promises a search across editorial journal posts, services, and projects;
            // pages such as Home are navigation surfaces, not result cards.
            'post_type' => ['post', 'perego_service', 'perego_project'],
        ];

        if (function_exists('pll_current_language')) {
            $locale = pll_current_language('slug');
            if (is_string($locale) && $locale !== '') {
                $queryArgs['lang'] = $locale;
            }
        }

        $results = new WP_Query($queryArgs);

        $html .= '<p class="section-lead">'
            . esc_html($copy['resultsFor']) . ' <strong>' . esc_html($query) . '</strong> &mdash; '
            . esc_html((string) $results->found_posts) . ' ' . esc_html($copy['matchesFound']) . '</p>';
        $html .= $this->searchForm($copy, $query) . '</div>';

        if (! $results->have_posts()) {
            $html .= '<p class="section-lead" role="status">' . esc_html($copy['empty']) . '</p>';
            $html .= '<p class="section-lead">' . esc_html($copy['emptyHint']) . '</p>';

            return $html . '</div></section>';
        }

        $html .= '<div class="blog-grid" style="margin-top:clamp(32px,4vw,52px);">';
        foreach ($results->posts as $post) {
            $html .= $this->resultCard($post);
        }
        $html .= '</div>' . $this->pagination((int) $results->max_num_pages, $paged);

        wp_reset_postdata();

        return $html . '</div></section>';
    }

    private function resultCard(\WP_Post $post): string
    {
        $url = esc_url((string) get_permalink($post));
        $card = '<a class="post-card reveal" href="' . $url . '"><div class="post-card__media">';
        if (has_post_thumbnail($post)) {
            $card .= get_the_post_thumbnail($post, 'medium_large', ['alt' => '', 'loading' => 'lazy']);
        } else {
            $card .= '<span class="post-card__media-placeholder" aria-hidden="true"></span>';
        }
        $card .= '</div><div class="post-card__body">';

        $kicker = $this->kicker($post);
        if ($kicker !== '') {
            $card .= '<span class="post-card__cat">' . esc_html($kicker) . '</span>';
        }
        $card .= '<h3 class="post-card__title">' . esc_html((string) get_the_title($post)) . '</h3>';

        $excerpt = wp_strip_all_tags((string) get_the_excerpt($post));
        if ($excerpt !== '') {
            $card .= '<p class="post-card__excerpt">' . esc_html(wp_trim_words($excerpt, 24)) . '</p>';
        }

        $date = (string) get_the_date('', $post);
        if ($date !== '') {
            $card .= '<span class="post-card__meta">' . esc_html($date) . '</span>';
        }

        return $card . '</div></a>';
    }

    private function kicker(\WP_Post $post): string
    {
        $terms = get_the_category($post->ID);
        if (! empty($terms) && $terms[0] instanceof \WP_Term) {
            return $terms[0]->name;
        }

        $type = get_post_type_object((string) get_post_type($post));

        return $type !== null ? (string) $type->labels->singular_name : '';
    }

    private function pagination(int $totalPages, int $current): string
    {
        if ($totalPages < 2) {
            return '';
        }

        $html = '<nav class="pagination" aria-label="' . esc_attr__('Search results pages', 'perego-site') . '">';
        $html .= $current === 1
            ? '<span class="is-disabled">' . esc_html__('Prev', 'perego-site') . '</span>'
            : '<a href="' . esc_url(get_pagenum_link($current - 1)) . '">' . esc_html__('Prev', 'perego-site') . '</a>';

        for ($page = 1; $page <= $totalPages; $page++) {
            $html .= $page === $current
                ? '<span class="is-current">' . esc_html((string) $page) . '</span>'
                : '<a href="' . esc_url(get_pagenum_link($page)) . '">' . esc_html((string) $page) . '</a>';
        }

        $html .= $current === $totalPages
            ? '<span class="is-disabled">' . esc_html__('Next', 'perego-site') . '</span>'
            : '<a href="' . esc_url(get_pagenum_link($current + 1)) . '">' . esc_html__('Next', 'perego-site') . '</a>';

        return $html . '</nav>';
    }

    /** @param array<string, string> $copy */
    private function breadcrumb(array $copy): string
    {
        return '<nav class="page-crumb" style="justify-content:center;" aria-label="' . esc_attr__('Breadcrumb', 'perego-site') . '">'
            . '<a href="' . esc_url(home_url('/')) . '">' . esc_html($this->content->uiHome()) . '</a>'
            . '<span aria-hidden="true">/</span><span aria-current="page">' . esc_html($copy['h1']) . '</span></nav>';
    }

    /** @param array<string, string> $copy */
    private function searchForm(array $copy, string $query): string
    {
        return '<form class="search-bar" role="search" method="get" action="' . esc_url(home_url('/')) . '" style="margin:clamp(22px,3vw,34px) auto 0;">'
            . '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
            . '<label class="screen-reader-text" for="perego-search-field">' . esc_html($copy['h1']) . '</label>'
            . '<input type="search" id="perego-search-field" name="s" value="' . esc_attr($query) . '" placeholder="' . esc_attr($copy['placeholder']) . '" />'
            . '<button type="submit" class="btn btn--accent">' . esc_html($copy['button']) . '</button></form>';
    }
}
