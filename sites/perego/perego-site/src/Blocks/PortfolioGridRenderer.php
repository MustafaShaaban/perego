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
    /** Cards shown per page (client-side numbered pagination, spec 020 owner review). */
    private const PER_PAGE = 9;

    /**
     * @param list<array{title: string, url: string, category: string, categoryLabel: string, excerpt: string, thumbUrl: string, thumbAlt: string}> $projects
     * @param array<string, string> $filterLabels ordered, keyed by slug ('all' first); values are labels
     * @param array{groupLabel: string, noResults: string, heading?: string, intro?: string, demoNote?: string, uiHome?: string, ctaTitle?: string, ctaBody?: string, ctaButton?: string} $strings
     * @param array{link?: array<string, mixed>, target?: string} $cta the closing CTA's resolved link (spec 021 T036)
     */
    public function render(array $projects, array $filterLabels, array $strings, array $cta = []): string
    {
        $html = '<section class="page-section">';
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
        $html .= '<p id="portfolioEmpty" hidden class="section-lead" role="status" '
            . 'style="font-size:var(--fs-lead);padding:clamp(40px,6vw,80px) 0;">'
            . esc_html($strings['noResults']) . '</p>';
        $html .= $this->renderPager(count($projects));
        $html .= '</div></section>';

        $html .= $this->renderCta($strings, $cta);

        return $html;
    }

    /**
     * The handoff's closing "Have a project in mind?" CTA (portfolio.html), missing from the archive
     * entirely until now — matches the same section-title/section-lead/btn contract
     * ServicesOverviewRenderer's own closing CTA uses.
     *
     * @param array{ctaTitle?: string, ctaBody?: string, ctaButton?: string} $strings
     * @param array{href?: string, target?: string} $cta resolved by LinkTarget; empty keeps the contact route
     */
    private function renderCta(array $strings, array $cta = []): string
    {
        if (empty($strings['ctaTitle'])) {
            return '';
        }

        $href = (string) ($cta['href'] ?? '') !== '' ? (string) $cta['href'] : (string) home_url('/contact');

        $html = '<section class="page-section" aria-labelledby="pfCta" style="border-top:1px solid rgba(255,255,255,0.08);">';
        $html .= '<div class="container" style="text-align:center;max-width:820px;">';
        $html .= '<h2 class="section-title" id="pfCta">' . esc_html($strings['ctaTitle']) . '</h2>';
        $html .= '<p class="section-lead" style="margin:16px auto 28px;">' . esc_html($strings['ctaBody'] ?? '') . '</p>';
        $html .= '<a class="btn btn--accent" href="' . esc_url($href) . '"' . (string) ($cta['target'] ?? '') . '>'
            . esc_html($strings['ctaButton'] ?? '') . '</a>';
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

            // view.js binds the click and toggles is-active/aria-pressed by reading data-filter.
            $html .= '<button type="button" class="web-filter portfolio-filter' . ($isAll ? ' is-active' : '') . '" '
                . 'data-filter="' . esc_attr($slug) . '" '
                . 'aria-pressed="' . ($isAll ? 'true' : 'false') . '">'
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
        $html = '<div class="blog-grid" id="portfolioGrid" data-per-page="' . esc_attr((string) self::PER_PAGE) . '">';

        foreach ($projects as $project) {
            $media = $project['thumbUrl'] !== ''
                ? '<img src="' . esc_url($project['thumbUrl']) . '" alt="' . esc_attr($project['thumbAlt']) . '" loading="lazy" />'
                : '<span class="post-card__media-placeholder" data-category="' . esc_attr($project['category']) . '" aria-hidden="true"></span>';

            // view.js filters/paginates by reading data-category and toggling the hidden attribute.
            $html .= '<a class="post-card reveal" href="' . esc_url($project['url']) . '" '
                . 'data-category="' . esc_attr($project['category']) . '">';
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
     * Numbered pagination (spec 020 owner review). Rendered up to the unfiltered max page count; view.js
     * hides page numbers beyond the filtered set's page count and hides the whole pager at a single page.
     * Purely client-side (no query/URL change) so it cooperates with the client-side service filter.
     */
    private function renderPager(int $projectCount): string
    {
        $maxPages = (int) max(1, (int) ceil($projectCount / self::PER_PAGE));

        if ($maxPages <= 1) {
            return '';
        }

        // Hidden until view.js runs, so no-JS clients see the full grid rather than a dead control.
        $html = '<nav class="pagination" hidden aria-label="' . esc_attr__('Projects pagination', 'perego-site') . '">';

        $html .= '<button type="button" class="pagination__prev" '
            . 'aria-label="' . esc_attr__('Previous page', 'perego-site') . '">&#8249;</button>';

        for ($page = 1; $page <= $maxPages; $page++) {
            $html .= '<button type="button" data-page="' . esc_attr((string) $page) . '" '
                . 'aria-label="' . esc_attr(sprintf(/* translators: %d: page number */ __('Page %d', 'perego-site'), $page)) . '">'
                . esc_html((string) $page) . '</button>';
        }

        $html .= '<button type="button" class="pagination__next" '
            . 'aria-label="' . esc_attr__('Next page', 'perego-site') . '">&#8250;</button>';

        $html .= '</nav>';

        return $html;
    }
}
