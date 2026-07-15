<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\PostTypes;

defined('ABSPATH') || exit;

/**
 * Registers the `service` custom post type (spec 003 / M3, US3) — the studio's four fixed services,
 * each with its own single at `/services/<slug>` (the routes the header already links to) and a
 * `services` archive. Four fixed entries; the slugs match ProjectPostType::CATEGORIES + the header's
 * dropdown links. Args are exposed via a pure `postTypeArgs()` for unit testing.
 */
final class ServicePostType
{
    public const POST_TYPE = 'perego_service';

    /**
     * The four services in display order: slug => full name (from the handoff services content).
     *
     * @var array<string, string>
     */
    public const SERVICES = [
        'video-editing' => 'Video Editing & Post-Production',
        'motion-graphics' => '2D Motion Graphics & Animation',
        'graphic-design' => 'Graphic Design & Brand Identity',
        'website-making' => 'Website Making',
    ];

    public const META_SERVICE_SLUG = '_perego_service_slug';

    public function register(): void
    {
        register_post_type(self::POST_TYPE, $this->postTypeArgs());

        foreach ($this->metaArgs() as $key => $args) {
            register_post_meta(self::POST_TYPE, $key, $args);
        }
    }

    /**
     * Structured service metadata registered explicitly (REST schema + sanitization + auth) rather than
     * seed-only `update_post_meta`. `_perego_service_slug` is the canonical service key
     * (video/motion/design/web) the frontend and contact pre-selection resolve against.
     *
     * @return array<string, array<string, mixed>>
     */
    public function metaArgs(): array
    {
        return [
            self::META_SERVICE_SLUG => [
                'type' => 'string',
                'single' => true,
                'default' => '',
                'show_in_rest' => true,
                'sanitize_callback' => 'sanitize_key',
                'auth_callback' => [self::class, 'authEdit'],
            ],
        ];
    }

    public static function authEdit(): bool
    {
        return function_exists('current_user_can') && current_user_can('edit_posts');
    }

    /**
     * @return array<string, mixed>
     */
    public function postTypeArgs(): array
    {
        return [
            'labels' => [
                'name' => __('Services', 'perego-site'),
                'singular_name' => __('Service', 'perego-site'),
                'add_new_item' => __('Add New Service', 'perego-site'),
                'edit_item' => __('Edit Service', 'perego-site'),
                'menu_name' => __('Services', 'perego-site'),
            ],
            'public' => true,
            'has_archive' => 'services',
            'menu_icon' => 'dashicons-screenoptions',
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'],
            'rewrite' => ['slug' => 'services'],
            'show_in_rest' => true,
        ];
    }
}
