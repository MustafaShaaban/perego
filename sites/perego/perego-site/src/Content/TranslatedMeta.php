<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Content;

defined('ABSPATH') || exit;

use WP_Post;

/**
 * Reads post meta that belongs to a piece of content rather than to one of its languages, falling back
 * to the linked English translation when a translated post has none of its own.
 *
 * WHY THIS EXISTS. **Polylang does not copy post meta to translations.** An Arabic post is a separate
 * row with its own (empty) meta, so anything language-neutral — a video URL, a gallery, an attachment
 * id, a statistic — is stored once on the English post and simply absent on the Arabic one. Code that
 * reads `get_post_meta($arabicPost->ID, …)` directly therefore gets an empty string and silently
 * renders a degraded variant.
 *
 * That is not hypothetical: the Arabic homepage rendered its client cards as inert
 * `<div class="indiv-card">` instead of the `<button data-video="…">` the English page rendered,
 * because `ClientsCarouselRenderer` read `_perego_client_video_url` straight off the Arabic post. The
 * cards looked right and did nothing when clicked.
 *
 * `ProjectRepository` had already solved this privately for Projects; this is that logic extracted so
 * Clients — and anything added later — share one implementation instead of each rediscovering the bug.
 */
final class TranslatedMeta
{
    /**
     * A post's own meta value, falling back to its linked English translation's value when empty.
     */
    public static function string(WP_Post $post, string $key): string
    {
        $value = (string) get_post_meta($post->ID, $key, true);
        if ($value !== '') {
            return $value;
        }

        $enId = self::englishId($post);

        return $enId === 0 ? '' : (string) get_post_meta($enId, $key, true);
    }

    /**
     * A post's own meta value for a non-scalar (array) key, with the same English fallback.
     *
     * Kept separate from {@see string()} because "empty" differs by shape: an empty array is a real
     * "nothing stored here" for a gallery, while casting one to string would silently produce `''`
     * and mask the difference.
     *
     * @return mixed the raw stored value; callers apply their own sanitizer
     */
    public static function value(WP_Post $post, string $key): mixed
    {
        $value = get_post_meta($post->ID, $key, true);
        if (! self::isEmpty($value)) {
            return $value;
        }

        $enId = self::englishId($post);

        return $enId === 0 ? $value : get_post_meta($enId, $key, true);
    }

    /**
     * The linked English translation's post id, or 0 when there is none, when this already IS the
     * English post, or when Polylang is inactive (constitution IX: no optional dependency is a hard
     * one — without Polylang there are no translations to fall back to, and the post's own meta wins).
     */
    public static function englishId(WP_Post $post): int
    {
        if (! function_exists('pll_get_post')) {
            return 0;
        }

        $enId = (int) pll_get_post($post->ID, 'en');

        return ($enId === 0 || $enId === $post->ID) ? 0 : $enId;
    }

    /** @param mixed $value */
    private static function isEmpty($value): bool
    {
        return $value === '' || $value === [] || $value === null || $value === false;
    }
}
