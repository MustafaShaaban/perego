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

it('calls register_post_type on register()', function () {
    Functions\expect('register_post_type')->once()->with(ServicePostType::POST_TYPE, Mockery::type('array'));

    (new ServicePostType())->register();
});
