<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\LightboxTrigger;

beforeEach(function () {
    Functions\when('esc_url')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_url_raw')->returnArg();
    Functions\when('absint')->alias(fn ($v) => abs((int) $v));
    Functions\when('wp_get_attachment_image_url')->alias(
        fn (int $id) => $id > 0 ? "https://perego.local/uploads/{$id}.png" : ''
    );
});

it('emits nothing at all for an empty gallery with no fallback', function () {
    expect(LightboxTrigger::attribute([]))->toBe('');
});

it('falls back to a single image when the gallery is empty', function () {
    expect(LightboxTrigger::attribute([], 'https://perego.local/uploads/logo.png'))
        ->toBe(' data-image="https://perego.local/uploads/logo.png"');
});

/*
 * A one-entry `data-gallery` would make the lightbox render its next/previous chrome for media that
 * has no siblings, so a lone item uses the single-media attribute its own type calls for.
 */
it('uses data-image for a lone image and data-video for a lone video', function () {
    expect(LightboxTrigger::attribute([['type' => 'image', 'id' => 42, 'url' => '']]))
        ->toBe(' data-image="https://perego.local/uploads/42.png"')
        ->and(LightboxTrigger::attribute([['type' => 'video', 'id' => 0, 'url' => 'https://youtu.be/abc']]))
        ->toBe(' data-video="https://youtu.be/abc"');
});

it('joins a mixed gallery into one ordered data-gallery list', function () {
    $attribute = LightboxTrigger::attribute([
        ['type' => 'image', 'id' => 42, 'url' => ''],
        ['type' => 'video', 'id' => 0, 'url' => 'https://youtu.be/abc'],
        ['type' => 'image', 'id' => 43, 'url' => ''],
    ]);

    expect($attribute)->toBe(
        ' data-gallery="https://perego.local/uploads/42.png,https://youtu.be/abc,https://perego.local/uploads/43.png"'
    );
});

/*
 * `media-lightbox/view.js` splits this attribute on commas. A media URL may legitimately contain one,
 * which would tear a single item into two broken slides — percent-encoding is transparent to every
 * URL parser and invisible to the split.
 */
it('percent-encodes commas inside URLs so an item cannot be split in half', function () {
    $attribute = LightboxTrigger::attribute([
        ['type' => 'video', 'id' => 0, 'url' => 'https://cdn.test/a,b.mp4'],
        ['type' => 'video', 'id' => 0, 'url' => 'https://cdn.test/c.mp4'],
    ]);

    expect($attribute)->toBe(' data-gallery="https://cdn.test/a%2Cb.mp4,https://cdn.test/c.mp4"');
});

// A deleted attachment must not leave an empty slide the visitor has to page past.
it('drops a row whose attachment no longer resolves', function () {
    Functions\when('wp_get_attachment_image_url')->alias(fn (int $id) => $id === 42 ? 'https://perego.local/uploads/42.png' : '');

    $attribute = LightboxTrigger::attribute([
        ['type' => 'image', 'id' => 42, 'url' => ''],
        ['type' => 'image', 'id' => 99, 'url' => ''], // attachment gone
    ]);

    expect($attribute)->toBe(' data-image="https://perego.local/uploads/42.png"');
});

it('falls back to the image when every gallery row is unresolvable', function () {
    Functions\when('wp_get_attachment_image_url')->justReturn('');

    expect(LightboxTrigger::attribute([['type' => 'image', 'id' => 99, 'url' => '']], 'https://perego.local/logo.png'))
        ->toBe(' data-image="https://perego.local/logo.png"');
});

it('drops malformed rows through the post type sanitizer rather than emitting them', function () {
    $attribute = LightboxTrigger::attribute([
        ['type' => 'bogus', 'url' => 'https://cdn.test/x.mp4'],
        ['type' => 'video', 'id' => 0, 'url' => ''],
        ['type' => 'video', 'id' => 0, 'url' => 'https://cdn.test/ok.mp4'],
    ]);

    expect($attribute)->toBe(' data-video="https://cdn.test/ok.mp4"');
});
