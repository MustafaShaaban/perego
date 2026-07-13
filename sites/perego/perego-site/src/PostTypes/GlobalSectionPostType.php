<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\PostTypes;

defined('ABSPATH') || exit;

/**
 * Registers the `perego_global_section` custom post type (spec Phase 5) — the editor-managed,
 * Polylang-translatable home for the site's repeated global copy (header content, footers, the
 * global project CTA, contact details, and 404 editorial). It is **private-but-editor-visible**:
 * no public front-end route (`public => false`, `publicly_queryable => false`, no archive/rewrite),
 * yet a full block-editor + REST surface (`show_ui`, `show_in_rest`) so every visible sentence is
 * authored on the canvas — never in a sidebar field, option, or PHP string (the editor-canvas rule).
 *
 * Each record carries a `_perego_section_role` meta naming its role; linked EN/AR records share a
 * role, and the `perego/global-section` block resolves the current-language record at render time.
 */
final class GlobalSectionPostType
{
    // `perego_section` (14 chars) — WordPress rejects post type names over 20 characters, so the
    // fuller `perego_global_section` (21) cannot be used; this keeps the prefix and meaning.
    public const POST_TYPE = 'perego_section';

    public const META_ROLE = '_perego_section_role';

    /**
     * The controlled section roles required by the handoff shell: role key => admin label.
     *
     * @var array<string, string>
     */
    public const ROLES = [
        'header' => 'Header content',
        'standard-footer' => 'Standard footer',
        'contact-footer' => 'Contact (flat) footer',
        'global-cta' => 'Global project CTA',
        'contact-details' => 'Global contact details',
        'not-found' => '404 editorial',
    ];

    public function register(): void
    {
        register_post_type(self::POST_TYPE, $this->postTypeArgs());
        register_post_meta(self::POST_TYPE, self::META_ROLE, $this->roleMetaArgs());
    }

    /**
     * @return array<string, mixed>
     */
    public function postTypeArgs(): array
    {
        return [
            'labels' => [
                'name' => __('Global Sections', 'perego-site'),
                'singular_name' => __('Global Section', 'perego-site'),
                'add_new_item' => __('Add New Global Section', 'perego-site'),
                'edit_item' => __('Edit Global Section', 'perego-site'),
                'menu_name' => __('Global Sections', 'perego-site'),
            ],
            'public' => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => false,
            'has_archive' => false,
            'rewrite' => false,
            'menu_icon' => 'dashicons-layout',
            'supports' => ['title', 'editor', 'revisions'],
            'show_in_rest' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function roleMetaArgs(): array
    {
        return [
            'single' => true,
            'type' => 'string',
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_key',
            'auth_callback' => static fn (): bool => current_user_can('edit_posts'),
        ];
    }
}
