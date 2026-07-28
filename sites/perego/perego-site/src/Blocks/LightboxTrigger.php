<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\PostTypes\ClientPostType;

/**
 * Turns a stored media list into the one `data-*` attribute the site-wide media lightbox
 * (`perego-theme/media-lightbox`) listens for.
 *
 * `media-lightbox/view.js` delegates from `[data-image], [data-video], [data-gallery]` and resolves
 * each URL's own type (image / direct video file / YouTube-or-Vimeo embed) itself, so a renderer's
 * only job is to pick the right attribute and hand over URLs. That choice was previously open-coded
 * in every renderer that opens the lightbox; clients now share this one so the corporate and
 * individual cards cannot drift apart from each other.
 *
 * `data-gallery` is a comma-separated list because that is the contract `view.js` already parses.
 * A media URL may legitimately contain a comma, which would split one item into two — so commas are
 * percent-encoded on the way out, which every URL parser accepts and `view.js` never splits on.
 */
final class LightboxTrigger
{
    /**
     * The trigger attribute for a gallery, with a leading space so a renderer can concatenate it
     * straight into a tag — or an empty string when there is nothing to open.
     *
     * A single item becomes `data-video`/`data-image` rather than a one-entry `data-gallery`, so the
     * lightbox skips its next/previous chrome for media that has no siblings.
     *
     * @param list<array{type:string,id:int,url:string}> $gallery
     * @param string                                     $fallbackImageUrl used when the gallery is empty
     */
    public static function attribute(array $gallery, string $fallbackImageUrl = ''): string
    {
        $items = self::resolve($gallery);

        if ($items === []) {
            return $fallbackImageUrl !== ''
                ? ' data-image="' . esc_url($fallbackImageUrl) . '"'
                : '';
        }

        if (count($items) === 1) {
            $only = $items[0];
            $attribute = $only['type'] === 'video' ? 'data-video' : 'data-image';

            return ' ' . $attribute . '="' . esc_url($only['url']) . '"';
        }

        $urls = array_map(
            static fn (array $item): string => str_replace(',', '%2C', $item['url']),
            $items
        );

        return ' data-gallery="' . esc_attr(implode(',', $urls)) . '"';
    }

    /**
     * The gallery's rows as `{type, url}`, dropping any row whose media no longer resolves — a
     * deleted attachment must not leave an empty slide in the lightbox.
     *
     * @param list<array{type:string,id:int,url:string}> $gallery
     * @return list<array{type:string,url:string}>
     */
    private static function resolve(array $gallery): array
    {
        $items = [];

        foreach (ClientPostType::sanitizeGallery($gallery) as $item) {
            $url = $item['type'] === 'image'
                ? (string) wp_get_attachment_image_url($item['id'], 'large')
                : $item['url'];

            if ($url !== '') {
                $items[] = ['type' => $item['type'], 'url' => $url];
            }
        }

        return $items;
    }
}
