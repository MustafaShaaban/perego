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

    // Homepage services-teaser card presentation (spec 012 T003). These are teaser-specific — the short
    // card label and card image differ from the post title / featured image — so the teaser is an editable
    // projection of the Service. Unset meta makes the renderer fall back to the HomeContent seed (no
    // visual change), so registering them is safe before any seeding.
    public const META_TEASER_LABEL = '_perego_teaser_label';
    public const META_TEASER_IMAGE_ID = '_perego_teaser_image_id';
    public const META_TEASER_ALT = '_perego_teaser_alt';

    /** Service-single Selected Work source: existing records default to category-driven automatic mode. */
    public const META_PORTFOLIO_MODE = '_perego_service_portfolio_mode';

    /** Explicit Project IDs for manual mode, or the first cards in hybrid mode. */
    public const META_PORTFOLIO_PROJECT_IDS = '_perego_service_portfolio_project_ids';

    /** Project IDs omitted from automatic/hybrid category results. */
    public const META_PORTFOLIO_EXCLUDE_IDS = '_perego_service_portfolio_exclude_ids';

    public const PORTFOLIO_MODES = ['automatic', 'manual', 'hybrid'];

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
            self::META_TEASER_LABEL => [
                'type' => 'string',
                'single' => true,
                'default' => '',
                'show_in_rest' => true,
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback' => [self::class, 'authEdit'],
            ],
            self::META_TEASER_IMAGE_ID => [
                'type' => 'integer',
                'single' => true,
                'default' => 0,
                'show_in_rest' => true,
                'sanitize_callback' => 'absint',
                'auth_callback' => [self::class, 'authEdit'],
            ],
            self::META_TEASER_ALT => [
                'type' => 'string',
                'single' => true,
                'default' => '',
                'show_in_rest' => true,
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback' => [self::class, 'authEdit'],
            ],
            self::META_PORTFOLIO_MODE => [
                'type' => 'string',
                'single' => true,
                'default' => 'automatic',
                'show_in_rest' => true,
                'sanitize_callback' => [self::class, 'sanitizePortfolioMode'],
                'auth_callback' => [self::class, 'authEdit'],
            ],
            self::META_PORTFOLIO_PROJECT_IDS => [
                'type' => 'array',
                'single' => true,
                'default' => [],
                'show_in_rest' => ['schema' => ['type' => 'array', 'items' => ['type' => 'integer']]],
                'sanitize_callback' => [self::class, 'sanitizeIntList'],
                'auth_callback' => [self::class, 'authEdit'],
            ],
            self::META_PORTFOLIO_EXCLUDE_IDS => [
                'type' => 'array',
                'single' => true,
                'default' => [],
                'show_in_rest' => ['schema' => ['type' => 'array', 'items' => ['type' => 'integer']]],
                'sanitize_callback' => [self::class, 'sanitizeIntList'],
                'auth_callback' => [self::class, 'authEdit'],
            ],
        ];
    }

    public static function authEdit(): bool
    {
        return function_exists('current_user_can') && current_user_can('edit_posts');
    }

    public static function sanitizePortfolioMode($value): string
    {
        $mode = is_string($value) ? $value : '';

        return in_array($mode, self::PORTFOLIO_MODES, true) ? $mode : 'automatic';
    }

    /** @param mixed $value @return list<int> */
    public static function sanitizeIntList($value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(static fn ($id): int => abs((int) $id), $value))));
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
            // Archive disabled (spec 020): the 4 service singles at /services/<slug> are the canonical
            // service pages; the auto-generated /services/ archive is retired → 404. Singles keep working
            // because they come from `rewrite.slug`, not `has_archive`.
            'has_archive' => false,
            'menu_icon' => 'dashicons-screenoptions',
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'],
            'rewrite' => ['slug' => 'services'],
            'show_in_rest' => true,
        ];
    }
}
