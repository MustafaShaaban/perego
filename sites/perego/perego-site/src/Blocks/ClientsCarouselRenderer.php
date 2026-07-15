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
 * Individual client carousels. Cards are **server-rendered and present without JavaScript**; the
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
        $html = '<section class="clients" id="clients" aria-labelledby="corporateTitle">';
        $html .= '<div class="wavy-bg" aria-hidden="true"><img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/wavy-corners.png') . '" alt="" /></div>';
        $html .= '<svg width="0" height="0" style="position:absolute" aria-hidden="true"><defs><linearGradient id="eqg" x1="0" y1="0" x2="64" y2="64" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#31ffff"/><stop offset="0.5" stop-color="#7b8bf0"/><stop offset="1" stop-color="#d86af3"/></linearGradient></defs><symbol id="eq" viewBox="0 0 64 64"><g fill="none" stroke="#ffffff" stroke-width="3.4" stroke-linecap="round"><line x1="17" y1="11" x2="17" y2="53"/><line x1="32" y1="11" x2="32" y2="53"/><line x1="47" y1="11" x2="47" y2="53"/></g><g fill="#4a0d8f" stroke="#ffffff" stroke-width="3.2"><circle class="eq-bar" cx="17" cy="36" r="7"/><circle class="eq-bar" cx="32" cy="46" r="7"/><circle class="eq-bar" cx="47" cy="22" r="7"/></g></symbol></svg>';
        $html .= '<div class="container clients__inner">';
        $html .= $this->carousel('corporate', 'corporateTitle', 'corporateSubtitle');
        $html .= $this->carousel('individual', 'individualTitle', 'individualSubtitle');
        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    private function carousel(string $type, string $titleKey, string $subtitleKey): string
    {
        $clients = $this->query($type);
        $headingId = $type === 'corporate' ? 'corporateTitle' : 'individualTitle';

        $html = '<header class="clients__head' . ($type === 'individual' ? ' clients__head--indiv' : '') . ' reveal">';
        $html .= '<h2 class="section-title" id="' . $headingId . '">' . esc_html($this->content->get($titleKey)) . '</h2>';
        $html .= '<p class="section-subtitle">' . esc_html($this->content->get($subtitleKey)) . '</p></header>';

        if ($clients === []) {
            return $html;
        }

        $trackClass = $type === 'corporate' ? 'corp-track' : 'indiv-track';
        $sliderClass = $type === 'corporate' ? 'corp-slider' : 'indiv-slider';
        $isCorporate = $type === 'corporate';
        $previousLabel = $isCorporate ? __('Previous clients', 'perego-site') : __('Previous', 'perego-site');
        $nextLabel = $isCorporate ? __('More clients', 'perego-site') : __('More', 'perego-site');
        $trackAttributes = $isCorporate
            ? 'id="corporateTrack" tabindex="0" role="list" aria-label="' . esc_attr__('Corporate client logos', 'perego-site') . '"'
            : 'id="individualTrack" tabindex="0" role="list" aria-label="' . esc_attr__('Individual clients', 'perego-site') . '"';

        $html .= '<div class="' . $sliderClass . ' reveal"><button type="button" class="corp-arrow corp-arrow--prev" aria-label="' . esc_attr($previousLabel) . '">' . $this->arrowSvg('previous') . '</button>';
        $html .= '<div class="' . $trackClass . '" ' . $trackAttributes . '>';
        foreach ($clients as $client) {
            $html .= $type === 'corporate' ? $this->corporateCard($client) : $this->individualCard($client);
        }
        $html .= '</div><button type="button" class="corp-arrow corp-arrow--next" aria-label="' . esc_attr($nextLabel) . '">' . $this->arrowSvg('next') . '</button></div>';

        return $html;
    }

    /**
     * Corporate: a small square tile (handoff `.corp-card`) — icon/logo only, no visible name. The
     * client name stays available to assistive tech via aria-label.
     */
    private function corporateCard(\WP_Post $client): string
    {
        $title = (string) get_the_title($client);
        $html = '<button type="button" class="corp-card" role="listitem" aria-label="' . esc_attr($title) . '">';
        $html .= '<svg class="eq-icon" viewBox="0 0 64 64" aria-hidden="true"><use href="#eq"></use></svg>';
        $html .= '</button>';

        return $html;
    }

    /**
     * Individual: a wide info-plus-thumbnail card (handoff `.indiv-card`) — name/stat text beside a
     * thumbnail. When an editor sets a real `_perego_client_video_url`, the card opens it in the
     * site-wide media lightbox (perego-theme/media-lightbox) and shows the handoff's `.play-btn`
     * affordance; without one, it stays a plain non-video card — a decorative play icon on a card
     * with nothing to play would be a misleading affordance.
     */
    private function individualCard(\WP_Post $client): string
    {
        $title = (string) get_the_title($client);
        $stat = (string) get_post_meta($client->ID, '_perego_client_stat', true);
        $thumb = has_post_thumbnail($client->ID) ? get_the_post_thumbnail($client->ID, 'medium', ['loading' => 'lazy', 'alt' => '']) : '';
        $videoUrl = (string) get_post_meta($client->ID, '_perego_client_video_url', true);

        if ($videoUrl !== '') {
            $html = '<a class="indiv-card" href="' . esc_url($videoUrl) . '" target="_blank" rel="noopener" '
                . 'data-video="' . esc_attr($videoUrl) . '" role="listitem">';
        } else {
            $html = '<div class="indiv-card" role="listitem">';
        }

        $html .= '<div class="indiv-card__info"><h3 class="indiv-card__title">' . esc_html($title) . '</h3>';
        if ($stat !== '') {
            $html .= '<p class="indiv-card__stat">' . esc_html($stat) . '</p>';
        }
        $html .= '</div>'; // .client-card__info

        $html .= '<div class="indiv-card__thumb">';
        $html .= $thumb !== '' ? $thumb : '<img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/client-review-crop.png') . '" alt="" loading="lazy" />';
        if ($videoUrl !== '') {
            $html .= '<span class="play-btn" aria-hidden="true"></span>';
        }
        $html .= '</div>'; // .client-card__thumb

        $html .= $videoUrl !== '' ? '</a>' : '</div>';

        return $html;
    }

    private function arrowSvg(string $direction): string
    {
        $path = $direction === 'previous' ? 'M15 4 7 12l8 8' : 'M9 4l8 8-8 8';

        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="' . $path . '" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
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
