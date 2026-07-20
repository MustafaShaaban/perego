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

    public const META_STAT = '_perego_client_stat';

    /** The individual card's middle subtitle line (handoff `.indiv-card__sub`, e.g. "intertainment show"). */
    public const META_SUB = '_perego_client_sub';

    public const META_VIDEO_URL = '_perego_client_video_url';

    /** How the individual-card video plays: inline embed, an uploaded file, or a plain external link. */
    public const META_VIDEO_TYPE = '_perego_client_video_type';

    /** A corporate client's lightbox gallery: a mix of images and video links/uploads. */
    public const META_GALLERY = '_perego_client_gallery';

    /** Allowed `META_VIDEO_TYPE` values; `embed` also covers legacy rows saved before this field existed. */
    public const VIDEO_TYPES = ['embed', 'upload', 'external'];

    /** Allowed `META_GALLERY` item `type` values. */
    public const GALLERY_ITEM_TYPES = ['image', 'video'];

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
     * seed-only `update_post_meta`: the homepage-carousel statistic, the individual card's video source
     * (URL + how it should open — inline embed, an uploaded file, or an external link in a new tab), and
     * the corporate card's lightbox gallery (a mix of images and video links/uploads). Edited via
     * `Admin\ClientMediaMetaBox` (gallery + video) and `Admin\PostMetaBoxes` (statistic).
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
            self::META_SUB => [
                'type' => 'string',
                'single' => true,
                'default' => '',
                'show_in_rest' => true,
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback' => [self::class, 'authEdit'],
            ],
            self::META_VIDEO_URL => [
                'type' => 'string',
                'single' => true,
                'default' => '',
                'show_in_rest' => true,
                'sanitize_callback' => 'esc_url_raw',
                'auth_callback' => [self::class, 'authEdit'],
            ],
            self::META_VIDEO_TYPE => [
                'type' => 'string',
                'single' => true,
                'default' => 'embed',
                'show_in_rest' => true,
                'sanitize_callback' => [self::class, 'sanitizeVideoType'],
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

    public static function sanitizeVideoType(string $raw): string
    {
        return in_array($raw, self::VIDEO_TYPES, true) ? $raw : 'embed';
    }

    /**
     * The individual-card statistic permits only inline `<strong>` (the handoff bolds the number, e.g.
     * `<strong>+1M</strong> views`) — everything else is stripped, so it is safe to render with a
     * matching `wp_kses` in the block.
     */
    public static function sanitizeStat(string $raw): string
    {
        return wp_kses($raw, ['strong' => []]);
    }

    /**
     * Drops malformed rows rather than rejecting the whole save: an `image` row needs a positive
     * attachment id, a `video` row needs a non-empty URL once escaped.
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

            if ($type === 'image') {
                $id = absint($item['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $clean[] = ['type' => 'image', 'id' => $id, 'url' => ''];
            } else {
                $url = esc_url_raw((string) ($item['url'] ?? ''));
                if ($url === '') {
                    continue;
                }
                $clean[] = ['type' => 'video', 'id' => 0, 'url' => $url];
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
            'hierarchical' => false,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'client-type'],
        ];
    }
}
