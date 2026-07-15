<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Admin\PostMetaBoxes;
use PeregoSite\Content\HeroContent;
use PeregoSite\PostTypes\ClientPostType;
use PeregoSite\PostTypes\ProjectPostType;
use PeregoSite\PostTypes\ServicePostType;

beforeEach(function () {
    Functions\when('__')->returnArg();
    Functions\when('wp_unslash')->returnArg();
    Functions\when('sanitize_text_field')->returnArg();
    Functions\when('sanitize_key')->alias(fn ($v) => strtolower(preg_replace('/[^a-z0-9_\-]/', '', (string) $v)));
    Functions\when('esc_url_raw')->returnArg();
    Functions\when('wp_is_post_revision')->justReturn(false);
    $_POST = [];
});

afterEach(function () {
    $_POST = [];
});

function pmbMakePost(string $type): object
{
    return (object) ['ID' => 84, 'post_type' => $type];
}

/**
 * Run save() with update/delete stubbed to capture calls. Returns [updated, deleted].
 *
 * @param array<string,mixed> $post_data
 * @return array{updated: array<string,mixed>, deleted: list<string>}
 */
function pmbSave(string $type, array $post_data): array
{
    $_POST = $post_data;
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

    (new PostMetaBoxes())->save(84, pmbMakePost($type));

    return ['updated' => $updated, 'deleted' => $deleted];
}

it('covers the three collection CPTs plus the front-page hero, with scalar fields', function () {
    $schema = (new PostMetaBoxes())->schema();

    expect(array_keys($schema))->toBe([
        ProjectPostType::POST_TYPE,
        ServicePostType::POST_TYPE,
        ClientPostType::POST_TYPE,
        'page',
    ]);
    expect(array_keys($schema[ProjectPostType::POST_TYPE]['fields']))->toContain(ProjectPostType::META_CLIENT)
        ->and(array_keys($schema[ClientPostType::POST_TYPE]['fields']))->toContain(ClientPostType::META_VIDEO_URL)
        ->and(array_keys($schema['page']['fields']))->toContain(HeroContent::META_CTA);
});

it('ignores post types it does not manage', function () {
    Functions\when('wp_verify_nonce')->justReturn(true);
    Functions\when('current_user_can')->justReturn(true);

    $result = pmbSave('post', ['perego_meta_nonce' => 'n', ProjectPostType::META_CLIENT => 'Acme']);

    expect($result['updated'])->toBe([])->and($result['deleted'])->toBe([]);
});

it('does not save without a valid nonce', function () {
    Functions\when('wp_verify_nonce')->justReturn(false);
    Functions\when('current_user_can')->justReturn(true);

    $result = pmbSave(ProjectPostType::POST_TYPE, [ProjectPostType::META_CLIENT => 'Acme']);

    expect($result['updated'])->toBe([]);
});

it('does not save when the user lacks the edit capability', function () {
    Functions\when('wp_verify_nonce')->justReturn(true);
    Functions\when('current_user_can')->justReturn(false);

    $result = pmbSave(ProjectPostType::POST_TYPE, ['perego_meta_nonce' => 'n', ProjectPostType::META_CLIENT => 'Acme']);

    expect($result['updated'])->toBe([]);
});

it('saves sanitised values for present fields when authorised', function () {
    Functions\when('wp_verify_nonce')->justReturn(true);
    Functions\when('current_user_can')->justReturn(true);

    $result = pmbSave(ProjectPostType::POST_TYPE, [
        'perego_meta_nonce' => 'n',
        ProjectPostType::META_CLIENT => 'Acme Studios',
        ProjectPostType::META_YEAR => '2026',
    ]);

    expect($result['updated'])->toBe([
        ProjectPostType::META_CLIENT => 'Acme Studios',
        ProjectPostType::META_YEAR => '2026',
    ]);
});

it('deletes a field when its submitted value is emptied', function () {
    Functions\when('wp_verify_nonce')->justReturn(true);
    Functions\when('current_user_can')->justReturn(true);

    $result = pmbSave(ClientPostType::POST_TYPE, ['perego_meta_nonce' => 'n', ClientPostType::META_VIDEO_URL => '']);

    expect($result['deleted'])->toBe([ClientPostType::META_VIDEO_URL]);
});
