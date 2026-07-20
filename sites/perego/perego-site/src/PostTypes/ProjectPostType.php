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

    public const META_CLIENT = '_perego_client';
    public const META_YEAR = '_perego_year';
    public const META_ROLE = '_perego_role';
    public const META_DELIVERABLES = '_perego_deliverables';
    public const META_GALLERY = '_perego_gallery_attachment_ids';
    public const META_SITE_TYPE = '_perego_site_type';
    public const META_SITE_URL = '_perego_site_url';
    public const META_VIDEO_URL = '_perego_video_url';

    /**
     * The website-showcase filter types (handoff service-website-making.html web-filters), for
     * web-category projects. Values are the handoff's data-filter/data-category enum; anything
     * else sanitizes to '' (untyped — shown under "All" only).
     *
     * @var list<string>
     */
    public const SITE_TYPES = ['ecommerce', 'corporate', 'landing', 'webapp', 'portfolio'];

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

        foreach ($this->metaArgs() as $key => $args) {
            register_post_meta(self::POST_TYPE, $key, $args);
        }
    }

    /**
     * Structured project metadata registered explicitly (REST schema + sanitization + auth) so the fields
     * are first-class — editable through proper controls and exposed to the REST API — rather than
     * seed-only `update_post_meta`. Keys are protected (leading `_`), so each carries an auth_callback.
     *
     * @return array<string, array<string, mixed>>
     */
    public function metaArgs(): array
    {
        $text = static fn (): array => [
            'type' => 'string',
            'single' => true,
            'default' => '',
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback' => [self::class, 'authEdit'],
        ];

        return [
            self::META_CLIENT => $text(),
            self::META_YEAR => $text(),
            self::META_ROLE => $text(),
            self::META_DELIVERABLES => $text(),
            self::META_SITE_TYPE => array_merge($text(), [
                'sanitize_callback' => [self::class, 'sanitizeSiteType'],
            ]),
            self::META_SITE_URL => array_merge($text(), [
                'sanitize_callback' => 'esc_url_raw',
            ]),
            self::META_VIDEO_URL => array_merge($text(), [
                'sanitize_callback' => 'esc_url_raw',
            ]),
            self::META_GALLERY => [
                'type' => 'array',
                'single' => true,
                'show_in_rest' => [
                    'schema' => ['type' => 'array', 'items' => ['type' => 'integer']],
                ],
                'sanitize_callback' => [self::class, 'sanitizeIntList'],
                'auth_callback' => [self::class, 'authEdit'],
            ],
        ];
    }

    /**
     * Authorize protected-meta edits. Mirrors the CPT's own edit capability.
     */
    public static function authEdit(): bool
    {
        return function_exists('current_user_can') && current_user_can('edit_posts');
    }

    /**
     * Whitelist a site-type value to the fixed showcase-filter enum; anything else becomes ''.
     *
     * @param mixed $value
     */
    public static function sanitizeSiteType($value): string
    {
        $value = is_string($value) ? strtolower(trim($value)) : '';

        return in_array($value, self::SITE_TYPES, true) ? $value : '';
    }

    /**
     * Coerce a gallery value to a clean list of positive attachment IDs.
     *
     * @param mixed $value
     * @return list<int>
     */
    public static function sanitizeIntList($value): array
    {
        $ids = [];
        foreach ((array) $value as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
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
