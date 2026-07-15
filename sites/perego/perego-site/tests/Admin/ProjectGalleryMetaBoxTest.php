<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Admin\ProjectGalleryMetaBox;
use PeregoSite\PostTypes\ProjectPostType;

beforeEach(function () {
    Functions\when('__')->returnArg();
    Functions\when('wp_unslash')->returnArg();
    Functions\when('sanitize_text_field')->returnArg();
    Functions\when('wp_is_post_revision')->justReturn(false);
});

function pgMakePost(): object
{
    $post = new WP_Post();
    $post->ID = 91;
    $post->post_type = ProjectPostType::POST_TYPE;

    return $post;
}

/**
 * @param array<string, mixed> $post
 * @return array{updated: array<string,mixed>, deleted: list<string>}
 */
function pgSave(array $postData): array
{
    $updated = [];
    $deleted = [];
    Functions\when('update_post_meta')->alias(function ($id, $key, $value) use (&$updated) {
        $updated[$key] = $value;

        return true;
    });
    Functions\when('delete_post_meta')->alias(function ($id, $key) use (&$deleted) {
        $deleted[] = $key;

        return true;
    });

    $_POST = $postData;
    (new ProjectGalleryMetaBox())->save(91, pgMakePost());
    $_POST = [];

    return ['updated' => $updated, 'deleted' => $deleted];
}

it('ignores non-project post types', function () {
    Functions\when('wp_verify_nonce')->justReturn(true);
    Functions\when('current_user_can')->justReturn(true);

    $updated = [];
    Functions\when('update_post_meta')->alias(function ($id, $key, $value) use (&$updated) {
        $updated[$key] = $value;
    });
    Functions\when('delete_post_meta')->justReturn(true);

    $post = new WP_Post();
    $post->ID = 5;
    $post->post_type = 'page';
    $_POST = ['perego_gallery_nonce' => 'n', ProjectPostType::META_GALLERY => '1,2'];
    (new ProjectGalleryMetaBox())->save(5, $post);
    $_POST = [];

    expect($updated)->toBe([]);
});

it('does not save without a valid nonce', function () {
    Functions\when('wp_verify_nonce')->justReturn(false);
    Functions\when('current_user_can')->justReturn(true);

    $result = pgSave(['perego_gallery_nonce' => 'bad', ProjectPostType::META_GALLERY => '1,2,3']);

    expect($result['updated'])->toBe([])->and($result['deleted'])->toBe([]);
});

it('does not save without the edit capability', function () {
    Functions\when('wp_verify_nonce')->justReturn(true);
    Functions\when('current_user_can')->justReturn(false);

    $result = pgSave(['perego_gallery_nonce' => 'n', ProjectPostType::META_GALLERY => '1,2,3']);

    expect($result['updated'])->toBe([]);
});

it('explodes the comma list and stores a clean positive-int gallery when authorised', function () {
    Functions\when('wp_verify_nonce')->justReturn(true);
    Functions\when('current_user_can')->justReturn(true);

    $result = pgSave(['perego_gallery_nonce' => 'n', ProjectPostType::META_GALLERY => '114, 118 ,0,-3,117,abc']);

    expect($result['updated'][ProjectPostType::META_GALLERY])->toBe([114, 118, 117])
        ->and($result['deleted'])->toBe([]);
});

it('deletes the gallery meta when the submitted list is empty', function () {
    Functions\when('wp_verify_nonce')->justReturn(true);
    Functions\when('current_user_can')->justReturn(true);

    $result = pgSave(['perego_gallery_nonce' => 'n', ProjectPostType::META_GALLERY => '']);

    expect($result['deleted'])->toBe([ProjectPostType::META_GALLERY])
        ->and($result['updated'])->toBe([]);
});
