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
    Functions\expect('register_post_type')->once()->with(ProjectPostType::POST_TYPE, Mockery::type('array'));
    Functions\expect('register_taxonomy')->once()->with(
        ProjectPostType::TAXONOMY,
        ProjectPostType::POST_TYPE,
        Mockery::type('array')
    );

    (new ProjectPostType())->register();
});
