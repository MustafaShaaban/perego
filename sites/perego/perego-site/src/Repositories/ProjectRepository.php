<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Repositories;

defined('ABSPATH') || exit;

use PeregoSite\Content\PortfolioContent;
use PeregoSite\PostTypes\ProjectPostType;
use WP_Post;
use WP_Query;
use WP_Term;

/**
 * Reads `perego_project` posts for the portfolio grid (spec 003 / M3). Keeps the WP_Query + meta +
 * term lookups in one place so the block renderer stays a pure function of an array. The pure
 * mapping (`toGridCard()`) is unit-testable with a WP_Post-shaped fixture; the query itself is thin.
 */
final class ProjectRepository
{
    /**
     * Resolve a project's editor-managed gallery attachment IDs into safe image data. The meta is
     * intentionally a normal post-meta list: seed data supplies a default only when absent, while
     * editors retain full control thereafter.
     *
     * @return list<array{src: string, thumb: string, alt: string}>
     */
    public function galleryFor(WP_Post $project): array
    {
        $ids = get_post_meta($project->ID, '_perego_gallery_attachment_ids', true);
        $ids = is_array($ids) ? array_values(array_filter(array_map('absint', $ids))) : [];

        return array_values(array_filter(array_map(static function (int $id): ?array {
            $src = wp_get_attachment_image_url($id, 'full');
            if (! is_string($src) || $src === '') {
                return null;
            }

            $thumb = wp_get_attachment_image_url($id, 'large');
            $alt = (string) get_post_meta($id, '_wp_attachment_image_alt', true);

            return [
                'src' => $src,
                'thumb' => is_string($thumb) && $thumb !== '' ? $thumb : $src,
                'alt' => $alt !== '' ? $alt : get_the_title($id),
            ];
        }, $ids)));
    }

    /**
     * @return array{previous: ?WP_Post, next: ?WP_Post}
     */
    public function adjacentFor(WP_Post $project): array
    {
        $projects = $this->projectsForLocale($project, 60);
        $index = array_search($project->ID, array_map(static fn (WP_Post $item): int => $item->ID, $projects), true);
        $count = count($projects);

        if (! is_int($index) || $count < 2) {
            return ['previous' => null, 'next' => null];
        }

        return [
            'previous' => $projects[($index - 1 + $count) % $count],
            'next' => $projects[($index + 1) % $count],
        ];
    }

    /**
     * @return list<WP_Post>
     */
    public function relatedFor(WP_Post $project, int $limit = 3): array
    {
        $args = $this->localeQueryArgs($project) + [
            'post_type' => ProjectPostType::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'post__not_in' => [$project->ID],
            'no_found_rows' => true,
            'orderby' => 'date',
            'order' => 'DESC',
        ];
        $terms = get_the_terms($project->ID, ProjectPostType::TAXONOMY);
        if (is_array($terms) && $terms !== []) {
            $args['tax_query'] = [[
                'taxonomy' => ProjectPostType::TAXONOMY,
                'field' => 'term_id',
                'terms' => [(int) $terms[0]->term_id],
            ]];
        }

        $related = (new WP_Query($args))->posts;
        if (count($related) >= $limit) {
            return $related;
        }

        // Some handoff demo categories contain fewer than three projects. Preserve the three-card
        // layout by filling the remaining slots with other current-language work, never duplicating
        // the current project or an already selected related project.
        unset($args['tax_query']);
        $args['post__not_in'] = array_merge([$project->ID], array_map(static fn (WP_Post $item): int => $item->ID, $related));
        $args['posts_per_page'] = $limit - count($related);

        return array_merge($related, (new WP_Query($args))->posts);
    }

