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

it('registers the project_category taxonomy as hierarchical and REST enabled', function () {
    $args = (new ProjectPostType())->taxonomyArgs();

    expect($args['hierarchical'])->toBeTrue()
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
    Functions\when('register_post_meta')->justReturn(true);

    (new ProjectPostType())->register();

    expect($captured['pt']['slug'])->toBe(ProjectPostType::POST_TYPE)
        ->and($captured['pt']['args'])->toBeArray()
        ->and($captured['tax']['tax'])->toBe(ProjectPostType::TAXONOMY)
        ->and($captured['tax']['object'])->toBe(ProjectPostType::POST_TYPE);
});

it('registers every structured meta field with REST, sanitization, and auth', function () {
    $meta = (new ProjectPostType())->metaArgs();

    expect(array_keys($meta))->toBe([
        ProjectPostType::META_CLIENT,
        ProjectPostType::META_YEAR,
        ProjectPostType::META_ROLE,
        ProjectPostType::META_DELIVERABLES,
        ProjectPostType::META_SITE_TYPE,
        ProjectPostType::META_SITE_URL,
        ProjectPostType::META_VIDEO_URL,
        ProjectPostType::META_LOGO,
        ProjectPostType::META_GALLERY,
        ProjectPostType::META_ICON,
        ProjectPostType::META_THUMB_HERO,
        ProjectPostType::META_THUMB_BANNER,
        ProjectPostType::META_THUMB_TALL,
        ProjectPostType::META_THUMB_CARD,
    ]);

    foreach ($meta as $args) {
        expect($args['single'])->toBeTrue()
            ->and($args['show_in_rest'])->not->toBeFalse()
            ->and($args)->toHaveKey('sanitize_callback')
            ->and($args['auth_callback'])->toBe([ProjectPostType::class, 'authEdit']);
    }

    // The gallery is a typed integer list, not a scalar; the logo is an attachment id.
    expect($meta[ProjectPostType::META_GALLERY]['type'])->toBe('array')
        ->and($meta[ProjectPostType::META_CLIENT]['type'])->toBe('string')
        ->and($meta[ProjectPostType::META_LOGO]['type'])->toBe('integer')
        ->and($meta[ProjectPostType::META_LOGO]['sanitize_callback'])->toBe('absint');

    // The showcase fields carry their own dedicated sanitizers.
    expect($meta[ProjectPostType::META_SITE_TYPE]['sanitize_callback'])->toBe([ProjectPostType::class, 'sanitizeSiteType'])
        ->and($meta[ProjectPostType::META_SITE_URL]['sanitize_callback'])->toBe('esc_url_raw');

    // Per-shape crops are attachment ids, like the logo.
    expect($meta[ProjectPostType::META_THUMB_HERO]['type'])->toBe('integer')
        ->and($meta[ProjectPostType::META_THUMB_HERO]['sanitize_callback'])->toBe('absint');
});

/*
 * A registered default is returned by `get_post_meta()` for a key that was never written, which makes
 * "unset" indistinguishable from "explicitly none/zero". That breaks the migration's already-done
 * marker and stops the English-translation fallback, so an Arabic project stops inheriting its English
 * record's icon and crops. Established the hard way on the Client behaviour field; pinned here so the
 * next person to "tidy up" these registrations by adding defaults fails loudly instead.
 */
it('declares no default on the icon or the per-shape crops', function () {
    $meta = (new ProjectPostType())->metaArgs();

    foreach ([
        ProjectPostType::META_ICON,
        ProjectPostType::META_THUMB_HERO,
        ProjectPostType::META_THUMB_BANNER,
        ProjectPostType::META_THUMB_TALL,
        ProjectPostType::META_THUMB_CARD,
    ] as $key) {
        expect($meta[$key])->not->toHaveKey('default');
    }
});

it('normalizes an unknown icon to no icon, so a tile never advertises by accident', function () {
    expect(ProjectPostType::sanitizeIcon('play'))->toBe('play')
        ->and(ProjectPostType::sanitizeIcon('gallery'))->toBe('gallery')
        ->and(ProjectPostType::sanitizeIcon('none'))->toBe('none')
        ->and(ProjectPostType::sanitizeIcon('bogus'))->toBe('none')
        ->and(ProjectPostType::sanitizeIcon(''))->toBe('none');
});

it('whitelists the site type to the fixed showcase-filter enum', function () {
    expect(ProjectPostType::sanitizeSiteType('ecommerce'))->toBe('ecommerce')
        ->and(ProjectPostType::sanitizeSiteType(' Corporate '))->toBe('corporate')
        ->and(ProjectPostType::sanitizeSiteType('WEBAPP'))->toBe('webapp')
        ->and(ProjectPostType::sanitizeSiteType('shady<script>'))->toBe('')
        ->and(ProjectPostType::sanitizeSiteType(''))->toBe('')
        ->and(ProjectPostType::sanitizeSiteType(['array']))->toBe('');
});

it('sanitizes a gallery value to a clean list of positive attachment IDs', function () {
    expect(ProjectPostType::sanitizeIntList(['118', 117, 0, -3, 'x', 116]))->toBe([118, 117, 116])
        ->and(ProjectPostType::sanitizeIntList('42'))->toBe([42])
        ->and(ProjectPostType::sanitizeIntList([]))->toBe([]);
});
