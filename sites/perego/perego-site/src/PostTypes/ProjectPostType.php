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
    public const META_LOGO = '_perego_logo_id';

    /**
     * Which affordance the project's grid tile wears — the editor's explicit choice, not an inference
     * from which media the project happens to carry (owner, 2026-07-28).
     *
     * The tile's *trigger* is still derived from its media: what a tile opens and what it advertises
     * are different questions, and only the second one is editorial.
     */
    public const META_ICON = '_perego_project_icon';

    /** Allowed `META_ICON` values; `none` also covers every project saved before this field existed. */
    public const ICONS = ['none', 'play', 'gallery'];

    /**
     * Per-shape thumbnails. A project appears in tiles of six different aspect ratios, and
     * `.work-card img` is `object-fit: cover`, so one landscape featured image gets centre-cropped
     * into a 0.55:1 portrait slot and loses whatever mattered. These let an editor supply the crop.
     *
     * All optional — {@see ProjectThumbnails} falls back to the nearest shape and finally to the
     * featured image, so a project with none of them renders exactly as it does today.
     */
    public const META_THUMB_HERO = '_perego_thumb_hero';
    public const META_THUMB_BANNER = '_perego_thumb_banner';
    public const META_THUMB_TALL = '_perego_thumb_tall';
    public const META_THUMB_CARD = '_perego_thumb_card';

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
            // The client's logo, for web-category projects. The featured image stays the site
            // screenshot — the Website-Making showcase needs it — so the home Work grid needs a second
            // image to show a brand mark instead of a cropped screenshot. 0 means "not set".
            self::META_LOGO => [
                'type' => 'integer',
                'single' => true,
                'default' => 0,
                'show_in_rest' => true,
                'sanitize_callback' => 'absint',
                'auth_callback' => [self::class, 'authEdit'],
            ],
            self::META_GALLERY => [
                'type' => 'array',
                'single' => true,
                'show_in_rest' => [
                    'schema' => ['type' => 'array', 'items' => ['type' => 'integer']],
                ],
                'sanitize_callback' => [self::class, 'sanitizeIntList'],
                'auth_callback' => [self::class, 'authEdit'],
            ],
            /*
             * Deliberately NO `default` on the icon or the thumbnails below. Since WP 5.5 a registered
             * default is returned by `get_post_meta()` for a key that was never written, which makes
             * "unset" indistinguishable from "explicitly none/zero" — that breaks the migration's
             * already-done marker and stops the English-translation fallback, so an Arabic project
             * stops inheriting its English record's media. The same trap cost a debugging cycle on the
             * Client behaviour field; `sanitizeIcon()` turns an empty value into `none` regardless, so
             * the effective default is unchanged.
             */
            self::META_ICON => [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => [self::class, 'sanitizeIcon'],
                'auth_callback' => [self::class, 'authEdit'],
            ],
            ...$this->thumbnailMetaArgs(),
        ];
    }

    /**
     * The per-shape thumbnail metas — one attachment id each, all optional.
     *
     * @return array<string, array<string, mixed>>
     */
    private function thumbnailMetaArgs(): array
    {
        $args = [];

        foreach (ProjectThumbnails::metaKeys() as $key) {
            $args[$key] = [
                'type' => 'integer',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => 'absint',
                'auth_callback' => [self::class, 'authEdit'],
            ];
        }

        return $args;
    }

    /** An unrecognised icon means no icon — a tile never advertises something by accident. */
    public static function sanitizeIcon(string $raw): string
    {
        return in_array($raw, self::ICONS, true) ? $raw : 'none';
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
            // Existing service terms and assignments are retained; this only changes the editor and
            // taxonomy relationship model to support parent Service → child project-type structures.
            'hierarchical' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'work/service'],
        ];
    }
}