    /**
     * All published projects, newest first, shaped for PortfolioGridRenderer. Bounded to a sane cap
     * (never -1) per the query-discipline rule.
     *
     * @return list<array{title: string, url: string, category: string, categoryLabel: string, excerpt: string, thumbUrl: string, thumbAlt: string}>
     */
    public function allForGrid(PortfolioContent $content): array
    {
        $query = new WP_Query([
            'post_type' => ProjectPostType::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 60,
            'no_found_rows' => true,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        return array_map(
            fn (WP_Post $post): array => $this->toGridCard($post, $content),
            $query->posts,
        );
    }

    /**
     * @return array{title: string, url: string, category: string, categoryLabel: string, excerpt: string, thumbUrl: string, thumbAlt: string}
     */
    public function toGridCard(WP_Post $post, PortfolioContent $content): array
    {
        $terms    = get_the_terms($post->ID, ProjectPostType::TAXONOMY);
        $category = (is_array($terms) && $terms !== []) ? $this->canonicalCategorySlug($terms[0]) : '';

        $client   = (string) get_post_meta($post->ID, '_perego_client', true);
        $year     = (string) get_post_meta($post->ID, '_perego_year', true);
        $excerpt  = $this->buildExcerpt($post, $content, $client, $year);

        $thumbId  = get_post_thumbnail_id($post->ID);
        $thumbUrl = $thumbId ? (string) wp_get_attachment_image_url($thumbId, 'large') : '';
        $thumbAlt = $thumbId ? (string) get_post_meta($thumbId, '_wp_attachment_image_alt', true) : '';

        return [
            'title' => get_the_title($post),
            'url' => (string) get_permalink($post),
            'category' => $category,
            'categoryLabel' => $category !== '' ? $content->categoryLabel($category) : '',
            'excerpt' => $excerpt,
            'thumbUrl' => $thumbUrl,
            'thumbAlt' => $thumbAlt !== '' ? $thumbAlt : get_the_title($post),
        ];
    }

    /**
     * Resolve the fixed, canonical category slug (`ProjectPostType::CATEGORIES`) for a term that may
     * be a Polylang per-language variant. Polylang gives every language its own term — the English
     * `video` term and a separate Arabic `video-ar` term, linked as translations — so an AR project's
     * own term slug is never one of the four canonical slugs the filter buttons and `categoryLabel()`
     * are keyed on. Without this, AR cards showed the literal term slug ("video-ar") as their category
     * badge, and the filter buttons (which filter by canonical slug) never matched any AR card.
     */
    private function canonicalCategorySlug(WP_Term $term): string
    {
        if (isset(ProjectPostType::CATEGORIES[$term->slug])) {
            return $term->slug;
        }

        if (! function_exists('pll_get_term')) {
            return $term->slug;
        }

        $enTermId = (int) pll_get_term($term->term_id, 'en');
        $enTerm   = $enTermId ? get_term($enTermId, ProjectPostType::TAXONOMY) : null;

        return ($enTerm instanceof WP_Term) ? $enTerm->slug : $term->slug;
    }

    /** @return list<WP_Post> */
    private function projectsForLocale(WP_Post $project, int $limit): array
    {
        return (new WP_Query($this->localeQueryArgs($project) + [
            'post_type' => ProjectPostType::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'no_found_rows' => true,
            'orderby' => 'date',
            'order' => 'ASC',
        ]))->posts;
    }

    /** @return array<string, string> */
    private function localeQueryArgs(WP_Post $project): array
    {
        if (! function_exists('pll_get_post_language')) {
            return [];
        }

        $language = pll_get_post_language($project->ID, 'slug');

        return is_string($language) && $language !== '' ? ['lang' => $language] : [];
    }

    private function buildExcerpt(WP_Post $post, PortfolioContent $content, string $client, string $year): string
    {
        if ($client !== '' || $year !== '') {
            $parts = [];
            if ($client !== '') {
                $parts[] = $content->clientLabel() . ': ' . $client;
            }
            if ($year !== '') {
                $parts[] = $year;
            }

            return implode(' · ', $parts);
        }

        return has_excerpt($post) ? get_the_excerpt($post) : '';
    }
}
