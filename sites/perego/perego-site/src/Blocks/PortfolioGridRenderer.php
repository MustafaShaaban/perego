<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

/**
 * Server-renders the perego/portfolio-grid block (spec 003 / M3): the service-filter chip row + the
 * masonry grid of project cards + a no-results message. Ported from the handoff prototype's
 * `.portfolio-filters` + `.post-card` grid. The client-side filter (toggling card visibility by
 * category) is wired by view.js via the Interactivity API directives emitted here.
 *
 * `render()` takes already-resolved data (projects + the ordered filter labels) so it stays a pure,
 * unit-testable function; the block's render callback does the WP_Query and maps posts to the array.
 *
 * @phpstan-type Project array{title: string, url: string, category: string, categoryLabel: string, excerpt: string, thumbUrl: string, thumbAlt: string}
 */
final class PortfolioGridRenderer
{
    /**
     * @param list<array{title: string, url: string, category: string, categoryLabel: string, excerpt: string, thumbUrl: string, thumbAlt: string}> $projects
     * @param array<string, string> $filterLabels ordered, keyed by slug ('all' first); values are labels
     * @param array{groupLabel: string, noResults: string, heading?: string, intro?: string, demoNote?: string, uiHome?: string} $strings
     */
    public function render(array $projects, array $filterLabels, array $strings): string
    {
        $present = $this->presentCategories($projects);

        $context = esc_attr((string) wp_json_encode([
            'activeFilter' => 'all',
            'present' => $present,
        ]));

        $html = '<section class="portfolio" data-wp-interactive="perego/portfolio-grid" '
            . "data-wp-context='" . $context . "'>";
        $html .= '<div class="portfolio__inner">';

        if (! empty($strings['heading'])) {
            $html .= '<header class="portfolio__head">';
            if (! empty($strings['uiHome'])) {
                $html .= '<nav class="page-crumb" aria-label="' . esc_attr__('Breadcrumb', 'perego-site') . '">';
                $html .= '<a href="' . esc_url(home_url('/')) . '">' . esc_html($strings['uiHome']) . '</a>';
                $html .= '<span aria-hidden="true">/</span>';
                $html .= '<span aria-current="page">' . esc_html($strings['heading']) . '</span>';
                $html .= '</nav>';
            }
            $html .= '<h1 class="portfolio__title">' . esc_html($strings['heading']) . '</h1>';
            if (! empty($strings['intro'])) {
                $html .= '<p class="portfolio__intro">' . esc_html($strings['intro']) . '</p>';
            }
            if (! empty($strings['demoNote'])) {
                $html .= '<p class="portfolio__demo-note">' . esc_html($strings['demoNote']) . '</p>';
            }
            $html .= '</header>';
        }

        $html .= $this->renderFilters($filterLabels, $strings['groupLabel']);
        $html .= $this->renderGrid($projects);
        $html .= '<p class="portfolio__empty" role="status" data-wp-bind--hidden="callbacks.noResultsHidden">'
            . esc_html($strings['noResults']) . '</p>';
        $html .= '</div></section>';

        return $html;
    }

    /**
     * @param array<string, string> $filterLabels
     */
    private function renderFilters(array $filterLabels, string $groupLabel): string
    {
        $html = '<div class="portfolio-filters" role="group" aria-label="' . esc_attr($groupLabel) . '">';

        foreach ($filterLabels as $slug => $label) {
            $isAll = $slug === 'all';

            $html .= '<button type="button" class="portfolio-filter' . ($isAll ? ' is-active' : '') . '" '
                . 'data-filter="' . esc_attr($slug) . '" '
                . 'aria-pressed="' . ($isAll ? 'true' : 'false') . '" '
                . "data-wp-context='" . esc_attr((string) wp_json_encode(['filter' => $slug])) . "' "
                . 'data-wp-on--click="actions.setFilter" '
                . 'data-wp-bind--aria-pressed="callbacks.filterPressed" '
                . 'data-wp-class--is-active="callbacks.filterPressed">'
                . esc_html($label) . '</button>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * @param list<array{title: string, url: string, category: string, categoryLabel: string, excerpt: string, thumbUrl: string, thumbAlt: string}> $projects
     */
    private function renderGrid(array $projects): string
    {
        $html = '<div class="post-cards portfolio-grid">';

        foreach ($projects as $project) {
            $media = $project['thumbUrl'] !== ''
                ? '<img src="' . esc_url($project['thumbUrl']) . '" alt="' . esc_attr($project['thumbAlt']) . '" loading="lazy" />'
                : '<span class="post-card__media-placeholder" data-category="' . esc_attr($project['category']) . '" aria-hidden="true"></span>';

            $html .= '<a class="post-card" href="' . esc_url($project['url']) . '" '
                . 'data-category="' . esc_attr($project['category']) . '" '
                . "data-wp-context='" . esc_attr((string) wp_json_encode(['category' => $project['category']])) . "' "
                . 'data-wp-bind--hidden="callbacks.cardHidden">';
            $html .= '<div class="post-card__media">' . $media . '</div>';
            $html .= '<div class="post-card__body">';
            $html .= '<span class="post-card__cat">' . esc_html($project['categoryLabel']) . '</span>';
            $html .= '<h2 class="post-card__title">' . esc_html($project['title']) . '</h2>';
            $html .= '<p class="post-card__excerpt">' . esc_html($project['excerpt']) . '</p>';
            $html .= '</div></a>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * The distinct category slugs that actually have at least one project — view.js uses this to
     * decide when to show the no-results message.
     *
     * @param list<array{category: string}> $projects
     * @return list<string>
     */
    private function presentCategories(array $projects): array
    {
        return array_values(array_unique(array_column($projects, 'category')));
    }
}
