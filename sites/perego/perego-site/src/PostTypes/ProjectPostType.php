<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\PostTypes;

defined('ABSPATH') || exit;

/**
 * Registers the `project` custom post type + its `project_category` taxonomy (spec 003 / M3). The
 * four categories mirror the studio's four services (video · motion · design · web) — the same enum
 * the handoff CONTENT_MODEL.md defines. Post type + taxonomy args are exposed via pure
 * `postTypeArgs()` / `taxonomyArgs()` methods so they can be asserted in a unit test without a
 * booted WordPress; `register()` is the thin WordPress-facing wiring.
 */
final class ProjectPostType
{
    public const POST_TYPE = 'perego_project';

    public const TAXONOMY = 'perego_project_category';

    /**
     * The fixed category terms, in display order. Slugs are the enum from CONTENT_MODEL.md; the
     * labels match the four service names.
     *
     * @var array<string, string>
     */
    public const CATEGORIES = [
        'video' => 'Video Editing',
        'motion' => '2D Motion Graphics',
        'design' => 'Graphic Design',
        'web' => 'Website Making',
    ];

    public function register(): void
    {
        register_post_type(self::POST_TYPE, $this->postTypeArgs());
        register_taxonomy(self::TAXONOMY, self::POST_TYPE, $this->taxonomyArgs());
    }

    /**
     * @return array<string, mixed>
     */
    public function postTypeArgs(): array
    {
        return [
            'labels' => [
                'name' => __('Projects', 'perego-site'),
                'singular_name' => __('Project', 'perego-site'),
                'add_new_item' => __('Add New Project', 'perego-site'),
                'edit_item' => __('Edit Project', 'perego-site'),
                'menu_name' => __('Portfolio', 'perego-site'),
            ],
            'public' => true,
            'has_archive' => 'work',
            'menu_icon' => 'dashicons-portfolio',
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'],
            'rewrite' => ['slug' => 'work'],
            'show_in_rest' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function taxonomyArgs(): array
    {
        return [
            'labels' => [
                'name' => __('Services', 'perego-site'),
                'singular_name' => __('Service', 'perego-site'),
            ],
            'public' => true,
            'hierarchical' => false,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'work/service'],
        ];
    }
}
