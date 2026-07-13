<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Seo;

defined('ABSPATH') || exit;

use WP_Post;

/**
 * Keeps WordPress's auto-created "Sample Page" placeholder out of search indexes and the XML sitemap.
 *
 * That page (fixed slug `sample-page`, created on every fresh install) is deliberately retained as the
 * `page`-template route-health fixture, but it still carries WordPress's default lorem copy — so left
 * alone it leaks placeholder content into `wp-sitemap-posts-page-1.xml` and is indexable. This adds a
 * `noindex,nofollow` robots directive on that page and drops it from the posts sitemap.
 *
 * Launch note: when the fixture is replaced with real content or deleted (LAUNCH-CHECKLIST §4), this
 * guard becomes a harmless no-op (the slug no longer resolves) and can be removed.
 */
final class PlaceholderPageIndexing
{
    /** WordPress's auto-created placeholder page slug (present on every fresh install). */
    private const PLACEHOLDER_SLUG = 'sample-page';

    public function register(): void
    {
        add_filter('wp_robots', [$this, 'noindexPlaceholder']);
        add_filter('wp_sitemaps_posts_query_args', [$this, 'excludeFromSitemap'], 10, 2);
    }

    /**
     * @param array<string, mixed> $robots
     * @return array<string, mixed>
     */
    public function noindexPlaceholder(array $robots): array
    {
        if (function_exists('is_page') && is_page(self::PLACEHOLDER_SLUG)) {
            $robots['noindex'] = true;
            $robots['nofollow'] = true;
        }

        return $robots;
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function excludeFromSitemap(array $args, string $postType): array
    {
        if ($postType !== 'page') {
            return $args;
        }

        $placeholderId = $this->placeholderId();
        if ($placeholderId === 0) {
            return $args;
        }

        $excluded = isset($args['post__not_in']) && is_array($args['post__not_in']) ? $args['post__not_in'] : [];
        $excluded[] = $placeholderId;
        $args['post__not_in'] = $excluded;

        return $args;
    }

    private function placeholderId(): int
    {
        $page = function_exists('get_page_by_path') ? get_page_by_path(self::PLACEHOLDER_SLUG) : null;

        return $page instanceof WP_Post ? (int) $page->ID : 0;
    }
}
