<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\PostTypes\ProjectThumbnails;

/**
 * The `<picture>` for one project tile: the right crop for the slot, at each layout band.
 *
 * A project tile changes shape twice as the viewport narrows — the services mosaic runs a 7-column
 * layout above 900px, a 4-column one down to 561px, and a uniform 2-column grid below that — so the
 * crop that suits a slot on a desktop is the wrong one on a phone. The breakpoints are viewport-based
 * and the slot is known server-side, so this states the mapping in markup rather than measuring
 * anything at runtime: the `<img>` carries the desktop crop and the `<source>`s override it, which
 * also means the tile still renders correctly with JavaScript disabled.
 *
 * The `media` values here MUST match the bands in the theme's `perego-reference.scss`; the slot-map
 * test pins the slot shapes, and these three constants are the other half of that contract.
 */
final class ProjectTileImage
{
    /** Below this, the mosaic is a uniform 2-column grid — see `MOBILE_SHAPE`. */
    private const MOBILE_MAX = 560;

    /** Between `MOBILE_MAX` and this, the mosaic is 4 uniform columns of card-shaped tiles. */
    private const TABLET_MAX = 900;

    /**
     * One tile's image markup.
     *
     * Falls back to a plain `<img>` when the project resolves to a single image for every band, so a
     * project with no per-shape crops emits exactly the markup it did before this existed.
     *
     * @param int    $postId the project
     * @param string $slot   mosaic slot (`m1`…`m15`), or '' for the uniform grids
     * @param string $alt    already-unescaped alt text
     * @param string $legacyUrl the URL the caller would have used; kept for projects with no attachment
     */
    public static function render(int $postId, string $slot, string $alt, string $legacyUrl = ''): string
    {
        $desktop = self::url($postId, ProjectThumbnails::shapeForSlot($slot), $legacyUrl);

        if ($desktop === '') {
            return '';
        }

        $tablet = self::url($postId, ProjectThumbnails::DEFAULT_SHAPE, $legacyUrl);
        $mobile = self::url($postId, ProjectThumbnails::MOBILE_SHAPE, $legacyUrl);

        $img = '<img src="' . esc_url($desktop) . '" alt="' . esc_attr($alt) . '" loading="lazy" />';

        // Nothing to switch between: one crop serves every band, so skip the <picture> wrapper
        // entirely rather than emit three <source>s pointing at the same file.
        if ($tablet === $desktop && $mobile === $desktop) {
            return $img;
        }

        return '<picture>'
            . self::source($mobile, self::MOBILE_MAX)
            . self::source($tablet, self::TABLET_MAX)
            . $img
            . '</picture>';
    }

    /** A `<source>` for one band, or nothing when that band has no distinct crop. */
    private static function source(string $url, int $maxWidth): string
    {
        return $url === ''
            ? ''
            : '<source media="(max-width: ' . $maxWidth . 'px)" srcset="' . esc_url($url) . '" />';
    }

    /** One shape's URL at the size the tiles actually consume, or the caller's fallback. */
    private static function url(int $postId, string $shape, string $legacyUrl): string
    {
        $id = ProjectThumbnails::idFor($postId, $shape);

        if ($id === 0) {
            return $legacyUrl;
        }

        $url = (string) wp_get_attachment_image_url($id, 'large');

        return $url !== '' ? $url : $legacyUrl;
    }
}
