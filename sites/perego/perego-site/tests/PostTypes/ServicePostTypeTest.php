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

it('registers a public service post type with a services archive at /services/', function () {
    $args = (new ServicePostType())->postTypeArgs();

    expect($args['public'])->toBeTrue()
        ->and($args['has_archive'])->toBe('services')
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

    (new ServicePostType())->register();

    expect($captured['slug'])->toBe(ServicePostType::POST_TYPE)
        ->and($captured['args'])->toBeArray()
        ->and($captured['args']['has_archive'])->toBe('services');
});
