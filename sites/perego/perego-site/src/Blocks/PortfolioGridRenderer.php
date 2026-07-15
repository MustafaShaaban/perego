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
     * @param array{groupLabel: string, noResults: string, heading?: string, intro?: string, demoNote?: string, uiHome?: string, ctaTitle?: string, ctaBody?: string, ctaButton?: string} $strings
     */
    public function render(array $projects, array $filterLabels, array $strings): string
    {
        $present = $this->presentCategories($projects);

        $context = esc_attr((string) wp_json_encode([
            'activeFilter' => 'all',
            'present' => $present,
        ]));

        $html = '<section class="page-section" data-wp-interactive="perego/portfolio-grid" '
            . "data-wp-context='" . $context . "'>";
        $html .= '<div class="container">';

        if (! empty($strings['heading'])) {
            $html .= '<div class="post-hero__inner" style="text-align:center;">';
            if (! empty($strings['uiHome'])) {
                $html .= '<nav class="page-crumb" style="justify-content:center;" aria-label="' . esc_attr__('Breadcrumb', 'perego-site') . '">';
                $html .= '<a href="' . esc_url(home_url('/')) . '">' . esc_html($strings['uiHome']) . '</a>';
                $html .= '<span aria-hidden="true">/</span>';
                $html .= '<span aria-current="page">' . esc_html($strings['heading']) . '</span>';
                $html .= '</nav>';
            }
            $html .= '<h1 class="post-title" style="font-size:clamp(34px,4.5vw,60px);margin-top:14px;">' . esc_html($strings['heading']) . '</h1>';
            if (! empty($strings['intro'])) {
                $html .= '<p class="section-lead">' . esc_html($strings['intro']) . '</p>';
            }
            if (! empty($strings['demoNote'])) {
                $html .= '<p class="section-lead" style="font-size:14px;color:var(--muted-2);margin-top:6px;">' . esc_html($strings['demoNote']) . '</p>';
            }
            $html .= '</div>';
        }

        $html .= $this->renderFilters($filterLabels, $strings['groupLabel']);
        $html .= $this->renderGrid($projects);
        $html .= '<p id="portfolioEmpty" hidden class="section-lead" role="status" data-wp-bind--hidden="callbacks.noResultsHidden" '
            . 'style="font-size:var(--fs-lead);padding:clamp(40px,6vw,80px) 0;">'
            . esc_html($strings['noResults']) . '</p>';
        $html .= '</div></section>';

        $html .= $this->renderCta($strings);

        return $html;
    }

    /**
     * The handoff's closing "Have a project in mind?" CTA (portfolio.html), missing from the archive
     * entirely until now — matches the same section-title/section-lead/btn contract
     * ServicesOverviewRenderer's own closing CTA uses.
     *
     * @param array{ctaTitle?: string, ctaBody?: string, ctaButton?: string} $strings
     */
    private function renderCta(array $strings): string
    {
        if (empty($strings['ctaTitle'])) {
            return '';
        }

        $html = '<section class="page-section" aria-labelledby="pfCta" style="border-top:1px solid rgba(255,255,255,0.08);">';
        $html .= '<div class="container" style="text-align:center;max-width:820px;">';
        $html .= '<h2 class="section-title" id="pfCta">' . esc_html($strings['ctaTitle']) . '</h2>';
        $html .= '<p class="section-lead" style="margin:16px auto 28px;">' . esc_html($strings['ctaBody'] ?? '') . '</p>';
        $html .= '<a class="btn btn--accent" href="' . esc_url(home_url('/contact')) . '">' . esc_html($strings['ctaButton'] ?? '') . '</a>';
        $html .= '</div></section>';

        return $html;
    }

    /**
     * @param array<string, string> $filterLabels
     */
    private function renderFilters(array $filterLabels, string $groupLabel): string
    {
        $html = '<div class="portfolio-filters" role="group" aria-label="' . esc_attr($groupLabel) . '" '
            . 'style="display:flex;flex-wrap:wrap;gap:12px;justify-content:center;margin:clamp(24px,3vw,40px) 0;">';

        foreach ($filterLabels as $slug => $label) {
            $isAll = $slug === 'all';

            $html .= '<button type="button" class="web-filter portfolio-filter' . ($isAll ? ' is-active' : '') . '" '
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
        $html = '<div class="blog-grid" id="portfolioGrid">';

        foreach ($projects as $project) {
            $media = $project['thumbUrl'] !== ''
                ? '<img src="' . esc_url($project['thumbUrl']) . '" alt="' . esc_attr($project['thumbAlt']) . '" loading="lazy" />'
                : '<span class="post-card__media-placeholder" data-category="' . esc_attr($project['category']) . '" aria-hidden="true"></span>';

            $html .= '<a class="post-card reveal" href="' . esc_url($project['url']) . '" '
                . 'data-category="' . esc_attr($project['category']) . '" '
                . "data-wp-context='" . esc_attr((string) wp_json_encode(['category' => $project['category']])) . "' "
                . 'data-wp-bind--hidden="callbacks.cardHidden">';
            $html .= '<div class="post-card__media">' . $media . '</div>';
            $html .= '<div class="post-card__body">';
            $html .= '<span class="post-card__cat">' . esc_html($project['categoryLabel']) . '</span>';
            $html .= '<h2 class="post-card__title" style="font-size:clamp(18px,1.6vw,22px);">' . esc_html($project['title']) . '</h2>';
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
