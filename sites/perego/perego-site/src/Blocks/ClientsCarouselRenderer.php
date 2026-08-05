<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use Corex\Assets\Image;
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
 *
 * 2026-07-28: what a card DOES is now the editor's explicit choice — `_perego_client_behavior` is one
 * of `none` (an inert tile, the default), `lightbox` (opens the client's gallery) or `link` (navigates
 * to a `LinkTarget` destination). It used to be inferred: a corporate tile opened a lightbox if it
 * happened to have a gallery and otherwise opened its own logo image, and an individual card's action
 * came from a `video_type` enum. Both inferences are gone — a tile is interactive only when its
 * behaviour says so, and both card types now share one behaviour model and one gallery.
 */
final class ClientsCarouselRenderer
{
    /** Corporate is a dense 2-row logo grid (10 per row at desktop), so it carries far more tiles than the wide individual cards. */
    private const CORP_MAX = 20;

    private const INDIV_MAX = 12;

    /**
     * What an individual card's body may contain.
     *
     * The card element is itself a `<button>` or an `<a>`, so anything interactive nested inside it is
     * a real accessibility defect (a link inside a button has no sane keyboard or screen-reader
     * behaviour) as well as invalid HTML. Editors write the body in the normal editor, so this is the
     * gate that keeps a stray link, embed or media block out of the card.
     */
    private const CONTENT_TAGS = [
        'p' => [],
        'strong' => [],
        'em' => [],
        'b' => [],
        'i' => [],
        'br' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'span' => [],
    ];

    /**
     * @param array<string,mixed> $attributes
     * @param LinkTarget|null     $linkTarget resolves a `link`-behaviour client to an href; null keeps
     *                                        the renderer constructible without a language driver, in
     *                                        which case a custom URL is used verbatim and a dynamic
     *                                        link falls back to its stored URL
     */
    public function __construct(
        private readonly ClientsContent $content,
        private readonly string $locale = 'en',
        private readonly array $attributes = [],
        private readonly ?LinkTarget $linkTarget = null
    ) {
    }

    public function render(): string
    {
        $html = '<section class="clients" id="clients" aria-labelledby="corporateTitle">';
        $html .= '<div class="wavy-bg" aria-hidden="true">'
            . Image::picture('images/wavy-corners.png', [
                'base' => 'perego-theme',
                'alt' => '',
                'width' => 2560,
                'height' => 1440,
            ])
            . '</div>';
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
            ? 'id="corporateTrack" tabindex="0" role="group" aria-label="' . esc_attr__('Corporate client logos', 'perego-site') . '"'
            : 'id="individualTrack" tabindex="0" role="group" aria-label="' . esc_attr__('Individual clients', 'perego-site') . '"';

        $html .= '<div class="' . $sliderClass . ' reveal"><button type="button" class="corp-arrow corp-arrow--prev" aria-label="' . esc_attr($previousLabel) . '">' . $this->arrowSvg('previous') . '</button>';
        $html .= '<div class="' . $trackClass . '" ' . $trackAttributes . '>';
        foreach ($clients as $client) {
            $html .= $type === 'corporate' ? $this->corporateCard($client) : $this->individualCard($client);
        }
        $html .= '</div><button type="button" class="corp-arrow corp-arrow--next" aria-label="' . esc_attr($nextLabel) . '">' . $this->arrowSvg('next') . '</button></div>';

        return $html;
    }

    /**
     * Corporate: a small square tile (handoff `.corp-card`) — the client's logo, no visible name. The
     * client name (the post title) stays available to assistive tech via aria-label, which is also why
     * the logo itself is `alt=""`: repeating the name would announce it twice.
     *
     * The equalizer glyph is a PLACEHOLDER, shown only until a logo is uploaded — so a client without
     * artwork still renders a finished-looking tile instead of an empty box.
     *
     * What the tile does is the editor's explicit choice, not an inference from which fields happen to
     * be filled in — see {@see cardTags()}.
     */
    private function corporateCard(\WP_Post $client): string
    {
        $title = (string) get_the_title($client);
        $tags = $this->cardTags($client, 'corp-card', ' aria-label="' . esc_attr($title) . '"');
        // Follows the English fallback: Polylang gives a translation its own empty thumbnail slot, so
        // an Arabic tile would otherwise drop back to the placeholder its English twin never shows.
        $logo = $this->thumbnailHtml($client, 'medium', ['class' => 'corp-card__logo']);

        return $tags['open']
            . ($logo !== '' ? $logo : '<svg class="eq-icon" viewBox="0 0 64 64" aria-hidden="true"><use href="#eq"></use></svg>')
            . $tags['close'];
    }

