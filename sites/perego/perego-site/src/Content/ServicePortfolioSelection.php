<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Content;

use PeregoSite\PostTypes\ServicePostType;
use WP_Post;

defined('ABSPATH') || exit;

/**
 * Purely composes a Service's automatic category results with its manual Project choices.
 * Retrieval, localization, and rendering remain at their existing boundaries.
 */
final class ServicePortfolioSelection
{
    /**
     * @param list<WP_Post> $automatic
     * @param list<WP_Post> $selected
     * @param list<int> $excludedIds
     * @return list<WP_Post>
     */
    public function resolve(array $automatic, array $selected, string $mode, array $excludedIds = []): array
    {
        $mode = ServicePostType::sanitizePortfolioMode($mode);
        $excludedIds = ServicePostType::sanitizeIntList($excludedIds);
        $automatic = $this->withoutExcluded($automatic, $excludedIds);

        if ($mode === 'automatic') {
            return $automatic;
        }
        if ($mode === 'manual') {
            return $selected;
        }

        $selectedIds = array_map(static fn (WP_Post $post): int => $post->ID, $selected);

        return [...$selected, ...array_values(array_filter(
            $automatic,
            static fn (WP_Post $post): bool => ! in_array($post->ID, $selectedIds, true)
        ))];
    }

    /** @param list<WP_Post> $projects @param list<int> $excludedIds @return list<WP_Post> */
    private function withoutExcluded(array $projects, array $excludedIds): array
    {
        if ($excludedIds === []) {
            return $projects;
        }

        return array_values(array_filter(
            $projects,
            static fn (WP_Post $post): bool => ! in_array($post->ID, $excludedIds, true)
        ));
    }
}
