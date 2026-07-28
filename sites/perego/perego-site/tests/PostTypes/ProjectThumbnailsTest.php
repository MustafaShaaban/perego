<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\PostTypes\ProjectPostType;
use PeregoSite\PostTypes\ProjectThumbnails;

/**
 * Stage per-shape crops and featured images as `[postId => [metaKey => value]]`.
 *
 * @param array<int, array<string, mixed>> $meta
 * @param array<int, int>                  $featured postId => attachment id
 */
function perego_stage_project_media(array $meta, array $featured = []): void
{
    Functions\when('get_post_meta')->alias(static fn (int $id, string $key) => $meta[$id][$key] ?? '');
    Functions\when('get_post_thumbnail_id')->alias(static fn (int $id) => $featured[$id] ?? 0);
}

beforeEach(function () {
    Functions\when('absint')->alias(fn ($v) => abs((int) $v));
    perego_stage_project_media([]);
});

it('uses the crop uploaded for the requested shape', function () {
    perego_stage_project_media([7 => [ProjectPostType::META_THUMB_TALL => 42]]);

    expect(ProjectThumbnails::idFor(7, 'tall'))->toBe(42);
});

/*
 * A slot with no crop of its own borrows the least-wrong one rather than dropping straight to the
 * featured image — a banner (2.41:1) is a far better source for a hero slot (1.74:1) than an
 * arbitrary portrait photo would be.
 */
it('falls back to the nearest shape by aspect ratio', function () {
    // Asking for `hero` (1.74) with only `card` (1.60) and `tall` (0.55) present: card is nearer.
    perego_stage_project_media([7 => [
        ProjectPostType::META_THUMB_CARD => 21,
        ProjectPostType::META_THUMB_TALL => 99,
    ]]);

    expect(ProjectThumbnails::idFor(7, 'hero'))->toBe(21);
});

it('falls back to the featured image when no crop was uploaded at all', function () {
    perego_stage_project_media([], [7 => 55]);

    expect(ProjectThumbnails::idFor(7, 'hero'))->toBe(55);
});

it('answers 0 when the project has no media of any kind', function () {
    expect(ProjectThumbnails::idFor(7, 'hero'))->toBe(0);
});

/*
 * The pure grid renderers hand already-shaped arrays to the tile helper and have no post id. They
 * must keep their own URL rather than resolving anything.
 */
it('resolves nothing for a missing post id', function () {
    expect(ProjectThumbnails::idFor(0, 'hero'))->toBe(0);
});

/*
 * Polylang does not copy meta to translations, so an Arabic project carries no crops of its own.
 * Read directly rather than through TranslatedMeta: an unset integer meta answers `''` but a stored
 * zero answers `"0"`, and only the first should fall through to English.
 */
it('follows the linked English record for a translation with no crops', function () {
    Functions\when('pll_get_post')->alias(fn (int $id, string $locale) => $id === 7 && $locale === 'en' ? 70 : 0);
    perego_stage_project_media([70 => [ProjectPostType::META_THUMB_HERO => 88]]);

    expect(ProjectThumbnails::idFor(7, 'hero'))->toBe(88);
});

it('follows the English featured image when neither post has crops', function () {
    Functions\when('pll_get_post')->alias(fn (int $id, string $locale) => $id === 7 && $locale === 'en' ? 70 : 0);
    perego_stage_project_media([], [70 => 91]);

    expect(ProjectThumbnails::idFor(7, 'hero'))->toBe(91);
});

it('prefers the translation own crop over the English record', function () {
    Functions\when('pll_get_post')->alias(fn (int $id, string $locale) => $id === 7 && $locale === 'en' ? 70 : 0);
    perego_stage_project_media([
        7 => [ProjectPostType::META_THUMB_HERO => 12],
        70 => [ProjectPostType::META_THUMB_HERO => 88],
    ]);

    expect(ProjectThumbnails::idFor(7, 'hero'))->toBe(12);
});

it('maps every mosaic slot to a real shape', function () {
    foreach (ProjectThumbnails::SLOT_SHAPES as $slot => $shape) {
        expect(array_keys(ProjectThumbnails::SHAPES))->toContain($shape);
    }

    // All 15 placements in the stylesheet are accounted for; a new one must be mapped deliberately.
    expect(ProjectThumbnails::SLOT_SHAPES)->toHaveCount(15);
});

it('sends an unknown slot and the other grids to the uniform card shape', function () {
    expect(ProjectThumbnails::shapeForSlot(''))->toBe('card')
        ->and(ProjectThumbnails::shapeForSlot('m99'))->toBe('card')
        ->and(ProjectThumbnails::shapeForSlot('m1'))->toBe('hero');
});

it('exposes one meta key per shape', function () {
    expect(ProjectThumbnails::metaKeys())->toHaveCount(count(ProjectThumbnails::SHAPES))
        ->and(ProjectThumbnails::metaKeys())->toContain(ProjectPostType::META_THUMB_HERO);
});
