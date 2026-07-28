<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\PostTypes;

defined('ABSPATH') || exit;

/**
 * Which crop of a project belongs in which grid tile (owner, 2026-07-28).
 *
 * THE PROBLEM. A project appears in tiles of six different aspect ratios — the services mosaic alone
 * spans 3×2, 2×1, 1×2, 1×1, 2×2 and 1.5-column slots — and `.work-card img` is `object-fit: cover`.
 * One landscape featured image therefore gets centre-cropped into a 0.55:1 portrait slot, and whatever
 * mattered in it is the first thing cut. Editors now supply a crop per shape.
 *
 * WHY THE SLOT IS RESOLVED IN PHP. Which tile a project lands in is already decided server-side — the
 * renderer hands out `m1`…`m15` by position — and the layout's breakpoints are viewport-based. So the
 * correct crop is knowable at render time, and measuring the container in JavaScript would only
 * rediscover, later and less reliably, something the markup can simply state. Renderers emit a
 * `<picture>` whose sources name the breakpoints; nothing depends on JavaScript.
 *
 * THE DUPLICATION. `SLOT_SHAPES` mirrors the `.m{n}` spans in the theme's `perego-reference.scss`, and
 * the two can drift silently. `tests-js`'s slot-map test parses that stylesheet and asserts every
 * slot's real aspect ratio matches the shape named here — the same parse-the-other-language guard
 * `EditorPanels/schema.test.js` uses against the PHP constants.
 */
final class ProjectThumbnails
{
    /**
     * The four crops an editor can upload, with the pixel size each one targets.
     *
     * `ratio` is width ÷ height and is what {@see nearestShape()} matches on, so a slot with no crop of
     * its own borrows the least-wrong one rather than falling straight back to the featured image.
     *
     * @var array<string, array{meta: string, width: int, height: int, ratio: float}>
     */
    public const SHAPES = [
        'hero' => ['meta' => ProjectPostType::META_THUMB_HERO, 'width' => 578, 'height' => 332, 'ratio' => 1.74],
        'banner' => ['meta' => ProjectPostType::META_THUMB_BANNER, 'width' => 380, 'height' => 158, 'ratio' => 2.41],
        'tall' => ['meta' => ProjectPostType::META_THUMB_TALL, 'width' => 182, 'height' => 332, 'ratio' => 0.55],
        'card' => ['meta' => ProjectPostType::META_THUMB_CARD, 'width' => 406, 'height' => 254, 'ratio' => 1.60],
    ];

    /**
     * Mosaic slot → shape, by the slot's real span in `perego-reference.scss`.
     *
     * Slots whose ratio has no crop of its own take the nearest: `m7` (1.5×2, 0.88:1) reads as `tall`,
     * `m12` (1.5×1, 1.85:1) as `hero`, and the squarish `m5`/`m8`/`m9`/`m13` as `card`.
     *
     * @var array<string, string>
     */
    public const SLOT_SHAPES = [
        'm1' => 'hero',    // 3 col × 2 row
        'm2' => 'banner',  // 2 col × 1 row
        'm3' => 'tall',    // 1 col × 2 row
        'm4' => 'banner',
        'm5' => 'card',    // 1 col × 1 row
        'm6' => 'banner',
        'm7' => 'tall',    // 1.5 col × 2 row
        'm8' => 'card',    // 2 col × 2 row
        'm9' => 'card',
        'm10' => 'banner',
        'm11' => 'hero',
        'm12' => 'hero',   // 1.5 col × 1 row
        'm13' => 'card',
        'm14' => 'tall',
        'm15' => 'banner', // 2.5 col × 1 row
    ];

    /** Every tile is a uniform card below the mosaic's own breakpoints, and in the other grids. */
    public const DEFAULT_SHAPE = 'card';

    /** Below this width the mosaic is a 2-column grid of ~1.76:1 tiles — the `hero` crop's shape. */
    public const MOBILE_SHAPE = 'hero';

    /** @return list<string> */
    public static function metaKeys(): array
    {
        return array_column(self::SHAPES, 'meta');
    }

    /** The shape a mosaic slot wants; unknown slots (and the other grids) get the uniform card. */
    public static function shapeForSlot(string $slot): string
    {
        return self::SLOT_SHAPES[$slot] ?? self::DEFAULT_SHAPE;
    }

    /**
     * The attachment id to show for one shape, or 0 when the project has nothing at all.
     *
     * Resolution order: the shape's own crop, then the nearest shape by aspect ratio that the editor
     * did upload, then the project's featured image. Every step is optional, so a project with no
     * crops renders exactly as it did before this existed.
     */
    public static function idFor(int $postId, string $shape): int
    {
        // No post, nothing to resolve. Callers that render already-shaped data rather than a real
        // post (the pure grid renderers, and their tests) pass 0 and keep their own URL.
        if ($postId <= 0) {
            return 0;
        }

        $own = self::attachmentId($postId, $shape);
        if ($own > 0) {
            return $own;
        }

        foreach (self::nearestShapes($shape) as $candidate) {
            $id = self::attachmentId($postId, $candidate);
            if ($id > 0) {
                return $id;
            }
        }

        return self::featuredId($postId);
    }

    /**
     * The other shapes, ordered by how close their aspect ratio is to the requested one.
     *
     * @return list<string>
     */
    private static function nearestShapes(string $shape): array
    {
        $target = self::SHAPES[$shape]['ratio'] ?? self::SHAPES[self::DEFAULT_SHAPE]['ratio'];

        $others = array_diff(array_keys(self::SHAPES), [$shape]);
        usort($others, static fn (string $a, string $b): int => abs(self::SHAPES[$a]['ratio'] - $target)
            <=> abs(self::SHAPES[$b]['ratio'] - $target));

        return array_values($others);
    }

    /**
     * One shape's stored attachment id, following the linked English record when the translation has
     * none of its own.
     *
     * Read directly rather than through `Content\TranslatedMeta`: an unset integer meta answers `''`
     * but a stored zero answers `"0"`, and only the first should fall through to English. This is the
     * same distinction `ProjectRepository::logoId()` documents for `_perego_logo_id`.
     */
    private static function attachmentId(int $postId, string $shape): int
    {
        $key = self::SHAPES[$shape]['meta'] ?? '';
        if ($key === '') {
            return 0;
        }

        $own = absint(get_post_meta($postId, $key, true));
        if ($own > 0) {
            return $own;
        }

        $englishId = self::englishId($postId);

        return $englishId === 0 ? 0 : absint(get_post_meta($englishId, $key, true));
    }

    /** The project's featured image, following the same English fallback. */
    private static function featuredId(int $postId): int
    {
        $own = absint(get_post_thumbnail_id($postId));
        if ($own > 0) {
            return $own;
        }

        $englishId = self::englishId($postId);

        return $englishId === 0 ? 0 : absint(get_post_thumbnail_id($englishId));
    }

    /**
     * The linked English translation's id, or 0 when there is none, when this already IS English, or
     * when Polylang is inactive (constitution IX: no optional dependency is a hard one).
     */
    private static function englishId(int $postId): int
    {
        if (! function_exists('pll_get_post')) {
            return 0;
        }

        $englishId = (int) pll_get_post($postId, 'en');

        return ($englishId === 0 || $englishId === $postId) ? 0 : $englishId;
    }
}
