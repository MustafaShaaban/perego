<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use Corex\Assets\Image;
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
 *
 * spec 020 round 4 — the section heading + "See All Services" link text are real block attributes,
 * RichText-editable in the canvas (previously 100% hardcoded with zero admin UI). This block instance
 * lives in the shared `front-page.html` FSE template, so — matching `footer-careers`'/
 * `ClientsCarouselRenderer`'s pattern for the same problem — each string is an En/Ar attribute pair;
 * an empty attribute falls back to the locale-aware `HomeContent` seed.
 */
final class ServicesTeaserRenderer
{
    private const HEADING_ID = 'servicesTeaserTitle';

    public function __construct(private readonly LanguageService $languageService)
    {
    }

    /** @param array<string,string> $attributes */
    public function render(array $attributes = []): string
    {
        $locale  = $this->languageService->driver()->currentLocale();
        $content = new HomeContent($locale);
        $posts   = (new ServiceCatalog())->postsBySlug($locale);
        $suffix  = $locale === 'ar' ? 'Ar' : 'En';
        $heading = trim((string) ($attributes['heading' . $suffix] ?? '')) ?: $content->servicesTeaserTitle();
        $seeAll  = trim((string) ($attributes['seeAll' . $suffix] ?? '')) ?: $content->servicesTeaserSeeAll();

        $html = '<section class="services-teaser" id="services" aria-labelledby="' . self::HEADING_ID . '">';

        $html .= '<div class="wavy-bg" aria-hidden="true">'
            . Image::picture('images/wavy-corners.png', [
                'base' => 'perego-theme',
                'alt' => '',
                'width' => 2560,
                'height' => 1440,
            ])
            . '</div>';

        $html .= '<div class="container services-teaser__inner">';

        $html .= '<div class="services-teaser__head">';
        $html .= '<h2 class="services-teaser__title reveal" id="' . self::HEADING_ID . '">'
            . wp_kses_post($heading) . '</h2>';
        // spec 021 T036: "See all" may name a page instead of the default services archive.
        $seeAllLink = LinkTarget::fromAttributes($attributes, 'seeAll');
        $linkTarget = new LinkTarget($this->languageService->driver());
        $html .= '<a class="link-arrow services-teaser__link reveal" data-delay="1" href="'
            . esc_url($linkTarget->href($seeAllLink, '/services')) . '"'
            . $linkTarget->targetAttributes($seeAllLink) . '>'
            . esc_html($seeAll)
            . $this->arrowSvg()
            . '</a>';
        $html .= '</div>';

        $html .= '<div class="service-cards">';
        foreach ($this->cards($content->services(), $posts, $locale, $attributes) as $index => $card) {
            $html .= $this->renderCard($card, $index);
        }
        $html .= '</div>';

        $html .= '</div>'; // .services-teaser__inner
        $html .= '</section>';

        return $html;
    }

    /**
     * The established automatic projection remains the default. Manual mode restricts the card set to
     * explicitly selected Services; hybrid mode places those selections first, then appends the
     * remaining automatic cards. IDs are translated at render time for a shared EN/AR template.
     *
     * @param list<array{slug:string,name:string,image:string,alt:string}> $seeds
     * @param array<string,\WP_Post> $postsBySlug
     * @param array<string,mixed> $attributes
     * @return list<array{slug:string,name:string,imageUrl:string,alt:string}>
     */
    private function cards(array $seeds, array $postsBySlug, string $locale, array $attributes): array
    {
        $mode = (string) ($attributes['servicesMode'] ?? 'automatic');
        $selectedIds = $this->positiveIds($attributes['serviceOrder'] ?? []);
        $excludedIds = $this->positiveIds($attributes['serviceExcludeIds'] ?? []);
        $seedBySlug = [];
        foreach ($seeds as $seed) {
            $seedBySlug[$seed['slug']] = $seed;
        }

        $selected = $this->selectedCards($selectedIds, $seedBySlug, $locale);
        if ($mode === 'manual') {
            return $selected;
        }

        $automatic = [];
        foreach ($seeds as $seed) {
            $post = $postsBySlug[$seed['slug']] ?? null;
            if ($this->isExcluded($post, $excludedIds)) {
                continue;
            }
            $automatic[] = $this->resolveCard($seed, $post);
        }
        if ($mode !== 'hybrid') {
            return $automatic;
        }

        $selectedSlugs = array_column($selected, 'slug');

        return [...$selected, ...array_values(array_filter(
            $automatic,
            static fn (array $card): bool => ! in_array($card['slug'], $selectedSlugs, true)
        ))];
    }

    /**
     * @param list<int> $ids
     * @param array<string,array{slug:string,name:string,image:string,alt:string}> $seedBySlug
     * @return list<array{slug:string,name:string,imageUrl:string,alt:string}>
     */
    private function selectedCards(array $ids, array $seedBySlug, string $locale): array
    {
        if (! function_exists('get_post')) {
            return [];
        }

        $cards = [];
        foreach ($ids as $id) {
            $localizedId = function_exists('pll_get_post') ? (int) pll_get_post($id, $locale) : $id;
            $post = get_post($localizedId ?: $id);
            if (! $post instanceof \WP_Post || $post->post_type !== ServicePostType::POST_TYPE || $post->post_status !== 'publish') {
                continue;
            }
            $slug = (string) get_post_meta($post->ID, ServicePostType::META_SERVICE_SLUG, true);
            if (! isset($seedBySlug[$slug])) {
                continue;
            }
            $cards[] = $this->resolveCard($seedBySlug[$slug], $post);
        }

        return $cards;
    }

    /** @param mixed $ids @return list<int> */
    private function positiveIds($ids): array
    {
        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(static fn ($id): int => abs((int) $id), $ids))));
    }

    /** @param list<int> $excludedIds */
    private function isExcluded(?\WP_Post $post, array $excludedIds): bool
    {
        if ($post === null || $excludedIds === []) {
            return false;
        }
        $englishId = function_exists('pll_get_post') ? (int) pll_get_post($post->ID, 'en') : 0;

        return array_intersect([$post->ID, $englishId], $excludedIds) !== [];
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
        $href = esc_url($this->languageService->driver()->localizedUrl('/services/' . $service['slug']));

        $themeImagesUrl = rtrim(get_stylesheet_directory_uri(), '/') . '/assets/images/';
        $media = str_starts_with($service['imageUrl'], $themeImagesUrl)
            ? Image::picture('images/' . basename($service['imageUrl']), [
                'base' => 'perego-theme',
                'alt' => $service['alt'],
                'width' => 560,
                'height' => 680,
            ])
            : '<img src="' . esc_url($service['imageUrl']) . '" alt="' . esc_attr($service['alt']) . '" loading="lazy" />';

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
