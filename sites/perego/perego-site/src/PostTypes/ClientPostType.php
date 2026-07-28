<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\PostTypes;

defined('ABSPATH') || exit;

/**
 * Registers the `perego_client` custom post type + the `perego_client_type` taxonomy
 * (corporate / individual) — spec M6. Clients are editor-managed content shown in the homepage
 * carousels. Args exposed via pure methods for unit testing.
 */
final class ClientPostType
{
    public const POST_TYPE = 'perego_client';

    public const TAXONOMY = 'perego_client_type';

    /** The two client types, in display order. @var array<string, string> */
    public const TYPES = [
        'corporate' => 'Corporate',
        'individual' => 'Individual',
    ];

    /**
     * The individual card's line under the name (handoff `.indiv-card__sub`), which may bold part of
     * itself — the handoff's `<strong>+1M</strong> views`. Labelled "Subtitle" in the editor since
     * 2026-07-28; the key keeps its original `_stat` name so no stored value had to move.
     */
    public const META_STAT = '_perego_client_stat';

    /**
     * What the card DOES, chosen by the editor rather than inferred from which fields happen to be
     * filled in. `none` is a deliberately inert card; `lightbox` opens `META_GALLERY`; `link` follows
     * the `META_LINK_*` set. Applies to both client types.
     */
    public const META_BEHAVIOR = '_perego_client_behavior';

    /**
     * Whether an individual lightbox card SUPPRESSES the handoff's `.play-btn` badge. Per client since
     * 2026-07-28, where it used to be one toggle on the carousel block governing every card at once.
     *
     * Stored inverted on purpose. WordPress writes a `false` boolean to the meta table as `''`, which
     * reads back identically to "never set" — so a `show_play_icon` flag defaulting to true could
     * never actually be switched off. Defaulting to false and naming it `hide` makes the only value
     * worth storing the one the editor explicitly chose, and an absent value keeps the badge the card
     * already shows. The editor UI still presents this as a positive "Show play icon" switch.
     */
    public const META_HIDE_PLAY_ICON = '_perego_client_hide_play_icon';

    /** The `link` behaviour's destination — the flat `Blocks\LinkTarget` set, `_perego_client_link` prefixed. */
    public const META_LINK_URL = '_perego_client_link_url';

    public const META_LINK_KIND = '_perego_client_link_kind';

    /** Only the picker needs this — it remembers which type the chosen record came from. */
    public const META_LINK_POST_TYPE = '_perego_client_link_post_type';

    public const META_LINK_POST_ID = '_perego_client_link_post_id';

    public const META_LINK_NEW_TAB = '_perego_client_link_new_tab';

    /** A client's lightbox gallery: a mix of images, uploaded videos and video links. */
    public const META_GALLERY = '_perego_client_gallery';

    /** Allowed `META_BEHAVIOR` values; `none` also covers every row saved before this field existed. */
    public const BEHAVIORS = ['none', 'lightbox', 'link'];

    /** Allowed `META_LINK_KIND` values — mirror of `Blocks\LinkTarget`'s two kinds. */
    public const LINK_KINDS = ['custom', 'dynamic'];

    /** Allowed `META_GALLERY` item `type` values. */
    public const GALLERY_ITEM_TYPES = ['image', 'video'];

    /**
     * How long the individual card's subtitle may be, in VISIBLE characters — `<strong>` markup does
     * not count against it.
     *
     * This is a WRITING GUIDE, not the thing protecting the layout. `.indiv-card__sub` is clamped to
     * two lines in CSS, which holds at every width; a character count cannot, because the box fits 24
     * characters per line at 1440px but only 15 at 1024px (the narrowest point, where three cards are
     * still side by side). The cap was 30 — two lines even at 1024 — and rose to 48 once the clamp made
     * that belt-and-braces rather than load-bearing (owner, 2026-07-28).
     */
    public const SUBTITLE_MAX_CHARS = 48;

    public function register(): void
    {
        register_post_type(self::POST_TYPE, $this->postTypeArgs());
        register_taxonomy(self::TAXONOMY, self::POST_TYPE, $this->taxonomyArgs());

        foreach ($this->metaArgs() as $key => $args) {
            register_post_meta(self::POST_TYPE, $key, $args);
        }
    }

