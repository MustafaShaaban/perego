<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Content\ClientsContent;
use PeregoSite\Content\TranslatedMeta;
use PeregoSite\PostTypes\ClientPostType;
use WP_Query;

/**
 * Server-renders the perego-theme/clients-carousel block (spec M6): the homepage Corporate +
 * Individual client carousels. Cards are **server-rendered and present without JavaScript**; a
 * scroll-snap track + arrow-button enhancement in view.js only upgrades the already-rendered markup —
 * no page-level horizontal overflow, works with JS off. Language-aware via ClientsContent. Ported
 * from the handoff `index.html` #clients.
 *
 * spec 020 round 4: the four section headings (corporate/individual title+subtitle) are real block
 * attributes, RichText-editable directly in the block editor canvas — the first genuinely
 * canvas-editable text on this block, previously 100% hardcoded with no admin UI at all. This block
 * instance lives in the shared `front-page.html` FSE template (not per-language post content), so —
 * matching the already-proven `footer-careers` block's pattern for the same problem — each heading is
 * two attributes (`...En`/`...Ar`), both edited together in one editor view, and the PHP renderer
 * picks the current-locale variant. An empty attribute (never edited) falls back to the
 * locale-aware `ClientsContent` seed, so existing pages with no saved attributes render unchanged.
 */
final class ClientsCarouselRenderer
{
    /** Corporate is a dense 2-row logo grid (10 per row at desktop), so it carries far more tiles than the wide individual cards. */
    private const CORP_MAX = 20;

    private const INDIV_MAX = 12;

    /** @param array<string,mixed> $attributes */
    public function __construct(
        private readonly ClientsContent $content,
        private readonly string $locale = 'en',
        private readonly array $attributes = []
    ) {
    }

