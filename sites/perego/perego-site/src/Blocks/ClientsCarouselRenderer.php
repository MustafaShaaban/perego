<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Content\ClientsContent;
use PeregoSite\PostTypes\ClientPostType;
use WP_Query;

/**
 * Server-renders the perego-theme/clients-carousel block (spec M6): the homepage Corporate +
 * Individual client carousels. Cards are **server-rendered and present without JavaScript** (the
 * Swiper enhancement in view.js only upgrades the already-rendered .swiper markup — no page-level
 * horizontal overflow, works with JS off). Language-aware via ClientsContent. Ported from the
 * handoff `index.html` #clients.
 */
final class ClientsCarouselRenderer
{
    private const PER_TYPE = 12;

    public function __construct(
        private readonly ClientsContent $content,
        private readonly string $locale = 'en'
    ) {
    }

    public function render(): string
    {
        $html = '<section class="clients page-section" id="clients" aria-label="' . esc_attr($this->content->get('sectionLabel')) . '">';
        $html .= '<div class="clients__inner">';
        $html .= $this->carousel('corporate', 'corporateTitle', 'corporateSubtitle');
        $html .= $this->carousel('individual', 'individualTitle', 'individualSubtitle');
        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    private function carousel(string $type, string $titleKey, string $subtitleKey): string
    {
        $clients = $this->query($type);
        $headingId = 'clients-' . $type . '-title';

        $html = '<div class="clients-carousel clients-carousel--' . esc_attr($type) . '" aria-labelledby="' . $headingId . '">';
        $html .= '<h2 class="clients-carousel__title" id="' . $headingId . '">' . esc_html($this->content->get($titleKey)) . '</h2>';
        $html .= '<p class="clients-carousel__subtitle">' . esc_html($this->content->get($subtitleKey)) . '</p>';

        if ($clients === []) {
            return $html . '</div>';
        }

        $html .= '<div class="swiper clients-swiper clients-swiper--' . esc_attr($type) . '" data-clients-swiper>';
        $html .= '<div class="swiper-wrapper">';
        foreach ($clients as $client) {
            $html .= $type === 'corporate' ? $this->corporateCard($client) : $this->individualCard($client);
        }
        $html .= '</div>'; // .swiper-wrapper
        $html .= '<button type="button" class="swiper-button-prev clients-swiper__prev" aria-label="' . esc_attr__('Previous', 'perego-site') . '"></button>';
        $html .= '<button type="button" class="swiper-button-next clients-swiper__next" aria-label="' . esc_attr__('Next', 'perego-site') . '"></button>';
        $html .= '<div class="swiper-pagination clients-swiper__pagination"></div>';
        $html .= '</div>'; // .swiper

        return $html . '</div>';
    }

    /**
     * Corporate: a small square tile (handoff `.corp-card`) — icon/logo only, no visible name. The
     * client name stays available to assistive tech via aria-label.
     */
    private function corporateCard(\WP_Post $client): string
    {
        $title = (string) get_the_title($client);
        $thumb = has_post_thumbnail($client->ID) ? get_the_post_thumbnail($client->ID, 'medium', ['loading' => 'lazy', 'alt' => '']) : '';

        $html = '<div class="swiper-slide client-card client-card--corporate" role="img" aria-label="' . esc_attr($title) . '">';
        $html .= $thumb !== ''
            ? '<div class="client-card__media">' . $thumb . '</div>'
            : '<img class="client-card__icon" src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/corp-icon.png') . '" alt="" />';
        $html .= '</div>';

        return $html;
    }

    /**
     * Individual: a wide info-plus-thumbnail card (handoff `.indiv-card`) — name/stat text beside a
     * thumbnail. No play-button overlay: unlike the handoff's demo, real cards here have no video to
     * play, and a decorative play icon on a non-interactive card would be a misleading affordance.
     */
    private function individualCard(\WP_Post $client): string
    {
        $title = (string) get_the_title($client);
        $stat = (string) get_post_meta($client->ID, '_perego_client_stat', true);
        $thumb = has_post_thumbnail($client->ID) ? get_the_post_thumbnail($client->ID, 'medium', ['loading' => 'lazy', 'alt' => '']) : '';

        $html = '<div class="swiper-slide client-card client-card--individual">';
        $html .= '<div class="client-card__info">';
        $html .= '<p class="client-card__name">' . esc_html($title) . '</p>';
        if ($stat !== '') {
            $html .= '<p class="client-card__stat">' . esc_html($stat) . '</p>';
        }
        $html .= '</div>'; // .client-card__info

        $html .= '<div class="client-card__thumb">';
        $html .= $thumb !== '' ? $thumb : '<div class="client-card__media--placeholder" aria-hidden="true"></div>';
        $html .= '</div>'; // .client-card__thumb

        $html .= '</div>';

        return $html;
    }

    /** @return list<\WP_Post> */
    private function query(string $type): array
    {
        $q = new WP_Query([
            'post_type' => ClientPostType::POST_TYPE,
            'posts_per_page' => self::PER_TYPE,
            'no_found_rows' => true,
            'post_status' => 'publish',
            'ignore_sticky_posts' => true,
            'tax_query' => [[
                'taxonomy' => ClientPostType::TAXONOMY,
                'field' => 'term_id',
                'terms' => $this->termIdForCurrentLocale($type),
            ]],
        ]);

        /** @var list<\WP_Post> $posts */
        $posts = $q->posts;
        wp_reset_postdata();

        return $posts;
    }

    /**
     * Resolve the `perego_client_type` term id for the *current* language. Polylang gives every
     * language its own term (e.g. `corporate` for en, a separate `corporate-ar` term for ar, linked as
     * translations) — querying by the English slug alone only ever matches English-tagged posts, so
     * the Arabic carousels rendered empty even though AR client posts existed and carried the AR term.
     */
    private function termIdForCurrentLocale(string $enSlug): int
    {
        $enTerm = get_term_by('slug', $enSlug, ClientPostType::TAXONOMY);
        if (! $enTerm) {
            return 0;
        }

        if (! function_exists('pll_get_term')) {
            return (int) $enTerm->term_id;
        }

        $localized = pll_get_term((int) $enTerm->term_id, $this->locale);

        return $localized ? (int) $localized : (int) $enTerm->term_id;
    }
}
