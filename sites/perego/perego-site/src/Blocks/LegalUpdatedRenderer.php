<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Content\GlobalContent;
use WP_Post;

/**
 * Server-renders the perego-theme/legal-updated block: the "Last updated: <date>" line that sits under
 * the legal page's H1 in the legal-hero, per the handoff `terms.html` / `privacy.html`. The date is an
 * editable page projection — an editor sets `_perego_legal_updated` (the curated review date) on each
 * language's legal page; when empty it falls back to the page's own modified date. Either way the value
 * is formatted in the current locale via `wp_date`, so AR renders Arabic month names/numerals. The
 * "Last updated" label comes from GlobalContent, so it too is language-aware.
 */
final class LegalUpdatedRenderer
{
    public const META_UPDATED = '_perego_legal_updated';

    public function __construct(private readonly GlobalContent $content)
    {
    }

    /**
     * Register the curated-date meta on the `page` type (REST-exposed, sanitised, edit-gated). Only the
     * legal pages carry a value and only this renderer reads it; empty falls back to the modified date.
     */
    public function register(): void
    {
        register_post_meta('page', self::META_UPDATED, [
            'type'              => 'string',
            'single'            => true,
            'default'           => '',
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => [self::class, 'authEdit'],
        ]);
    }

    public static function authEdit(): bool
    {
        return function_exists('current_user_can') && current_user_can('edit_pages');
    }

    public function render(?WP_Post $page): string
    {
        if (! $page instanceof WP_Post) {
            return '';
        }

        $timestamp = $this->resolveTimestamp($page);
        if ($timestamp === null) {
            return '';
        }

        $label = $this->content->legal()['lastUpdated'];
        $date = wp_date('F j, Y', $timestamp);

        return '<p class="legal-updated">' . esc_html($label . ': ' . $date) . '</p>';
    }

    /**
     * The curated `_perego_legal_updated` date if an editor set one, else the page's own modified date.
     */
    private function resolveTimestamp(WP_Post $page): ?int
    {
        $curated = trim((string) get_post_meta($page->ID, self::META_UPDATED, true));
        if ($curated !== '') {
            $parsed = strtotime($curated);
            if ($parsed !== false) {
                return $parsed;
            }
        }

        $modified = get_post_timestamp($page, 'modified');

        return $modified > 0 ? $modified : null;
    }
}
