<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\PostTypes\ClientPostType;

beforeEach(function () {
    Functions\when('__')->returnArg();
});

it('registers a public client post type with REST + thumbnail support and no archive', function () {
    $args = (new ClientPostType())->postTypeArgs();

    expect($args['public'])->toBeTrue()
        ->and($args['has_archive'])->toBeFalse()
        ->and($args['show_in_rest'])->toBeTrue()
        ->and($args['supports'])->toContain('thumbnail')
        ->and($args['supports'])->toContain('editor')
        ->and($args['rewrite']['slug'])->toBe('clients');
});

it('defines a flat, REST-exposed client-type taxonomy', function () {
    $args = (new ClientPostType())->taxonomyArgs();

    expect($args['hierarchical'])->toBeFalse()
        ->and($args['show_in_rest'])->toBeTrue()
        ->and($args['public'])->toBeTrue();
});

it('names the two client types in display order', function () {
    expect(array_keys(ClientPostType::TYPES))->toBe(['corporate', 'individual'])
        ->and(ClientPostType::POST_TYPE)->toBe('perego_client')
        ->and(ClientPostType::TAXONOMY)->toBe('perego_client_type');
});

it('registers the post type and taxonomy with their canonical slugs on register()', function () {
    $captured = [];
    Functions\expect('register_post_type')
        ->once()
        ->andReturnUsing(function ($slug, $args) use (&$captured) {
            $captured['pt'] = ['slug' => $slug, 'args' => $args];

            return null;
        });
    Functions\expect('register_taxonomy')
        ->once()
        ->andReturnUsing(function ($tax, $object, $args) use (&$captured) {
            $captured['tax'] = ['tax' => $tax, 'object' => $object, 'args' => $args];

            return null;
        });
    Functions\when('register_post_meta')->justReturn(true);

    (new ClientPostType())->register();

    expect($captured['pt']['slug'])->toBe(ClientPostType::POST_TYPE)
        ->and($captured['tax']['tax'])->toBe(ClientPostType::TAXONOMY)
        ->and($captured['tax']['object'])->toBe(ClientPostType::POST_TYPE);
});

it('registers the client statistic, subtitle, video url/type, and gallery meta with REST, sanitization, and auth', function () {
    $meta = (new ClientPostType())->metaArgs();

    expect(array_keys($meta))->toBe([
        ClientPostType::META_STAT,
        ClientPostType::META_SUB,
        ClientPostType::META_VIDEO_URL,
        ClientPostType::META_VIDEO_TYPE,
        ClientPostType::META_GALLERY,
    ]);
    expect($meta[ClientPostType::META_STAT]['sanitize_callback'])->toBe([ClientPostType::class, 'sanitizeStat'])
        ->and($meta[ClientPostType::META_SUB]['sanitize_callback'])->toBe('sanitize_text_field')
        ->and($meta[ClientPostType::META_VIDEO_URL]['sanitize_callback'])->toBe('esc_url_raw')
        ->and($meta[ClientPostType::META_VIDEO_TYPE]['sanitize_callback'])->toBe([ClientPostType::class, 'sanitizeVideoType'])
        ->and($meta[ClientPostType::META_VIDEO_TYPE]['default'])->toBe('embed')
        ->and($meta[ClientPostType::META_GALLERY]['sanitize_callback'])->toBe([ClientPostType::class, 'sanitizeGallery']);

    foreach ($meta as $args) {
        expect($args['show_in_rest'])->not->toBeFalse()
            ->and($args['single'])->toBeTrue()
            ->and($args['auth_callback'])->toBe([ClientPostType::class, 'authEdit']);
    }
});

it('sanitizes the client statistic through a strong-only kses allowlist', function () {
    $captured = null;
    Functions\when('wp_kses')->alias(function (string $raw, array $allowed) use (&$captured) {
        $captured = $allowed;

        return $raw;
    });

    // The allowlist is what keeps the handoff's bold "+1M" while blocking every other tag; assert its
    // shape rather than re-testing WordPress's own kses implementation.
    expect(ClientPostType::sanitizeStat('<strong>+1M</strong> views'))->toBe('<strong>+1M</strong> views')
        ->and($captured)->toBe(['strong' => []]);
});

it('normalizes an unknown video type to the embed default', function () {
    expect(ClientPostType::sanitizeVideoType('embed'))->toBe('embed')
        ->and(ClientPostType::sanitizeVideoType('upload'))->toBe('upload')
        ->and(ClientPostType::sanitizeVideoType('external'))->toBe('external')
        ->and(ClientPostType::sanitizeVideoType('bogus'))->toBe('embed');
});

it('sanitizes a mixed image/video gallery, dropping malformed rows', function () {
    Functions\when('absint')->alias(fn ($v) => abs((int) $v));
    Functions\when('esc_url_raw')->alias(fn ($v) => (string) $v);

    $clean = ClientPostType::sanitizeGallery([
        ['type' => 'image', 'id' => '42'],
        ['type' => 'video', 'url' => 'https://youtube.com/watch?v=abc'],
        ['type' => 'image', 'id' => 0], // dropped: no attachment id
        ['type' => 'video', 'url' => ''], // dropped: empty url
        ['type' => 'bogus', 'url' => 'x'], // dropped: unknown type
        'not-an-array', // dropped: malformed row
    ]);

    expect($clean)->toBe([
        ['type' => 'image', 'id' => 42, 'url' => ''],
        ['type' => 'video', 'id' => 0, 'url' => 'https://youtube.com/watch?v=abc'],
    ]);
});

it('returns an empty gallery for non-array input', function () {
    expect(ClientPostType::sanitizeGallery('not-an-array'))->toBe([]);
});
