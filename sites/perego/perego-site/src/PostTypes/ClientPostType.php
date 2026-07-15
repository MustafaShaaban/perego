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
    public const META_VIDEO_URL = '_perego_client_video_url';

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
     * seed-only `update_post_meta`: the homepage-carousel statistic and the optional video URL that drives
     * the play icon + lightbox.
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
        ];
    }

    public static function authEdit(): bool
    {
        return function_exists('current_user_can') && current_user_can('edit_posts');
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
