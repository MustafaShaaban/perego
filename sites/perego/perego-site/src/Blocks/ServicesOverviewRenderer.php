<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Content\ServiceContent;

/**
 * Server-renders the perego-theme/services-overview block (spec 003 / M3, US3; restructured spec 004
 * T014): the services archive ("Our Services" — one studio, four services). Language-aware via
 * ServiceContent, so the language-neutral FSE archive template stays free of hardcoded prose
 * (Polylang Free constraint).
 *
 * Structure ported from the handoff `services.html`: a hero (background image + H1 + four service
 * tabs, each with a "start your project" sub-link), a "what we do" intro (H2 + subline + two
 * paragraphs + a media image), the shared four-step process (reuses the theme's `.svc-process`
 * styling), and the closing "Have a project in mind?" CTA. Exactly one H1.
 */
final class ServicesOverviewRenderer
{
    public function render(ServiceContent $content): string
    {
        $o = $content->overview();

        $html = '<section class="services-overview">';
        $html .= $this->renderHero($o, $content);
        $html .= $this->renderWhatWeDo($o);
        $html .= $this->renderProcess($o);
        $html .= $this->renderCta($o);
        $html .= '</section>';

        return $html;
    }

    /** @param array<string, mixed> $o */
    private function renderHero(array $o, ServiceContent $content): string
    {
        $html = '<section class="svc-hero" aria-labelledby="services-overview-title">';
        $html .= '<div class="svc-hero__bg" aria-hidden="true">'
            . '<img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/svc-hero-bg.png') . '" alt="" />'
            . '</div>';
        $html .= '<div class="svc-hero__inner">';
        $html .= '<h1 class="svc-hero__title" id="services-overview-title">' . esc_html($o['h1']) . '</h1>';
        $html .= '<nav class="svc-tabs" aria-label="' . esc_attr($o['h1']) . '">';

        foreach ($content->slugs() as $slug) {
            $href = esc_url(home_url('/services/' . $slug));
            $ctaHref = esc_url(add_query_arg('service', $content->name($slug), home_url('/contact')));
            $html .= '<div class="svc-tab">';
            $html .= '<a class="svc-tab__main" href="' . $href . '"><span class="svc-tab__label">'
                . esc_html($content->name($slug)) . '</span></a>';
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

    /** @param array<string, mixed> $o */
    private function renderCta(array $o): string
    {
        $html = '<section class="services-overview__cta">';
        $html .= '<div class="services-overview__cta-inner">';
        $html .= '<h2 class="wp-block-heading">' . esc_html($o['ctaTitle']) . '</h2>';
        $html .= '<p>' . esc_html($o['ctaBody']) . '</p>';
        $html .= '<a class="perego-btn perego-btn--accent" href="' . esc_url(home_url('/contact')) . '">'
            . esc_html($o['ctaButton']) . '</a>';
        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }
}
