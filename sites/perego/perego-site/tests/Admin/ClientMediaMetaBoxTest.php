<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Admin\ClientMediaMetaBox;
use PeregoSite\PostTypes\ClientPostType;

beforeEach(function () {
    Functions\when('__')->returnArg();
    Functions\when('wp_unslash')->returnArg();
    Functions\when('sanitize_text_field')->returnArg();
    Functions\when('esc_url_raw')->returnArg();
    Functions\when('absint')->alias(fn ($v) => abs((int) $v));
    Functions\when('wp_is_post_revision')->justReturn(false);
});

function cmMakePost(): object
{
    $post = new WP_Post();
    $post->ID = 61;
    $post->post_type = ClientPostType::POST_TYPE;

    return $post;
}

/**
 * @param array<string, mixed> $postData
 * @return array{updated: array<string,mixed>, deleted: list<string>}
 */
function cmSave(array $postData): array
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
    (new ClientMediaMetaBox())->save(61, cmMakePost());
    $_POST = [];

    return ['updated' => $updated, 'deleted' => $deleted];
}

it('ignores non-client post types', function () {
    Functions\when('wp_verify_nonce')->justReturn(true);
    Functions\when('current_user_can')->justReturn(true);

    $updated = [];
    Functions\when('update_post_meta')->alias(function ($id, $key, $value) use (&$updated) {
        $updated[$key] = $value;
    });

    $post = new WP_Post();
    $post->ID = 5;
    $post->post_type = 'page';
    $_POST = ['perego_client_media_nonce' => 'n', ClientPostType::META_VIDEO_URL => 'https://youtube.com/x'];
    (new ClientMediaMetaBox())->save(5, $post);
    $_POST = [];

    expect($updated)->toBe([]);
});

it('does not save without a valid nonce', function () {
    Functions\when('wp_verify_nonce')->justReturn(false);
    Functions\when('current_user_can')->justReturn(true);

    $result = cmSave(['perego_client_media_nonce' => 'bad', ClientPostType::META_VIDEO_URL => 'https://youtube.com/x']);

    expect($result['updated'])->toBe([])->and($result['deleted'])->toBe([]);
});

it('does not save without the edit capability', function () {
    Functions\when('wp_verify_nonce')->justReturn(true);
    Functions\when('current_user_can')->justReturn(false);

    $result = cmSave(['perego_client_media_nonce' => 'n', ClientPostType::META_VIDEO_URL => 'https://youtube.com/x']);

    expect($result['updated'])->toBe([]);
});

it('stores a clean mixed image/video gallery from the JSON hidden field', function () {
    Functions\when('wp_verify_nonce')->justReturn(true);
    Functions\when('current_user_can')->justReturn(true);

    $json = json_encode([
        ['type' => 'image', 'id' => 12],
        ['type' => 'video', 'url' => 'https://youtube.com/watch?v=abc'],
        ['type' => 'image', 'id' => 0], // dropped
    ]);

    $result = cmSave(['perego_client_media_nonce' => 'n', ClientPostType::META_GALLERY => $json]);

    expect($result['updated'][ClientPostType::META_GALLERY])->toBe([
        ['type' => 'image', 'id' => 12, 'url' => ''],
        ['type' => 'video', 'id' => 0, 'url' => 'https://youtube.com/watch?v=abc'],
    ]);
});

it('deletes the gallery meta when the submitted list is empty or malformed', function () {
    Functions\when('wp_verify_nonce')->justReturn(true);
    Functions\when('current_user_can')->justReturn(true);

    $result = cmSave(['perego_client_media_nonce' => 'n', ClientPostType::META_GALLERY => 'not-json']);

    expect($result['deleted'])->toBe([ClientPostType::META_GALLERY])
        ->and($result['updated'])->toBe([]);
});

it('saves the video url and type together', function () {
    Functions\when('wp_verify_nonce')->justReturn(true);
    Functions\when('current_user_can')->justReturn(true);

    $result = cmSave([
        'perego_client_media_nonce' => 'n',
        ClientPostType::META_VIDEO_URL => 'https://example.com/clip.mp4',
        ClientPostType::META_VIDEO_TYPE => 'upload',
    ]);

    expect($result['updated'][ClientPostType::META_VIDEO_URL])->toBe('https://example.com/clip.mp4')
        ->and($result['updated'][ClientPostType::META_VIDEO_TYPE])->toBe('upload');
});

it('normalizes an unknown submitted video type to embed', function () {
    Functions\when('wp_verify_nonce')->justReturn(true);
    Functions\when('current_user_can')->justReturn(true);

    $result = cmSave([
        'perego_client_media_nonce' => 'n',
        ClientPostType::META_VIDEO_TYPE => 'bogus',
    ]);

    expect($result['updated'][ClientPostType::META_VIDEO_TYPE])->toBe('embed');
});

it('deletes the video url when submitted empty, independent of the gallery/type fields', function () {
    Functions\when('wp_verify_nonce')->justReturn(true);
    Functions\when('current_user_can')->justReturn(true);

    $result = cmSave(['perego_client_media_nonce' => 'n', ClientPostType::META_VIDEO_URL => '']);

    expect($result['deleted'])->toBe([ClientPostType::META_VIDEO_URL]);
});
