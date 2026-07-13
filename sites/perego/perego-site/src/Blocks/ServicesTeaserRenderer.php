<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Content\HomeContent;
use PeregoSite\Services\LanguageService;

/**
 * Server-renders the perego/services-teaser block (spec 002 / M2, US2): the homepage "Services we
 * can help you with" section — a heading, a "See All Services" arrow link, and the four service
 * cards (staggered offsets + hover lift handled entirely in CSS). Ported from the handoff
 * prototype's `.services-teaser` section. Static (no view script); hover is pure CSS.
 *
 * Content-driven for now; in M3 the cards rebind to the `service` CPT with no markup change (the
 * slugs already match the service single routes the header links to).
 */
final class ServicesTeaserRenderer
{
    private const HEADING_ID = 'servicesTeaserTitle';

    public function __construct(private readonly LanguageService $languageService)
    {
    }

    public function render(): string
    {
        $content = new HomeContent($this->languageService->driver()->currentLocale());

        $html = '<section class="services-teaser" id="services" aria-labelledby="' . self::HEADING_ID . '">';

        $html .= '<div class="wavy-bg" aria-hidden="true">'
            . '<img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/wavy-corners.png') . '" alt="" />'
            . '</div>';

        $html .= '<div class="services-teaser__inner">';

        $html .= '<div class="services-teaser__head">';
        $html .= '<h2 class="services-teaser__title" id="' . self::HEADING_ID . '">'
            . esc_html($content->servicesTeaserTitle()) . '</h2>';
        $html .= '<a class="perego-link-arrow services-teaser__link" href="' . esc_url(home_url('/services')) . '">'
            . esc_html($content->servicesTeaserSeeAll())
            . $this->arrowSvg()
            . '</a>';
        $html .= '</div>';

        $html .= '<div class="service-cards">';
        foreach ($content->services() as $service) {
            $html .= $this->renderCard($service);
        }
        $html .= '</div>';

        $html .= '</div>'; // .services-teaser__inner
        $html .= '</section>';

        return $html;
    }

    /**
     * @param array{slug: string, name: string, image: string, alt: string} $service
     */
    private function renderCard(array $service): string
    {
        $href = esc_url(home_url('/services/' . $service['slug']));

        $imageUrl = get_stylesheet_directory_uri() . '/assets/images/' . $service['image'] . '.png';
        $media    = '<img src="' . esc_url($imageUrl) . '" alt="' . esc_attr($service['alt']) . '" loading="lazy" />';

        return '<a class="service-card" href="' . $href . '">'
            . $media
            . '<span class="service-card__overlay"></span>'
            . '<span class="service-card__label">' . esc_html($service['name']) . '</span>'
            . '</a>';
    }

    private function arrowSvg(): string
    {
        return '<svg width="34" height="16" viewBox="0 0 34 16" fill="none" aria-hidden="true">'
            . '<path d="M0 8h31M25 2l7 6-7 6" stroke="currentColor" stroke-width="2.4" '
            . 'stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }
}
