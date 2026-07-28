<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Theme\SiteRoutes;

/**
 * Server-renders the perego/portfolio-grid block (spec 003 / M3): the service-filter chip row + the
 * masonry grid of project cards + a no-results message. Ported from the handoff prototype's
 * `.portfolio-filters` + `.post-card` grid. The client-side filter (toggling card visibility by
 * category) is wired by view.js via the Interactivity API directives emitted here.
 *
 * `render()` takes already-resolved data (projects + the ordered filter labels) so it stays a pure,
 * unit-testable function; the block's render callback does the WP_Query and maps posts to the array.
 *
 * @phpstan-type Project array{id: int, icon: string, title: string, url: string, category: string, categoryLabel: string, excerpt: string, thumbUrl: string, thumbAlt: string, gallerySrcs: list<string>, videoUrl: string, logoUrl: string, logoAlt: string, siteUrl: string, role: string}
 */
final class PortfolioGridRenderer
{
    /** Cards shown per page (client-side numbered pagination, spec 020 owner review). */
    private const PER_PAGE = 9;

    /**
     * @param list<Project> $projects
     * @param array<string, string> $filterLabels ordered, keyed by slug ('all' first); values are labels
     * @param array{groupLabel: string, noResults: string, heading?: string, intro?: string, demoNote?: string, uiHome?: string, ctaTitle?: string, ctaBody?: string, ctaButton?: string, galleryBadge?: string} $strings
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
        $html .= $this->renderGrid($projects, (string) ($strings['galleryBadge'] ?? __('Gallery', 'perego-site')));
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

        $href = (string) ($cta['href'] ?? '') !== '' ? (string) $cta['href'] : (string) home_url(SiteRoutes::START_PROJECT);

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
     * @param list<Project> $projects
     */
    private function renderGrid(array $projects, string $galleryBadge): string
    {
        $html = '<div class="blog-grid" id="portfolioGrid" data-per-page="' . esc_attr((string) self::PER_PAGE) . '">';

        foreach ($projects as $project) {
            $html .= $this->renderCard($project, $galleryBadge);
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * One project card.
     *
     * Two shapes. A web-category project carrying a logo is the exception (client request 2026-07-27):
     * it shows the client's brand mark instead of a cover-cropped screenshot, and goes to the live site
     * instead of opening the lightbox — so it renders as an <a>, or as an inert <article> when no live
     * URL is recorded, because a control that does nothing on click should not be focusable.
     *
     * Everything else keeps the <button> lightbox trigger from the 2026-07-26 request: a card opens the
     * project's media in the shared dialog rather than navigating to a single page. Either way view.js
     * filters and paginates by reading `data-category` off this element and toggling `hidden`, which is
     * why the class and that attribute stay on the wrapper whatever the tag.
     *
     * @param Project $project
     */
    private function renderCard(array $project, string $galleryBadge): string
    {
        // Every web project is a logo card, whether or not a logo has been supplied. It used to
        // depend on `logoUrl`, so a site awaiting its logo silently fell back to a cropped
        // screenshot — one photograph in a wall of marks, which reads as a mistake. Without an
        // image the card renders a typographic name plate instead: still on-brand, still a link to
        // the live site, and obviously awaiting an asset rather than looking broken.
        $isWebCard = $project['category'] === 'web';
        $hasLogo = $isWebCard && $project['logoUrl'] !== '';

        $mediaSrc = $hasLogo ? $project['logoUrl'] : $project['thumbUrl'];
        $mediaAlt = $hasLogo ? $project['logoAlt'] : $project['thumbAlt'];

        if ($isWebCard && ! $hasLogo) {
            $media = $this->namePlate($project);
        } elseif ($hasLogo) {
            // A logo is a brand mark, not a scene: it has no per-shape crops and must not be
            // swapped by breakpoint.
            $media = '<img src="' . esc_url($mediaSrc) . '" alt="' . esc_attr($mediaAlt) . '" loading="lazy" />';
        } elseif ($mediaSrc !== '') {
            // These cards are all one shape, so they ask for the uniform card crop.
            $media = ProjectTileImage::render((int) ($project['id'] ?? 0), '', $mediaAlt, $mediaSrc);
        } else {
            $media = '<span class="post-card__media-placeholder" data-category="' . esc_attr($project['category']) . '" aria-hidden="true"></span>';
        }

        // The work grid used to carry no affordance at all, even though its non-web cards are lightbox
        // buttons — a card that opened a gallery looked identical to one that did nothing. It now
        // shows whatever the editor chose, the same vocabulary the services mosaic uses.
        $affordance = $isWebCard
            ? ''
            : ServiceSelectedWorkRenderer::affordance((string) ($project['icon'] ?? 'none'), $galleryBadge);

        $classes = 'post-card reveal' . ($isWebCard ? ' post-card--logo' : '')
            . ($isWebCard && ! $hasLogo ? ' post-card--plate' : '');
        $category = ' data-category="' . esc_attr($project['category']) . '"';

        if ($isWebCard && $project['siteUrl'] !== '') {
            /* translators: %s: project title. */
            $visitLabel = sprintf(__('Visit %s', 'perego-site'), $project['title']);
            $open = '<a class="' . $classes . '" href="' . esc_url($project['siteUrl']) . '" '
                . 'target="_blank" rel="noopener" '
                . 'aria-label="' . esc_attr($visitLabel) . '"' . $category . '>';
            $close = '</a>';
        } elseif ($isWebCard) {
            $open = '<article class="' . $classes . '"' . $category . '>';
            $close = '</article>';
        } else {
            /* translators: %s: project title. */
            $openLabel = sprintf(__('Open %s', 'perego-site'), $project['title']);
            $open = '<button type="button" class="' . $classes . '" ' . $this->lightboxTrigger($project) . ' '
                . 'aria-label="' . esc_attr($openLabel) . '"' . $category . '>';
            $close = '</button>';
        }

        $role = (string) ($project['role'] ?? '');

        $html = $open;
        $html .= '<div class="post-card__media">' . $media . $affordance . '</div>';
        $html .= '<div class="post-card__body">';
        $html .= '<span class="post-card__cat">' . esc_html($project['categoryLabel']) . '</span>';
        $html .= '<h2 class="post-card__title" style="font-size:clamp(18px,1.6vw,22px);">' . esc_html($project['title']) . '</h2>';
        // The role qualifier replaces the excerpt when set: on a card that names a client's site,
        // "framework upgrade participation" is the more honest and more useful sentence.
        $html .= $role !== ''
            ? '<p class="post-card__role">' . esc_html($role) . '</p>'
            : '<p class="post-card__excerpt">' . esc_html($project['excerpt']) . '</p>';
        $html .= '</div>' . $close;

        return $html;
    }

    /**
     * A typographic stand-in for a website whose logo has not been supplied yet.
     *
     * Five of the twenty-nine sites do not publish a usable logo a script can reach — two are behind
     * a bot challenge, one is a JavaScript-only shell, one no longer resolves, and one publishes its
     * mark at 192px. Showing nothing would drop them from a portfolio the client asked to be
     * complete; showing a screenshot instead would break the wall of marks. The plate is the site's
     * name over its bare domain, in the brand's own type.
     *
     * @param Project $project
     */
    private function namePlate(array $project): string
    {
        $host = (string) wp_parse_url($project['siteUrl'], PHP_URL_HOST);

        return '<span class="post-card__plate" aria-hidden="true">'
            . '<span class="post-card__plate-name">' . esc_html($project['title']) . '</span>'
            . ($host !== '' ? '<span class="post-card__plate-host">' . esc_html(preg_replace('/^www\./', '', $host) ?? $host) . '</span>' : '')
            . '</span>';
    }

    /**
     * The card's lightbox trigger attribute. A project carrying a gallery AND a video opens both as one
     * mixed gallery; otherwise a video wins, then a real multi-image gallery, then the single featured
     * image. A project with no media at all yields no trigger — the card then does nothing on click
     * rather than opening an empty dialog, which matters because the client noted not every project has
     * content yet.
     *
     * @param array{thumbUrl: string, gallerySrcs: list<string>, videoUrl?: string} $project
     */
    private function lightboxTrigger(array $project): string
    {
        $videoUrl = (string) ($project['videoUrl'] ?? '');
        $gallery = $project['gallerySrcs'];

        // A project with BOTH becomes one mixed gallery rather than hiding its stills behind the video.
        // media-lightbox/view.js picks a renderer per slide (`mediaType()` -> embed / video / img), so a
        // single data-gallery list can carry images and a video together.
        if ($videoUrl !== '' && count($gallery) > 1) {
            return 'data-gallery="' . esc_attr(implode(',', [...$gallery, $videoUrl])) . '"';
        }

        if ($videoUrl !== '') {
            return 'data-video="' . esc_attr($videoUrl) . '"';
        }

        if (count($gallery) > 1) {
            return 'data-gallery="' . esc_attr(implode(',', $gallery)) . '"';
        }

        $image = $project['thumbUrl'] !== '' ? $project['thumbUrl'] : ($project['gallerySrcs'][0] ?? '');

        return $image !== '' ? 'data-image="' . esc_attr($image) . '"' : '';
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
