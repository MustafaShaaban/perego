<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\PostTypes\GlobalSectionPostType;

beforeEach(function () {
    Functions\when('__')->returnArg();
});

it('registers a private, editor-visible, REST-enabled post type (no public front-end route)', function () {
    $args = (new GlobalSectionPostType())->postTypeArgs();

    expect($args['public'])->toBeFalse()
        ->and($args['show_ui'])->toBeTrue()
        ->and($args['show_in_rest'])->toBeTrue()
        ->and($args['publicly_queryable'])->toBeFalse()
        ->and($args['has_archive'])->toBeFalse()
        ->and($args['rewrite'])->toBeFalse()
        ->and($args['supports'])->toContain('title')
        ->and($args['supports'])->toContain('editor')
        ->and($args['supports'])->toContain('revisions');
});

it('keeps the post type name within WordPress’s 20-character limit', function () {
    // register_post_type rejects names longer than 20 chars — a silent registration failure.
    expect(strlen(GlobalSectionPostType::POST_TYPE))->toBeLessThanOrEqual(20)
        ->and(GlobalSectionPostType::POST_TYPE)->toStartWith('perego_');
});

it('defines the controlled section roles required by the handoff shell', function () {
    expect(array_keys(GlobalSectionPostType::ROLES))->toBe([
        'header',
        'standard-footer',
        'contact-footer',
        'global-cta',
        'contact-details',
        'not-found',
    ]);
});

it('exposes the section-role meta to REST as a single sanitized string', function () {
    $meta = (new GlobalSectionPostType())->roleMetaArgs();

    expect($meta['single'])->toBeTrue()
        ->and($meta['type'])->toBe('string')
        ->and($meta['show_in_rest'])->toBeTrue()
        ->and($meta['sanitize_callback'])->toBe('sanitize_key');
});

it('registers the post type and its role meta on register()', function () {
    $captured = [];
    Functions\expect('register_post_type')->once()
        ->andReturnUsing(function ($slug, $args) use (&$captured) {
            $captured['pt'] = ['slug' => $slug, 'args' => $args];

            return null;
        });
    Functions\expect('register_post_meta')->once()
        ->andReturnUsing(function ($pt, $key, $args) use (&$captured) {
            $captured['meta'] = ['pt' => $pt, 'key' => $key, 'args' => $args];

            return true;
        });

    (new GlobalSectionPostType())->register();

    expect($captured['pt']['slug'])->toBe(GlobalSectionPostType::POST_TYPE)
        ->and($captured['meta']['pt'])->toBe(GlobalSectionPostType::POST_TYPE)
        ->and($captured['meta']['key'])->toBe(GlobalSectionPostType::META_ROLE);
});