    public function render(): string
    {
        $html = '<section class="clients" id="clients" aria-labelledby="corporateTitle">';
        $html .= '<div class="wavy-bg" aria-hidden="true"><img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/wavy-corners.png') . '" alt="" /></div>';
        $html .= '<svg width="0" height="0" style="position:absolute" aria-hidden="true"><defs><linearGradient id="eqg" x1="0" y1="0" x2="64" y2="64" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#31ffff"/><stop offset="0.5" stop-color="#7b8bf0"/><stop offset="1" stop-color="#d86af3"/></linearGradient></defs><symbol id="eq" viewBox="0 0 64 64"><g fill="none" stroke="#ffffff" stroke-width="3.4" stroke-linecap="round"><line x1="17" y1="11" x2="17" y2="53"/><line x1="32" y1="11" x2="32" y2="53"/><line x1="47" y1="11" x2="47" y2="53"/></g><g fill="#4a0d8f" stroke="#ffffff" stroke-width="3.2"><circle class="eq-bar" cx="17" cy="36" r="7"/><circle class="eq-bar" cx="32" cy="46" r="7"/><circle class="eq-bar" cx="47" cy="22" r="7"/></g></symbol></svg>';
        $html .= '<div class="container clients__inner">';
        $html .= $this->carousel('corporate', 'corporateHeading', 'corporateSubtitle');
        $html .= $this->carousel('individual', 'individualHeading', 'individualSubtitle');
        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    private function carousel(string $type, string $headingAttr, string $subtitleAttr): string
    {
        $clients = $this->query($type);
        $headingId = $type === 'corporate' ? 'corporateTitle' : 'individualTitle';
        $contentKeyPrefix = $type; // 'corporateTitle'/'corporateSubtitle' or 'individualTitle'/'individualSubtitle'
        $heading = $this->attribute($headingAttr, $contentKeyPrefix . 'Title');
        $subtitle = $this->attribute($subtitleAttr, $contentKeyPrefix . 'Subtitle');

        $html = '<header class="clients__head' . ($type === 'individual' ? ' clients__head--indiv' : '') . ' reveal">';
        $html .= '<h2 class="section-title" id="' . $headingId . '">' . wp_kses_post($heading) . '</h2>';
        $html .= '<p class="section-subtitle">' . wp_kses_post($subtitle) . '</p></header>';

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
     * client name stays available to assistive tech via aria-label. When an editor sets a gallery
     * (`Admin\ClientMediaMetaBox`, a mix of images and video links/uploads), the tile opens it in the
     * site-wide media lightbox (perego-theme/media-lightbox) as a `data-gallery` list — the lightbox
     * already resolves each item's type (image vs YouTube/video) itself. Falls back to the featured
     * image (single logo) when there's no gallery; stays non-interactive with neither.
     */
    private function corporateCard(\WP_Post $client): string
    {
        $title = (string) get_the_title($client);
        // Media is language-neutral and Polylang does not copy meta to translations, so an Arabic
        // client reads its linked English record's gallery — otherwise the AR card renders inert.
        $gallery = ClientPostType::sanitizeGallery(TranslatedMeta::value($client, ClientPostType::META_GALLERY));
        $galleryUrls = array_filter(array_map([$this, 'galleryItemUrl'], $gallery));

        if ($galleryUrls !== []) {
            $trigger = ' data-gallery="' . esc_attr(implode(',', $galleryUrls)) . '"';
        } else {
            $logoUrl = $this->thumbnailUrl($client);
            $trigger = $logoUrl !== '' ? ' data-image="' . esc_url($logoUrl) . '"' : '';
        }

        $html = '<button type="button" class="corp-card" role="listitem" aria-label="' . esc_attr($title) . '"' . $trigger . '>';
        $html .= '<svg class="eq-icon" viewBox="0 0 64 64" aria-hidden="true"><use href="#eq"></use></svg>';
        $html .= '</button>';

        return $html;
    }

    /**
     * The post whose featured image this card should show: the client itself, or its linked English
     * record when the translation has none of its own.
     *
     * Polylang gives a translation its own thumbnail slot and leaves it empty, so on this install 27
     * of 31 Arabic clients have no featured image while their English counterparts do. Without this,
     * the Arabic cards render with no logo at all.
     */
    private function thumbnailSourceId(\WP_Post $client): int
    {
        if (has_post_thumbnail($client->ID)) {
            return $client->ID;
        }

        $enId = TranslatedMeta::englishId($client);

        return ($enId !== 0 && has_post_thumbnail($enId)) ? $enId : 0;
    }

    /** The card logo's URL, following the English fallback; empty when neither post has one. */
    private function thumbnailUrl(\WP_Post $client): string
    {
        $id = $this->thumbnailSourceId($client);

        return $id === 0 ? '' : (string) get_the_post_thumbnail_url($id, 'large');
    }

    /** The card thumbnail's `<img>`, following the English fallback; empty when neither post has one. */
    private function thumbnailHtml(\WP_Post $client): string
    {
        $id = $this->thumbnailSourceId($client);

        return $id === 0 ? '' : (string) get_the_post_thumbnail($id, 'medium', ['loading' => 'lazy', 'alt' => '']);
    }

    /** @param array{type:string,id:int,url:string} $item */
    private function galleryItemUrl(array $item): string
    {
        if ($item['type'] === 'image') {
            return $item['id'] > 0 ? (string) wp_get_attachment_image_url($item['id'], 'large') : '';
        }

        return $item['url'];
    }

    /**
     * Individual: a wide info-plus-thumbnail card (handoff `.indiv-card`) — name/stat text beside a
     * thumbnail. `_perego_client_video_type` decides the card's single action (handoff C-04: a card
     * must never both open the lightbox AND navigate): `embed`/`upload` render a <button> lightbox
     * trigger with the handoff's `.play-btn` affordance (perego-theme/media-lightbox resolves
     * YouTube-style embeds and direct video files itself); `external` is a plain link opening in a
     * new tab — no `data-video` and no play button (the play affordance is reserved for approved
     * in-lightbox media). Without any video the card is a plain non-interactive tile.
     */
    private function individualCard(\WP_Post $client): string
    {
        $title = (string) get_the_title($client);
        // MEDIA falls back to the linked English record, because Polylang does not copy meta to
        // translations and a video/thumbnail is the same asset in either language. Without this the
        // Arabic carousel rendered inert `<div>`s that looked right and did nothing when clicked.
        $thumb = $this->thumbnailHtml($client);
        $videoUrl = TranslatedMeta::string($client, ClientPostType::META_VIDEO_URL);
        $videoType = ClientPostType::sanitizeVideoType(TranslatedMeta::string($client, ClientPostType::META_VIDEO_TYPE));
        // COPY deliberately does NOT fall back. The subtitle and statistic are editorial text, not
        // assets: inheriting them would print the English wording on an Arabic card (the live EN
        // records carry "intertainment show"), which is the language leak this whole pass exists to
        // remove. An untranslated card shows no subtitle — the prompt to enter Arabic copy — while
        // still opening its video.
        $sub = (string) get_post_meta($client->ID, ClientPostType::META_SUB, true);
        $stat = (string) get_post_meta($client->ID, ClientPostType::META_STAT, true);
        $opensLightbox = $videoUrl !== '' && $videoType !== 'external';

        if ($opensLightbox) {
            $html = '<button type="button" class="indiv-card" data-video="' . esc_attr($videoUrl) . '" role="listitem">';
        } elseif ($videoUrl !== '') {
            $html = '<a class="indiv-card" href="' . esc_url($videoUrl) . '" target="_blank" rel="noopener" role="listitem">';
        } else {
            $html = '<div class="indiv-card" role="listitem">';
        }

        $html .= '<div class="indiv-card__info"><h3 class="indiv-card__title">' . esc_html($title) . '</h3>';
        if ($sub !== '') {
            $html .= '<p class="indiv-card__sub">' . esc_html($sub) . '</p>';
        }
        if ($stat !== '') {
            $html .= '<p class="indiv-card__stat">' . wp_kses($stat, ['strong' => []]) . '</p>';
        }
        $html .= '</div>'; // .client-card__info

        $html .= '<div class="indiv-card__thumb">';
        $html .= $thumb !== '' ? $thumb : '<img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/client-review-crop.png') . '" alt="" loading="lazy" />';
        // The play badge is an editor choice (client request 2026-07-28). It is purely decorative —
        // `aria-hidden`, and the card is already a button — so hiding it changes how the row looks,
        // never whether the video opens.
        if ($opensLightbox && $this->showsPlayIcon()) {
            $html .= '<span class="play-btn" aria-hidden="true"></span>';
        }
        $html .= '</div>'; // .client-card__thumb

        if ($opensLightbox) {
            $html .= '</button>';
        } elseif ($videoUrl !== '') {
            $html .= '</a>';
        } else {
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * The current-locale variant of a bilingual attribute pair (`{key}En`/`{key}Ar` — matches
     * `FooterCareersRenderer`'s convention for the same shared-template problem) if the editor set
     * one, else the locale-aware `ClientsContent` seed.
     */
    private function attribute(string $attrKey, string $contentKey): string
    {
        $suffix = $this->locale === 'ar' ? 'Ar' : 'En';
        $value = trim((string) ($this->attributes[$attrKey . $suffix] ?? ''));

        return $value !== '' ? $value : $this->content->get($contentKey);
    }

    /**
     * Whether the individual cards wear the play badge.
     *
     * Defaults to true so every page saved before the toggle existed keeps the badge it already
     * shows — an unset attribute must mean "as it was", not "off".
     */
    private function showsPlayIcon(): bool
    {
        return ! array_key_exists('showPlayIcon', $this->attributes)
            || (bool) $this->attributes['showPlayIcon'];
    }

    private function arrowSvg(string $direction): string
    {
        $path = $direction === 'previous' ? 'M15 4 7 12l8 8' : 'M9 4l8 8-8 8';

        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="' . $path . '" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    /** @return list<\WP_Post> */
    private function query(string $type): array
    {
        $mode = (string) ($this->attributes[$type . 'Mode'] ?? 'automatic');
        $selectedIds = $this->positiveIds($this->attributes[$type . 'Order'] ?? []);
        $excludedIds = $this->positiveIds($this->attributes[$type . 'ExcludeIds'] ?? []);
        $automatic = $this->automaticClients($type);

        if ($mode === 'automatic') {
            return $this->excludeClients($automatic, $excludedIds);
        }

        $selected = $this->selectedClients($selectedIds);
        if ($mode === 'manual') {
            return array_slice($selected, 0, $this->maxFor($type));
        }

        $selectedIds = array_map(static fn (\WP_Post $post): int => $post->ID, $selected);
        $remaining = array_filter(
            $this->excludeClients($automatic, $excludedIds),
            static fn (\WP_Post $post): bool => ! in_array($post->ID, $selectedIds, true)
        );

        return array_slice([...$selected, ...$remaining], 0, $this->maxFor($type));
    }

    /** @return list<\WP_Post> */
    private function automaticClients(string $type): array
    {
        $q = new WP_Query([
            'post_type' => ClientPostType::POST_TYPE,
            'posts_per_page' => $this->maxFor($type),
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

    /** @return list<\WP_Post> */
    private function selectedClients(array $ids): array
    {
        if (! function_exists('get_post')) {
            return [];
        }

        $posts = [];
        foreach ($ids as $id) {
            $localizedId = function_exists('pll_get_post') ? (int) pll_get_post($id, $this->locale) : $id;
            $post = get_post($localizedId ?: $id);
            if ($post instanceof \WP_Post && $post->post_type === ClientPostType::POST_TYPE && $post->post_status === 'publish') {
                $posts[] = $post;
            }
        }

        return $posts;
    }

    /** @param list<\WP_Post> $clients @param list<int> $excludedIds @return list<\WP_Post> */
    private function excludeClients(array $clients, array $excludedIds): array
    {
        if ($excludedIds === []) {
            return $clients;
        }

        return array_values(array_filter($clients, function (\WP_Post $post) use ($excludedIds): bool {
            $englishId = function_exists('pll_get_post') ? (int) pll_get_post($post->ID, 'en') : 0;

            return array_intersect([$post->ID, $englishId], $excludedIds) === [];
        }));
    }

    private function maxFor(string $type): int
    {
        return $type === 'corporate' ? self::CORP_MAX : self::INDIV_MAX;
    }

    /** @param mixed $ids @return list<int> */
    private function positiveIds($ids): array
    {
        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(static fn ($id): int => abs((int) $id), $ids))));
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
