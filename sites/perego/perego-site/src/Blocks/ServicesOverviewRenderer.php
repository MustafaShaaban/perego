<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Content\ServiceContent;
use PeregoSite\Theme\SiteRoutes;

/**
 * Server-renders the perego-theme/services-overview block (spec 003 / M3, US3; restructured spec 004
 * T014): the services archive ("Our Services" — one studio, four services). Language-aware via
 * ServiceContent, so the language-neutral FSE archive template stays free of hardcoded prose
 * (Polylang Free constraint).
 *
 * Structure ported from the handoff `services.html`: a hero (background image + H1 + four service
 * tabs, each with a "start your project" sub-link), a "what we do" intro (H2 + subline + two
 * paragraphs + a media image), the shared four-step process, a "Selected work" masonry drawn from
 * real published projects (opening the site-wide media lightbox), and the closing "Have a project in
 * mind?" CTA. Exactly one H1.
 */
final class ServicesOverviewRenderer
{
    public function __construct(private readonly ServiceSelectedWorkRenderer $selectedWorkRenderer)
    {
    }

    /**
     * @param list<array{title: string, thumbUrl: string, thumbAlt: string, gallerySrcs: list<string>}> $selectedWork
     *        Pre-resolved by the caller (real WP_Query + ProjectRepository::galleryFor()) so this
     *        class stays a pure, unit-testable function of its inputs — the same pattern
     *        ProjectGalleryLightboxRenderer uses for its own resolved images array.
     * @param array<string, string> $tabLabels canonical slug => editable tab label (spec 013); missing → seed
     */
    public function render(ServiceContent $content, array $selectedWork, array $tabLabels = []): string
    {
        $o = $content->overview();

        $html = '<section class="services-overview">';
        $html .= $this->renderHero($o, $content, $tabLabels);
        $html .= $this->renderWhatWeDo($o);
        $html .= $this->renderProcess($o);
        $html .= $this->renderSelectedWork($content, $selectedWork);
        $html .= $this->renderCta($o);
        $html .= '</section>';

        return $html;
    }

    /**
     * @param array<string, mixed>  $o
     * @param array<string, string> $tabLabels
     */
    private function renderHero(array $o, ServiceContent $content, array $tabLabels): string
    {
        $html = '<section class="svc-hero" aria-labelledby="services-overview-title">';
        $html .= '<div class="svc-hero__bg" aria-hidden="true">'
            . '<img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/svc-hero-bg.webp') . '" alt="" />'
            . '</div>';
        $html .= '<div class="svc-hero__inner">';
        $html .= '<h1 class="svc-hero__title" id="services-overview-title">' . esc_html($o['h1']) . '</h1>';
        $html .= '<nav class="svc-tabs" aria-label="' . esc_attr($o['h1']) . '">';

        foreach ($content->slugs() as $slug) {
            $href = esc_url(home_url('/services/' . $slug));
            // The contact chooser whitelists the canonical service *slug*, not the localized name —
            // pass the slug so ?service= preselects (matches the service-single hero's CTA).
            // add_query_arg url-encodes the value, so hand it the raw slug (no pre-encoding).
            $ctaHref = esc_url(add_query_arg('service', $slug, home_url(SiteRoutes::START_PROJECT)));
            $label = ($tabLabels[$slug] ?? '') !== '' ? $tabLabels[$slug] : $content->name($slug);
            $html .= '<div class="svc-tab">';
            $html .= '<a class="svc-tab__main" href="' . $href . '"><span class="svc-tab__label">'
                . esc_html($label) . '</span></a>';
            $html .= '<a class="svc-tab__cta" href="' . $ctaHref . '">'
                . esc_html__('Start your project', 'perego-site') . '</a>';
            $html .= '</div>';
        }

        $html .= '</nav>';
        $html .= '</div>'; // .svc-hero__inner
        $html .= '</section>';

