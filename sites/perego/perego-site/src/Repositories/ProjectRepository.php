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
