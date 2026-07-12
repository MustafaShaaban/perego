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

    (new ClientPostType())->register();

    expect($captured['pt']['slug'])->toBe(ClientPostType::POST_TYPE)
        ->and($captured['tax']['tax'])->toBe(ClientPostType::TAXONOMY)
        ->and($captured['tax']['object'])->toBe(ClientPostType::POST_TYPE);
});
