<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Admin;

defined('ABSPATH') || exit;

use PeregoSite\PostTypes\ClientPostType;
use PeregoSite\PostTypes\ProjectPostType;
use PeregoSite\PostTypes\ServicePostType;

/**
 * Admin list columns for the Perego post types (spec 021 Phase 4 / T022).
 *
 * None of the three CPTs defined any, so the Projects list showed Title / Services / Date and nothing
 * else — 154 rows with no thumbnail and no way to see which client or year a row belonged to without
 * opening it. Each type now leads with a thumbnail and carries the one or two fields that identify a
 * row at a glance.
 *
 * Read-only presentation over meta the panels already own; nothing here writes.
 */
final class PostListColumns
{
    private const THUMB = 'perego_thumb';

    public function register(): void
    {
        $this->registerFor(ProjectPostType::POST_TYPE, [
            'perego_client' => __('Client', 'perego-site'),
            'perego_year' => __('Year', 'perego-site'),
        ]);

        $this->registerFor(ServicePostType::POST_TYPE, [
            'perego_service_key' => __('Service key', 'perego-site'),
        ]);

        $this->registerFor(ClientPostType::POST_TYPE, [
            'perego_client_sub' => __('Subtitle', 'perego-site'),
        ]);

        add_action('admin_head', static function (): void {
            if (self::isPeregoList(function_exists('get_current_screen') ? get_current_screen() : null)) {
                self::columnStyles();
            }
        });
    }

    /**
     * @param array<string, string> $extra column key => heading, shown after the thumbnail
     */
    private function registerFor(string $postType, array $extra): void
    {
        add_filter("manage_{$postType}_posts_columns", function (array $columns) use ($extra): array {
            // Order: the bulk-select checkbox stays first (a list table's `cb` column must lead, or
            // the select-all control lands in the wrong cell), then the thumbnail, then the title,
            // then the identifying fields, then whatever the type already had (taxonomy, date).
            $rebuilt = [];
            foreach ($columns as $key => $label) {
                $rebuilt[$key] = $label;

                if ($key === 'cb') {
                    $rebuilt[self::THUMB] = __('Image', 'perego-site');
                }

                if ($key === 'title') {
                    foreach ($extra as $extraKey => $extraLabel) {
                        $rebuilt[$extraKey] = $extraLabel;
                    }
                }
            }

            // A list table without a `cb` column (no bulk actions) still gets the thumbnail.
            if (! isset($rebuilt[self::THUMB])) {
                $rebuilt = [self::THUMB => __('Image', 'perego-site')] + $rebuilt;
            }

            return $rebuilt;
        });

        add_action("manage_{$postType}_posts_custom_column", function (string $column, int $postId): void {
            $this->renderColumn($column, $postId);
        }, 10, 2);
    }

    private function renderColumn(string $column, int $postId): void
    {
        if ($column === self::THUMB) {
            $thumb = get_the_post_thumbnail($postId, [48, 48]);
            echo $thumb !== '' ? wp_kses_post($thumb) : '<span aria-hidden="true">&mdash;</span>';

            return;
        }

        $key = match ($column) {
            'perego_client' => ProjectPostType::META_CLIENT,
            'perego_year' => ProjectPostType::META_YEAR,
            'perego_service_key' => ServicePostType::META_SERVICE_SLUG,
            'perego_client_sub' => ClientPostType::META_SUB,
            default => '',
        };

        if ($key === '') {
            return;
        }

        $value = (string) get_post_meta($postId, $key, true);
        echo $value !== '' ? esc_html($value) : '<span aria-hidden="true">&mdash;</span>';
    }

    /**
     * The image column's width, so a 48px thumbnail does not get a full title-width column.
     * Hooked from the provider on the list screens only.
     */
    public static function columnStyles(): void
    {
        echo '<style>.column-' . self::THUMB . '{width:64px}.column-' . self::THUMB . ' img{border-radius:2px}</style>';
    }

    /** Whether the current screen is one of the Perego post-type lists. */
    public static function isPeregoList(?\WP_Screen $screen): bool
    {
        return $screen !== null
            && $screen->base === 'edit'
            && in_array($screen->post_type, [
                ProjectPostType::POST_TYPE,
                ServicePostType::POST_TYPE,
                ClientPostType::POST_TYPE,
            ], true);
    }
}
