<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Content\ServiceContent;

/**
 * Server-renders the perego-theme/services-overview block (spec 003 / M3, US3): the services
 * archive ("Our Services" — one studio, four services). Language-aware via ServiceContent, so the
 * language-neutral FSE archive template stays free of hardcoded prose (Polylang Free constraint).
 *
 * Structure ported from the handoff `services.html`: intro (H1 + title + subline + two paragraphs),
 * a four-service card grid linking to the singles, the shared four-step process (reuses the theme's
 * `.svc-process` styling), and the closing "Have a project in mind?" CTA. Exactly one H1.
 */
final class ServicesOverviewRenderer
{
    public function render(ServiceContent $content): string
    {
        $o = $content->overview();

        $html = '<section class="services-overview" aria-labelledby="services-overview-title">';

        // Intro — the single H1 for the archive.
        $html .= '<div class="services-overview__intro">';
        $html .= '<h1 class="services-overview__h1" id="services-overview-title">' . esc_html($o['h1']) . '</h1>';
        $html .= '<p class="services-overview__eyebrow">' . esc_html($o['introTitle'])
            . ' · ' . esc_html($o['introSubline']) . '</p>';
        $html .= '<p class="services-overview__lead">' . esc_html($o['introP1']) . '</p>';
        $html .= '<p class="services-overview__lead">' . esc_html($o['introP2']) . '</p>';
        $html .= '</div>';

        // Four service cards linking to the singles.
        $html .= '<div class="services-overview__cards">';
        foreach ($content->slugs() as $slug) {
            $href = esc_url(home_url('/services/' . $slug));
            $html .= '<a class="svc-overview-card" href="' . $href . '">';
            $html .= '<span class="svc-overview-card__label">' . esc_html($content->name($slug)) . '</span>';
            $html .= '<span class="svc-overview-card__sub">' . esc_html($content->subline($slug)) . '</span>';
            $html .= '</a>';
        }
        $html .= '</div>';

        // Shared process (reuses the theme .svc-process look).
        $html .= '<section class="svc-process services-overview__process" aria-labelledby="services-overview-process">';
        $html .= '<h2 class="wp-block-heading" id="services-overview-process">' . esc_html($o['processTitle']) . '</h2>';
        $html .= '<ol class="wp-block-list">';
        foreach ($o['processSteps'] as $step) {
            $html .= '<li><strong>' . esc_html($step['label']) . '</strong> — ' . esc_html($step['desc']) . '</li>';
        }
        $html .= '</ol>';
        $html .= '</section>';

        // Closing CTA.
        $html .= '<section class="services-overview__cta">';
        $html .= '<h2 class="wp-block-heading">' . esc_html($o['ctaTitle']) . '</h2>';
        $html .= '<p>' . esc_html($o['ctaBody']) . '</p>';
        $html .= '<a class="btn btn--accent" href="' . esc_url(home_url('/contact')) . '">'
            . esc_html($o['ctaButton']) . '</a>';
        $html .= '</section>';

        $html .= '</section>';

        return $html;
    }
}
