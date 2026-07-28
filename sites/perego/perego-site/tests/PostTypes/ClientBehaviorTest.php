<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\PostTypes\ClientBehavior;

/*
 * The migration deletes the fields these answers are derived from, so a wrong answer here is both
 * silent and permanent: a working card goes static, or a tile nobody meant to be clickable becomes a
 * button — and the evidence is gone. Every branch is pinned.
 */

$video = static fn (string $url): array => ['type' => 'video', 'id' => 0, 'url' => $url];
$image = static fn (int $id): array => ['type' => 'image', 'id' => $id, 'url' => ''];

it('turns an external video into a link, leaving the gallery alone', function () use ($video) {
    $result = ClientBehavior::derive('https://vimeo.com/x', 'external', [$video('https://youtu.be/a')]);

    expect($result['behavior'])->toBe('link')
        ->and($result['linkUrl'])->toBe('https://vimeo.com/x')
        ->and($result['gallery'])->toBe([$video('https://youtu.be/a')]);
});

it('turns an embedded or uploaded video into a lightbox gallery entry', function (string $type) use ($video) {
    $result = ClientBehavior::derive('https://youtu.be/a', $type, []);

    expect($result['behavior'])->toBe('lightbox')
        ->and($result['gallery'])->toBe([$video('https://youtu.be/a')])
        ->and($result['linkUrl'])->toBe('');
})->with(['embed', 'upload']);

// The card's own video was the thing a visitor saw first; the gallery followed it.
it('puts the card\'s own video ahead of an existing gallery', function () use ($video, $image) {
    $result = ClientBehavior::derive('https://youtu.be/a', 'embed', [$image(42)]);

    expect($result['gallery'])->toBe([$video('https://youtu.be/a'), $image(42)]);
});

it('does not add the video twice when the gallery already lists it', function () use ($video) {
    $gallery = [$video('https://youtu.be/a')];
    $result = ClientBehavior::derive('https://youtu.be/a', 'embed', $gallery);

    expect($result['gallery'])->toBe($gallery);
});

it('turns a gallery with no card video into a lightbox', function () use ($image) {
    $result = ClientBehavior::derive('', '', [$image(42)]);

    expect($result['behavior'])->toBe('lightbox')
        ->and($result['gallery'])->toBe([$image(42)]);
});

/*
 * The behaviour change the owner explicitly confirmed: a corporate tile with no gallery used to open
 * its own logo image in the lightbox. It becomes static, which is what "No actions" means.
 */
it('turns a client with neither video nor gallery into a static card', function () {
    $result = ClientBehavior::derive('', '', []);

    expect($result['behavior'])->toBe('none')
        ->and($result['gallery'])->toBe([])
        ->and($result['linkUrl'])->toBe('');
});

it('treats a whitespace-only video URL as no video at all', function () {
    expect(ClientBehavior::derive('   ', 'embed', [])['behavior'])->toBe('none');
});

// An unknown legacy type is not `external`, so it took the lightbox path — as it did before.
it('sends an unrecognised legacy video type down the lightbox path', function () use ($video) {
    $result = ClientBehavior::derive('https://youtu.be/a', 'bogus', []);

    expect($result['behavior'])->toBe('lightbox')
        ->and($result['gallery'])->toBe([$video('https://youtu.be/a')]);
});
