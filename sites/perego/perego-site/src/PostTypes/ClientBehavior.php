<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\PostTypes;

defined('ABSPATH') || exit;

/**
 * Reads the behaviour a client card ALREADY had out of the fields that used to imply it
 * (`scripts/migrate-client-behavior.php`, owner decision 2026-07-28).
 *
 * Lives here rather than inside the migration script so the derivation can be tested directly: it is
 * the one piece of that script where a mistake is silent and permanent — a wrong answer turns a
 * working card static, or makes a static tile clickable, on content the script then deletes the
 * evidence for.
 */
final class ClientBehavior
{
    /**
     * The behaviour, gallery and link URL a client should carry after migration.
     *
     * Order matters. An individual card's video WAS its action regardless of any gallery, so it is
     * read first; a corporate tile's gallery was its action. Everything else becomes a static card —
     * including a tile that only ever opened its own logo through the implicit fallback the owner
     * asked to remove.
     *
     * @param string                                     $videoUrl  legacy `_perego_client_video_url`
     * @param string                                     $videoType legacy `_perego_client_video_type`
     * @param list<array{type:string,id:int,url:string}> $gallery   the existing, already-sanitized gallery
     * @return array{behavior:string, gallery:list<array{type:string,id:int,url:string}>, linkUrl:string}
     */
    public static function derive(string $videoUrl, string $videoType, array $gallery): array
    {
        $videoUrl = trim($videoUrl);

        if ($videoUrl !== '' && $videoType === 'external') {
            return ['behavior' => 'link', 'gallery' => $gallery, 'linkUrl' => $videoUrl];
        }

        if ($videoUrl !== '') {
            $alreadyListed = array_filter(
                $gallery,
                static fn (array $item): bool => $item['url'] === $videoUrl
            );

            // The card's own video led; the gallery (if any) follows it, which is the order a
            // visitor paged through before.
            $merged = $alreadyListed !== []
                ? $gallery
                : [['type' => 'video', 'id' => 0, 'url' => $videoUrl], ...$gallery];

            return ['behavior' => 'lightbox', 'gallery' => $merged, 'linkUrl' => ''];
        }

        return [
            'behavior' => $gallery !== [] ? 'lightbox' : 'none',
            'gallery' => $gallery,
            'linkUrl' => '',
        ];
    }
}
