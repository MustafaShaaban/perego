<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\ProjectTileImage;
use PeregoSite\PostTypes\ProjectPostType;

beforeEach(function () {
    Functions\when('absint')->alias(fn ($v) => abs((int) $v));
    Functions\when('esc_url')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('get_post_thumbnail_id')->justReturn(0);
    Functions\when('get_post_meta')->justReturn('');
    Functions\when('wp_get_attachment_image_url')->alias(
        static fn (int $id) => $id > 0 ? "https://perego.local/{$id}.png" : ''
    );
});

/*
 * A project with no per-shape crops must emit exactly the markup it did before this feature existed —
 * three <source>s pointing at one file would be pure noise in the page and in every parity fixture.
 */
it('emits a plain img when one crop serves every breakpoint', function () {
    $html = ProjectTileImage::render(7, 'm1', 'Alt text', 'https://perego.local/legacy.png');

    expect($html)->toBe('<img src="https://perego.local/legacy.png" alt="Alt text" loading="lazy" />')
        ->and($html)->not->toContain('<picture>');
});

it('emits nothing at all when the project has no image and no fallback', function () {
    expect(ProjectTileImage::render(7, 'm1', 'Alt text'))->toBe('');
});

it('names the desktop slot crop on the img and the narrower bands on sources', function () {
    Functions\when('get_post_meta')->alias(static fn (int $id, string $key) => match ($key) {
        // m3 is a tall slot; the tablet band is uniform cards; below that everything is hero-shaped.
        ProjectPostType::META_THUMB_TALL => 11,
        ProjectPostType::META_THUMB_CARD => 22,
        ProjectPostType::META_THUMB_HERO => 33,
        default => '',
    });

    $html = ProjectTileImage::render(7, 'm3', 'Alt', 'https://perego.local/legacy.png');

    expect($html)->toContain('<img src="https://perego.local/11.png"')
        ->and($html)->toContain('<source media="(max-width: 900px)" srcset="https://perego.local/22.png" />')
        ->and($html)->toContain('<source media="(max-width: 560px)" srcset="https://perego.local/33.png" />');
});

// The mobile source must come first: a browser takes the first matching <source> and stops.
it('orders the sources narrowest first', function () {
    Functions\when('get_post_meta')->alias(static fn (int $id, string $key) => match ($key) {
        ProjectPostType::META_THUMB_TALL => 11,
        ProjectPostType::META_THUMB_CARD => 22,
        ProjectPostType::META_THUMB_HERO => 33,
        default => '',
    });

    $html = ProjectTileImage::render(7, 'm3', 'Alt');

    expect(strpos($html, '560px'))->toBeLessThan(strpos($html, '900px'));
});

it('falls back to the caller url for a band whose crop is missing', function () {
    Functions\when('get_post_meta')->alias(
        static fn (int $id, string $key) => $key === ProjectPostType::META_THUMB_TALL ? 11 : ''
    );

    $html = ProjectTileImage::render(7, 'm3', 'Alt', 'https://perego.local/legacy.png');

    // The tall crop is the nearest shape for every band once the others are absent, so one file
    // serves all three and the <picture> collapses away again.
    expect($html)->toBe('<img src="https://perego.local/11.png" alt="Alt" loading="lazy" />');
});

it('uses the uniform card shape for the grids that have no mosaic slot', function () {
    Functions\when('get_post_meta')->alias(static fn (int $id, string $key) => match ($key) {
        ProjectPostType::META_THUMB_CARD => 22,
        ProjectPostType::META_THUMB_HERO => 33,
        default => '',
    });

    $html = ProjectTileImage::render(7, '', 'Alt');

    expect($html)->toContain('<img src="https://perego.local/22.png"');
});