    /**
     * The card's wrapping element for the behaviour its editor chose (`_perego_client_behavior`).
     *
     * - `lightbox` — a `<button>` carrying the media-lightbox trigger for the client's gallery,
     *   falling back to the featured image so a client that chose Lightbox but has not filled the
     *   gallery in yet still opens its own artwork rather than nothing.
     * - `link` — an `<a>` to the resolved destination, honouring the link's own new-tab choice.
     * - `none` — an inert `<div>`. This is the default, and it is exactly as static as it says:
     *   the tile carries no trigger attribute at all.
     *
     * A behaviour whose data is missing entirely (Lightbox with no gallery **and** no featured image,
     * Link with no destination) degrades to the inert element, because a control that visibly invites
     * a click and then does nothing is worse than a plain tile.
     *
     * @return array{open:string, close:string, opensLightbox:bool}
     */
    private function cardTags(\WP_Post $client, string $class, string $extraAttributes = ''): array
    {
        $tags = match ($this->behavior($client)) {
            'lightbox' => $this->lightboxTags($client, $class, $extraAttributes),
            'link' => $this->linkTags($client, $class, $extraAttributes),
            default => null,
        };

        // The `?? ` is the degrade-to-inert rule: a behaviour whose data is missing produces null
        // above, and lands here rather than rendering a control that does nothing.
        return $tags ?? [
            'open' => '<div class="' . $class . '" role="img"' . $extraAttributes . '>',
            'close' => '</div>',
            'opensLightbox' => false,
        ];
    }

    /**
     * A lightbox card, or null when there is no media to open.
     *
     * @return array{open:string, close:string, opensLightbox:bool}|null
     */
    private function lightboxTags(\WP_Post $client, string $class, string $extraAttributes): ?array
    {
        // Media is language-neutral and Polylang does not copy meta to translations, so an Arabic
        // client reads its linked English record's gallery — otherwise the AR card renders inert.
        $trigger = LightboxTrigger::attribute(
            (array) TranslatedMeta::value($client, ClientPostType::META_GALLERY),
            $this->thumbnailUrl($client)
        );

        if ($trigger === '') {
            return null;
        }

        return [
            'open' => '<button type="button" class="' . $class . '"' . $extraAttributes . $trigger . '>',
            'close' => '</button>',
            'opensLightbox' => true,
        ];
    }

    /**
     * A navigating card, or null when the link has no destination.
     *
     * @return array{open:string, close:string, opensLightbox:bool}|null
     */
    private function linkTags(\WP_Post $client, string $class, string $extraAttributes): ?array
    {
        $link = $this->link($client);
        $href = $this->linkTarget?->hrefIfSet($link) ?? (string) $link['href'];

        if ($href === '') {
            return null;
        }

        $target = $this->linkTarget?->targetAttributes($link)
            ?? (! empty($link['openInNewTab']) ? ' target="_blank" rel="noopener"' : '');

        return [
            'open' => '<a class="' . $class . '" href="' . esc_url($href) . '"' . $target . $extraAttributes . '>',
            'close' => '</a>',
            'opensLightbox' => false,
        ];
    }

    /** The card's chosen behaviour, following the English fallback because it describes media, not copy. */
    private function behavior(\WP_Post $client): string
    {
        return ClientPostType::sanitizeBehavior(
            TranslatedMeta::string($client, ClientPostType::META_BEHAVIOR)
        );
    }