    /**
     * Structured client metadata registered explicitly (REST schema + sanitization + auth) rather than
     * seed-only `update_post_meta`: the individual card's statistic line, the card's chosen behaviour
     * and the data that behaviour needs (a lightbox gallery of images/videos/video links, or a link
     * destination), and the per-client play-badge switch. All edited in one grouped sidebar panel
     * (`EditorPanels/`) — there is no classic meta box for clients any more.
     *
     * @return array<string, array<string, mixed>>
     */
    public function metaArgs(): array
    {
        return [
            self::META_STAT => [
                'type' => 'string',
                'single' => true,
                'default' => '',
                'show_in_rest' => true,
                'sanitize_callback' => [self::class, 'sanitizeStat'],
                'auth_callback' => [self::class, 'authEdit'],
            ],
            /*
             * Deliberately NO `default`. Since WP 5.5 a registered default is returned by
             * `get_post_meta()` for a key that was never written, which makes "unset" and "explicitly
             * none" indistinguishable — and that breaks two things at once: the migration's
             * already-done marker always sees a value, and `Content\TranslatedMeta` stops falling
             * back to the linked English record, so every Arabic card renders inert. An empty value
             * reaches `sanitizeBehavior()` and becomes `none` anyway, so the effective default is
             * unchanged. Same reasoning for the two below.
             */
            self::META_BEHAVIOR => [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => [self::class, 'sanitizeBehavior'],
                'auth_callback' => [self::class, 'authEdit'],
            ],
            self::META_HIDE_PLAY_ICON => [
                'type' => 'boolean',
                'single' => true,
                // False so a card saved before this field existed keeps the badge it already shows —
                // an unset value must mean "as it was", not "off". See the constant for why the flag
                // is stored inverted.
                'default' => false,
                'show_in_rest' => true,
                'sanitize_callback' => [self::class, 'sanitizeBool'],
                'auth_callback' => [self::class, 'authEdit'],
            ],
            self::META_LINK_URL => [
                'type' => 'string',
                'single' => true,
                'default' => '',
                'show_in_rest' => true,
                'sanitize_callback' => [self::class, 'sanitizeLinkUrl'],
                'auth_callback' => [self::class, 'authEdit'],
            ],
            self::META_LINK_KIND => [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => [self::class, 'sanitizeLinkKind'],
                'auth_callback' => [self::class, 'authEdit'],
            ],
            self::META_LINK_POST_TYPE => [
                'type' => 'string',
                'single' => true,
                'default' => '',
                'show_in_rest' => true,
                'sanitize_callback' => 'sanitize_key',
                'auth_callback' => [self::class, 'authEdit'],
            ],
            self::META_LINK_POST_ID => [
                'type' => 'integer',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => 'absint',
                'auth_callback' => [self::class, 'authEdit'],
            ],
            self::META_LINK_NEW_TAB => [
                'type' => 'boolean',
                'single' => true,
                'default' => false,
                'show_in_rest' => true,
                'sanitize_callback' => [self::class, 'sanitizeBool'],
                'auth_callback' => [self::class, 'authEdit'],
            ],
            self::META_GALLERY => [
                'type' => 'array',
                'single' => true,
                'default' => [],
                'show_in_rest' => [
                    'schema' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'type' => ['type' => 'string'],
                                'id' => ['type' => 'integer'],
                                'url' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
                'sanitize_callback' => [self::class, 'sanitizeGallery'],
                'auth_callback' => [self::class, 'authEdit'],
            ],
        ];
    }

    public static function authEdit(): bool
    {
        return function_exists('current_user_can') && current_user_can('edit_posts');
    }

    /** An unrecognised behaviour is an inert card, never an accidental action. */
    public static function sanitizeBehavior(string $raw): string
    {
        return in_array($raw, self::BEHAVIORS, true) ? $raw : 'none';
    }

    public static function sanitizeLinkKind(string $raw): string
    {
        return in_array($raw, self::LINK_KINDS, true) ? $raw : 'custom';
    }

    /**
     * `esc_url_raw` strips a bare in-site path's leading slash-relative form only when it cannot parse
     * it; anchors and relative routes are legitimate destinations here (`LinkTarget::customHref`
     * localizes them), so those pass through untouched and everything else is escaped as a URL.
     */
    public static function sanitizeLinkUrl(string $raw): string
    {
        $raw = trim($raw);

        if ($raw === '' || str_starts_with($raw, '#') || str_starts_with($raw, '/')) {
            return sanitize_text_field($raw);
        }

        return (string) esc_url_raw($raw);
    }

    /** @param mixed $raw */
    public static function sanitizeBool($raw): bool
    {
        return filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $raw;
    }

    /**
     * The individual-card subtitle permits only inline `<strong>` (the handoff bolds the number, e.g.
     * `<strong>+1M</strong> views`) — everything else is stripped, so it is safe to render with a
     * matching `wp_kses` in the block — and is capped at {@see SUBTITLE_MAX_CHARS} visible characters.
     *
     * The cap is a backstop, not the primary control: the editor panel counts characters live and stops
     * accepting them at the limit, so a person never reaches this. It exists for the REST API, WP-CLI,
     * imports and seeders, which can otherwise write a subtitle long enough to grow every card in the
     * carousel row.
     */
    public static function sanitizeStat(string $raw): string
    {
        return self::capSubtitle(wp_kses($raw, ['strong' => []]));
    }

    /**
     * Trim a subtitle to {@see SUBTITLE_MAX_CHARS} visible characters, counting text only and leaving
     * the `<strong>` markup intact — capping the raw string would spend the budget on tag characters
     * and could slice a tag in half. Any `<strong>` still open at the cut is closed, so the result
     * stays well-formed for the renderer's matching `wp_kses`.
     */
    private static function capSubtitle(string $html): string
    {
        if (mb_strlen(wp_strip_all_tags($html)) <= self::SUBTITLE_MAX_CHARS) {
            return $html;
        }

        $budget = self::SUBTITLE_MAX_CHARS;
        $out = '';
        $openStrong = false;

        foreach (preg_split('#(</?strong>)#i', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
            if (preg_match('#^</?strong>$#i', $part) === 1) {
                $openStrong = ! str_starts_with($part, '</');
                $out .= $part;
                continue;
            }

            $length = mb_strlen($part);
            if ($length <= $budget) {
                $out .= $part;
                $budget -= $length;
                continue;
            }

            $out .= mb_substr($part, 0, $budget);
            $budget = 0;
            break;
        }

        return rtrim($out) . ($openStrong ? '</strong>' : '');
    }

    /**
     * Drops malformed rows rather than rejecting the whole save: an `image` row needs a positive
     * attachment id, a `video` row needs a non-empty URL once escaped.
     *
     * A `video` row keeps its attachment id when it has one — an uploaded video is then still
     * resolvable after the file is regenerated or moved, while a pasted YouTube/Vimeo link simply
     * carries id 0. Rows are otherwise stored exactly as the editor ordered them.
     *
     * @param mixed $raw
     * @return list<array{type:string,id:int,url:string}>
     */
    public static function sanitizeGallery($raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $clean = [];
        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }
            $type = is_string($item['type'] ?? null) ? $item['type'] : '';
            if (! in_array($type, self::GALLERY_ITEM_TYPES, true)) {
                continue;
            }

            $id = absint($item['id'] ?? 0);

            if ($type === 'image') {
                if ($id <= 0) {
                    continue;
                }
                $clean[] = ['type' => 'image', 'id' => $id, 'url' => ''];
            } else {
                $url = esc_url_raw((string) ($item['url'] ?? ''));
                if ($url === '') {
                    continue;
                }
                $clean[] = ['type' => 'video', 'id' => $id, 'url' => $url];
            }
        }

        return $clean;
    }

