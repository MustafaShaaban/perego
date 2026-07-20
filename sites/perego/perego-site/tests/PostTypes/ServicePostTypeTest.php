<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\PostTypes\ServicePostType;

beforeEach(function () {
    Functions\when('__')->returnArg();
});

it('registers a public service post type with the /services/ archive disabled (singles only)', function () {
    $args = (new ServicePostType())->postTypeArgs();

    // spec 020: the archive is retired (404); the 4 service singles at /services/<slug> are canonical.
    expect($args['public'])->toBeTrue()
        ->and($args['has_archive'])->toBeFalse()
        ->and($args['rewrite']['slug'])->toBe('services')
        ->and($args['show_in_rest'])->toBeTrue()
        ->and($args['supports'])->toContain('thumbnail');
});

it('defines the four services with slugs matching the header routes', function () {
    expect(array_keys(ServicePostType::SERVICES))->toBe([
        'video-editing',
        'motion-graphics',
        'graphic-design',
        'website-making',
    ])->and(ServicePostType::SERVICES['video-editing'])->toBe('Video Editing & Post-Production');
});

it('calls register_post_type with the service slug and its args on register()', function () {
    $captured = [];
    Functions\expect('register_post_type')
        ->once()
        ->andReturnUsing(function ($slug, $args) use (&$captured) {
            $captured = ['slug' => $slug, 'args' => $args];

            return null;
        });
    Functions\when('register_post_meta')->justReturn(true);

    (new ServicePostType())->register();

    expect($captured['slug'])->toBe(ServicePostType::POST_TYPE)
        ->and($captured['args'])->toBeArray()
        ->and($captured['args']['has_archive'])->toBeFalse();
});

it('registers the canonical service-slug meta with REST, sanitization, and auth', function () {
    $meta = (new ServicePostType())->metaArgs();

    expect($meta[ServicePostType::META_SERVICE_SLUG]['show_in_rest'])->toBeTrue()
        ->and($meta[ServicePostType::META_SERVICE_SLUG]['sanitize_callback'])->toBe('sanitize_key')
        ->and($meta[ServicePostType::META_SERVICE_SLUG]['auth_callback'])->toBe([ServicePostType::class, 'authEdit']);
});

it('registers the homepage services-teaser presentation meta (label, image id, alt) with REST + auth', function () {
    $meta = (new ServicePostType())->metaArgs();

    expect(array_keys($meta))->toBe([
        ServicePostType::META_SERVICE_SLUG,
        ServicePostType::META_TEASER_LABEL,
        ServicePostType::META_TEASER_IMAGE_ID,
        ServicePostType::META_TEASER_ALT,
    ]);

    expect($meta[ServicePostType::META_TEASER_LABEL]['sanitize_callback'])->toBe('sanitize_text_field')
        ->and($meta[ServicePostType::META_TEASER_LABEL]['show_in_rest'])->toBeTrue()
        ->and($meta[ServicePostType::META_TEASER_IMAGE_ID]['type'])->toBe('integer')
        ->and($meta[ServicePostType::META_TEASER_IMAGE_ID]['sanitize_callback'])->toBe('absint')
        ->and($meta[ServicePostType::META_TEASER_ALT]['sanitize_callback'])->toBe('sanitize_text_field')
        ->and($meta[ServicePostType::META_TEASER_ALT]['auth_callback'])->toBe([ServicePostType::class, 'authEdit']);
});
