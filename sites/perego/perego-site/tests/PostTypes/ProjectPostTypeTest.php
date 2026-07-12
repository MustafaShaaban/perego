<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\PostTypes\ProjectPostType;

beforeEach(function () {
    Functions\when('__')->returnArg();
});

it('registers a public project post type with a work archive and REST enabled', function () {
    $args = (new ProjectPostType())->postTypeArgs();

    expect($args['public'])->toBeTrue()
        ->and($args['has_archive'])->toBe('work')
        ->and($args['show_in_rest'])->toBeTrue()
        ->and($args['rewrite']['slug'])->toBe('work')
        ->and($args['supports'])->toContain('thumbnail')
        ->and($args['supports'])->toContain('excerpt');
});

it('registers the project_category taxonomy as flat and REST enabled', function () {
    $args = (new ProjectPostType())->taxonomyArgs();

    expect($args['hierarchical'])->toBeFalse()
        ->and($args['public'])->toBeTrue()
        ->and($args['show_in_rest'])->toBeTrue();
});

it('defines exactly the four service categories in the CONTENT_MODEL enum order', function () {
    expect(array_keys(ProjectPostType::CATEGORIES))->toBe(['video', 'motion', 'design', 'web'])
        ->and(ProjectPostType::CATEGORIES['video'])->toBe('Video Editing')
        ->and(ProjectPostType::CATEGORIES['web'])->toBe('Website Making');
});

it('calls register_post_type and register_taxonomy on register()', function () {
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

    (new ProjectPostType())->register();

    expect($captured['pt']['slug'])->toBe(ProjectPostType::POST_TYPE)
        ->and($captured['pt']['args'])->toBeArray()
        ->and($captured['tax']['tax'])->toBe(ProjectPostType::TAXONOMY)
        ->and($captured['tax']['object'])->toBe(ProjectPostType::POST_TYPE);
});