        return $html;
    }

    /**
     * Reuses the shared `.svc-whatwedo` look (dark gradient, heading/subline/paragraph treatment) and
     * the shared `.svc-whatwedo__grid`/`__media` two-column layout — both also used by service
     * singles' canvas "What we do" prose (`scripts/seed-services.php`), so they live theme-wide in
     * main.scss rather than in this block's own stylesheet.
     *
     * @param array<string, mixed> $o
     */
    private function renderWhatWeDo(array $o): string
    {
        $html = '<section class="svc-whatwedo">';
        $html .= '<div class="svc-whatwedo__grid">';
        $html .= '<div class="svc-whatwedo__text">';
        $html .= '<h2 class="wp-block-heading">' . esc_html($o['introTitle']) . '</h2>';
        $html .= '<p><strong>' . esc_html($o['introSubline']) . '</strong></p>';
        $html .= '<p>' . esc_html($o['introP1']) . '</p>';
        $html .= '<p>' . esc_html($o['introP2']) . '</p>';
        $html .= '</div>';
        $html .= '<div class="svc-whatwedo__media">'
            . '<img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/ui-video-editing.png') . '" '
            . 'alt="' . esc_attr($o['h1']) . '" loading="lazy" />'
            . '</div>';
        $html .= '</div>'; // .svc-whatwedo__grid
        $html .= '</section>';

        return $html;
    }

    /** Icon file, in the handoff's fixed Discover/Concept/Create/Deliver step order. */
    private const PROCESS_ICONS = ['icon-clapper.png', 'icon-film-l.png', 'icon-star.png', 'icon-film-h.png'];

    private const ARROW_SVG = '<svg viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" '
        . 'stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    /** @param array<string, mixed> $o */
    private function renderProcess(array $o): string
    {
        $html = '<section class="process" aria-labelledby="services-overview-process">';
        $html .= '<div class="container">';
        $html .= '<h2 class="section-title reveal" id="services-overview-process">' . esc_html($o['processTitle']) . '</h2>';
        $html .= '<ol class="process-list">';

        $steps = $o['processSteps'];
        $lastIndex = count($steps) - 1;
        $iconBase = get_stylesheet_directory_uri() . '/assets/images/';

        foreach ($steps as $index => $step) {
            $icon = self::PROCESS_ICONS[$index] ?? self::PROCESS_ICONS[0];
            $html .= '<li class="process-step reveal" data-delay="' . (int) $index . '">'
                . '<span class="process-step__icon"><img src="' . esc_url($iconBase . $icon) . '" alt="" /></span>'
                . '<span class="process-step__label">' . esc_html($step['label']) . '</span>'
                . '<span class="process-step__desc">' . esc_html($step['desc']) . '</span>'
                . '</li>';

            if ($index !== $lastIndex) {
                $html .= '<li class="process-arrow" aria-hidden="true">' . self::ARROW_SVG . '</li>';
            }
        }

        $html .= '</ol>';
        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * "Selected work" — real published projects opening the site-wide media lightbox
     * (perego-theme/media-lightbox). The mosaic itself (designed `.m1`–`.m15` placements, brand card,
     * hidden overflow grid + Load more) is the same component the service singles use
     * (ServiceSelectedWorkRenderer::masonry()); only this heading wrapper differs. Renders nothing
     * when no project has usable media, rather than an empty heading over a blank grid.
     *
     * @param list<array{title: string, thumbUrl: string, thumbAlt: string, gallerySrcs: list<string>}> $selectedWork
     */
    private function renderSelectedWork(ServiceContent $content, array $selectedWork): string
    {
        $masonry = $this->selectedWorkRenderer->masonry($selectedWork, [
            'loadMore' => $content->label('loadMore'),
            'galleryBadge' => $content->label('galleryBadge'),
        ]);

        if ($masonry === '') {
            return '';
        }

        $html = '<section class="portfolio page-section" aria-labelledby="services-overview-selected-work">';
        $html .= '<div class="container">';
        $html .= '<h2 class="section-title reveal" id="services-overview-selected-work" style="margin-bottom:clamp(24px,3vw,40px);">'
            . esc_html($content->label('selectedWork')) . '</h2>';
        $html .= $masonry;
        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /** @param array<string, mixed> $o */
    private function renderCta(array $o): string
    {
        $html = '<section class="services-overview__cta">';
        $html .= '<div class="services-overview__cta-inner">';
        $html .= '<h2 class="wp-block-heading">' . esc_html($o['ctaTitle']) . '</h2>';
        $html .= '<p>' . esc_html($o['ctaBody']) . '</p>';
        $html .= '<a class="perego-btn perego-btn--accent" href="' . esc_url(home_url(SiteRoutes::START_PROJECT)) . '">'
            . esc_html($o['ctaButton']) . '</a>';
        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }
}