    /** @return array<string, mixed> */
    public function postTypeArgs(): array
    {
        return [
            'labels' => [
                'name' => __('Clients', 'perego-site'),
                'singular_name' => __('Client', 'perego-site'),
                'add_new_item' => __('Add New Client', 'perego-site'),
                'edit_item' => __('Edit Client', 'perego-site'),
                'menu_name' => __('Clients', 'perego-site'),
            ],
            'public' => true,
            'has_archive' => false,
            'menu_icon' => 'dashicons-groups',
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'],
            'rewrite' => ['slug' => 'clients'],
            'show_in_rest' => true,
        ];
    }

    /** @return array<string, mixed> */
    public function taxonomyArgs(): array
    {
        return [
            'labels' => [
                'name' => __('Client Types', 'perego-site'),
                'singular_name' => __('Client Type', 'perego-site'),
            ],
            'public' => true,
            // Category-style terms preserve existing IDs/object assignments while making parent/child
            // structures and the native hierarchical editor selector available.
            'hierarchical' => true,
            'show_in_rest' => true,
            // Client type is the FIRST choice an editor makes and it decides which other fields exist,
            // so it is presented at the top of the grouped Client panel. Suppressing the default box
            // keeps it a single control in a single place rather than two that can disagree.
            'meta_box_cb' => false,
            'rewrite' => ['slug' => 'client-type'],
        ];
    }
}
