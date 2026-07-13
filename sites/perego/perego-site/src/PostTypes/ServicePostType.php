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

    public function register(): void
    {
        register_post_type(self::POST_TYPE, $this->postTypeArgs());
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
