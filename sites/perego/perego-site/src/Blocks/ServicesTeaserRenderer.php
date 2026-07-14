<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Content\HomeContent;
use PeregoSite\Content\ServiceCatalog;
use PeregoSite\PostTypes\ServicePostType;
use PeregoSite\Services\LanguageService;

/**
 * Server-renders the perego/services-teaser block (spec 002 / M2, US2): the homepage "Services we
 * can help you with" section — a heading, a "See All Services" arrow link, and the four service
 * cards (staggered offsets + hover lift handled entirely in CSS). Ported from the handoff
 * prototype's `.services-teaser` section. Static (no view script); hover is pure CSS.
 *
 * Spec 012 T003 — the cards are now a projection of the `perego_service` CPT: `HomeContent` provides
 * the fixed order + the handoff seed (slug/label/image/alt), and each card's presentation is
 * overlaid, PER FIELD, from the matching published Service's teaser meta
 * (`_perego_teaser_label`/`_perego_teaser_image_id`/`_perego_teaser_alt`, keyed by
 * `_perego_service_slug` for the current locale). Missing post or unset meta falls back to the seed,
 * so output is byte-identical until an editor sets a value — then editing a Service updates the card.
 */
final class ServicesTeaserRenderer
{
    private const HEADING_ID = 'servicesTeaserTitle';

    public function __construct(private readonly LanguageService $languageService)
    {
    }

    public function render(): string
    {
        $locale  = $this->languageService->driver()->currentLocale();
        $content = new HomeContent($locale);
        $posts   = (new ServiceCatalog())->postsBySlug($locale);

        $html = '<section class="services-teaser" id="services" aria-labelledby="' . self::HEADING_ID . '">';

        $html .= '<div class="wavy-bg" aria-hidden="true">'
            . '<img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/wavy-corners.png') . '" alt="" />'
            . '</div>';

        $html .= '<div class="container services-teaser__inner">';

        $html .= '<div class="services-teaser__head">';
        $html .= '<h2 class="services-teaser__title reveal" id="' . self::HEADING_ID . '">'
            . esc_html($content->servicesTeaserTitle()) . '</h2>';
        $html .= '<a class="link-arrow services-teaser__link reveal" data-delay="1" href="' . esc_url(home_url('/services')) . '">'
            . esc_html($content->servicesTeaserSeeAll())
            . $this->arrowSvg()
            . '</a>';
        $html .= '</div>';

        $html .= '<div class="service-cards">';
        foreach ($content->services() as $index => $service) {
            $card = $this->resolveCard($service, $posts[$service['slug']] ?? null);
            $html .= $this->renderCard($card, $index);
        }
        $html .= '</div>';

        $html .= '</div>'; // .services-teaser__inner
        $html .= '</section>';

        return $html;
    }

    /**
     * Overlay the seed card with the Service's teaser meta, per field. `slug` (and thus the route) is
     * always the canonical seed slug; label/image/alt use the meta when set, else the seed.
     *
     * @param array{slug: string, name: string, image: string, alt: string} $seed
     * @return array{slug: string, name: string, imageUrl: string, alt: string}
     */
    private function resolveCard(array $seed, ?\WP_Post $post): array
    {
        $name     = $seed['name'];
        $alt      = $seed['alt'];
        $imageUrl = get_stylesheet_directory_uri() . '/assets/images/' . $seed['image'] . '.png';

        if ($post !== null) {
            $label = (string) get_post_meta($post->ID, ServicePostType::META_TEASER_LABEL, true);
            if ($label !== '') {
                $name = $label;
            }

            $metaAlt = (string) get_post_meta($post->ID, ServicePostType::META_TEASER_ALT, true);
            if ($metaAlt !== '') {
                $alt = $metaAlt;
            }

            $imageId = (int) get_post_meta($post->ID, ServicePostType::META_TEASER_IMAGE_ID, true);
            if ($imageId > 0) {
                $resolved = wp_get_attachment_image_url($imageId, 'large');
                if (is_string($resolved) && $resolved !== '') {
                    $imageUrl = $resolved;
                }
            }
        }

        return [
            'slug'     => $seed['slug'],
            'name'     => $name,
            'imageUrl' => $imageUrl,
            'alt'      => $alt,
        ];
    }

    /**
     * @param array{slug: string, name: string, imageUrl: string, alt: string} $service
     */
    private function renderCard(array $service, int $index): string
    {
        $href = esc_url(home_url('/services/' . $service['slug']));

        $media = '<img src="' . esc_url($service['imageUrl']) . '" alt="' . esc_attr($service['alt']) . '" loading="lazy" />';

        return '<a class="service-card reveal"' . ($index > 0 ? ' data-delay="' . esc_attr((string) $index) . '"' : '') . ' href="' . $href . '">'
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
