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

    public function register(): void
    {
        register_post_type(self::POST_TYPE, $this->postTypeArgs());
        register_taxonomy(self::TAXONOMY, self::POST_TYPE, $this->taxonomyArgs());
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
