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

it('registers the client statistic and video-url meta with REST, sanitization, and auth', function () {
    $meta = (new ClientPostType())->metaArgs();

    expect(array_keys($meta))->toBe([ClientPostType::META_STAT, ClientPostType::META_VIDEO_URL]);
    expect($meta[ClientPostType::META_STAT]['sanitize_callback'])->toBe('sanitize_text_field')
        ->and($meta[ClientPostType::META_VIDEO_URL]['sanitize_callback'])->toBe('esc_url_raw');

    foreach ($meta as $args) {
        expect($args['show_in_rest'])->toBeTrue()
            ->and($args['single'])->toBeTrue()
            ->and($args['auth_callback'])->toBe([ClientPostType::class, 'authEdit']);
    }
});
