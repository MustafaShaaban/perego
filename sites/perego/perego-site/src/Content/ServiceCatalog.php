<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Content;

use PeregoSite\PostTypes\ServicePostType;
use WP_Query;
use WP_Post;

defined('ABSPATH') || exit;

/**
 * The published `perego_service` posts for a locale, keyed by their canonical service slug
 * (`_perego_service_slug`). One place for the query so the homepage teaser, the service-single hero
 * tabs, and the services archive all project the same CPT the same way. Polylang honours the `lang`
 * arg, so the AR surfaces match AR Services (which share the EN slug meta). No posts (or CPT/Polylang
 * absent) → empty map → every consumer falls back to its `ServiceContent`/`HomeContent` seed.
 */
final class ServiceCatalog
{
    /**
     * @return array<string, \WP_Post>
     */
    public function postsBySlug(string $locale): array
    {
        if (! class_exists(WP_Query::class)) {
            return [];
        }

        $query = new WP_Query([
            'post_type'           => ServicePostType::POST_TYPE,
            'post_status'         => 'publish',
            'posts_per_page'      => 10,
            'no_found_rows'       => true,
            'ignore_sticky_posts' => true,
            'orderby'             => 'menu_order',
            'order'               => 'ASC',
            'lang'                => $locale,
        ]);

        $map = [];
        foreach ($query->posts as $post) {
            $slug = (string) get_post_meta($post->ID, ServicePostType::META_SERVICE_SLUG, true);
            if ($slug !== '' && ! isset($map[$slug])) {
                $map[$slug] = $post;
            }
        }
        wp_reset_postdata();

        return $map;
    }

    /**
     * Canonical slug => editable teaser/tab label (`_perego_teaser_label`), only where set.
     *
     * @return array<string, string>
     */
    public function labelsBySlug(string $locale): array
    {
        $labels = [];
        foreach ($this->postsBySlug($locale) as $slug => $post) {
            $label = (string) get_post_meta($post->ID, ServicePostType::META_TEASER_LABEL, true);
            if ($label !== '') {
                $labels[$slug] = $label;
            }
        }

        return $labels;
    }
}