    /**
     * The `link` behaviour's destination in the shape `LinkTarget` expects. Language-neutral like the
     * gallery: `LinkTarget` localizes an internal path and resolves a chosen record in the current
     * locale itself, so an Arabic card follows its English record's choice to the Arabic page.
     *
     * @return array<string, mixed>
     */
    private function link(\WP_Post $client): array
    {
        return [
            'href' => TranslatedMeta::string($client, ClientPostType::META_LINK_URL),
            'linkKind' => ClientPostType::sanitizeLinkKind(
                TranslatedMeta::string($client, ClientPostType::META_LINK_KIND)
            ),
            'postId' => (int) TranslatedMeta::value($client, ClientPostType::META_LINK_POST_ID),
            'openInNewTab' => (bool) TranslatedMeta::value($client, ClientPostType::META_LINK_NEW_TAB),
        ];
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

    /**
     * The card thumbnail's `<img>`, following the English fallback; empty when neither post has one.
     *
     * `alt=""` on both card types: the individual card prints the client name as a heading beside the
     * image, and the corporate tile carries it as the element's own aria-label, so describing the
     * image would announce the same name twice.
     *
     * @param array<string, string> $attributes merged over the shared defaults
     */
    private function thumbnailHtml(\WP_Post $client, string $size = 'medium', array $attributes = []): string
    {
        $id = $this->thumbnailSourceId($client);

        return $id === 0 ? '' : (string) get_the_post_thumbnail(
            $id,
            $size,
            array_merge(['loading' => 'lazy', 'alt' => ''], $attributes)
        );
    }

    /**
     * Individual: a wide info-plus-thumbnail card (handoff `.indiv-card`) — title, subtitle and body
     * copy beside a thumbnail. The card's behaviour decides its single action (handoff C-04: a card
     * must never both open the lightbox AND navigate) — see {@see cardTags()}.
     */
    private function individualCard(\WP_Post $client): string
    {
        $title = (string) get_the_title($client);
        $tags = $this->cardTags($client, 'indiv-card');
        // MEDIA falls back to the linked English record, because Polylang does not copy meta to
        // translations and a thumbnail is the same asset in either language. Without this the
        // Arabic carousel rendered inert `<div>`s that looked right and did nothing when clicked.
        $thumb = $this->thumbnailHtml($client);
        // COPY deliberately does NOT fall back. The subtitle and body are editorial text, not assets:
        // inheriting them would print the English wording on an Arabic card, which is the language
        // leak this whole pass exists to remove. An untranslated card shows no subtitle — the prompt
        // to enter Arabic copy — while still opening its media.
        $subtitle = (string) get_post_meta($client->ID, ClientPostType::META_STAT, true);

        $html = $tags['open'];
        $html .= '<div class="indiv-card__info"><h3 class="indiv-card__title">' . esc_html($title) . '</h3>';
        if ($subtitle !== '') {
            $html .= '<p class="indiv-card__sub">' . wp_kses($subtitle, ['strong' => []]) . '</p>';
        }
        $html .= $this->cardContent($client);
        $html .= '</div>'; // .indiv-card__info

        $html .= '<div class="indiv-card__thumb">';
        $html .= $thumb !== '' ? $thumb : '<img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/client-review-crop.png') . '" alt="" loading="lazy" />';
        // The play badge is a per-client editor choice (client request 2026-07-28). It is purely
        // decorative — `aria-hidden`, and the card is already a button — so hiding it changes how the
        // row looks, never whether the media opens.
        if ($tags['opensLightbox'] && $this->showsPlayIcon($client)) {
            $html .= '<span class="play-btn" aria-hidden="true"></span>';
        }
        $html .= '</div>'; // .indiv-card__thumb

        return $html . $tags['close'];
    }

    /**
     * The card's body copy — the client post's own editor content, reduced to the non-interactive
     * subset a card may safely contain ({@see CONTENT_TAGS}).
     */
    private function cardContent(\WP_Post $client): string
    {
        $raw = (string) $client->post_content;

        if (trim($raw) === '') {
            return '';
        }

        $rendered = function_exists('do_blocks') ? (string) do_blocks($raw) : $raw;
        $safe = trim(wp_kses($rendered, self::CONTENT_TAGS));

        return $safe === '' ? '' : '<div class="indiv-card__content">' . $safe . '</div>';
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
     * Whether THIS card wears the play badge.
     *
     * Was one toggle on the carousel block governing every card at once; it is now the client's own
     * setting, so a row can mix badged and unbadged cards. The stored flag is the inverse — see
     * `ClientPostType::META_HIDE_PLAY_ICON` for why — so an absent value keeps the badge.
     */
    private function showsPlayIcon(\WP_Post $client): bool
    {
        return ! ClientPostType::sanitizeBool(
            TranslatedMeta::value($client, ClientPostType::META_HIDE_PLAY_ICON)
        );
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
