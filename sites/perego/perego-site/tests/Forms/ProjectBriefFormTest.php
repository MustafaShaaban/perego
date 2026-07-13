<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Forms\ProjectBriefForm;

if (! class_exists('WP_Post')) {
    class WP_Post
    {
        public int $ID = 0;

        public string $post_name = '';
    }
}

beforeEach(function () {
    Functions\when('__')->returnArg();
    Functions\when('sanitize_key')->alias(fn ($v) => strtolower(preg_replace('/[^a-z0-9_\-]/', '', (string) $v)));
    Functions\when('wp_unslash')->returnArg();
    unset($_GET['service']);
});

afterEach(function () {
    unset($_GET['service']);
});

function briefFields(): array
{
    // No get_posts defined -> the form falls back to the fixed four services.
    return (new ProjectBriefForm())->fields();
}

it('exposes the perego-project-brief slug', function () {
    expect((new ProjectBriefForm())->slug)->toBe('perego-project-brief')
        ->and(ProjectBriefForm::SLUG)->toBe('perego-project-brief');
});

it('mirrors the handoff contact fields in order', function () {
    Functions\when('get_posts')->justReturn([]);

    expect(array_keys(briefFields()))->toBe([
        'name', 'email', 'phone', 'company', 'budget', 'subject', 'message', 'services',
    ]);
});

it('keeps the handoff validation limits on every field', function () {
    Functions\when('get_posts')->justReturn([]);
    $fields = briefFields();

    expect($fields['name']['rules'])->toBe(['required', 'min:2', 'max:80'])
        ->and($fields['email']['rules'])->toBe(['required', 'email', 'max:120'])
        ->and($fields['phone']['rules'])->toBe(['max:24'])
        ->and($fields['subject']['rules'])->toBe(['required', 'min:3', 'max:120'])
        ->and($fields['message']['rules'])->toBe(['required', 'min:10', 'max:1200'])
        ->and($fields['services']['rules'])->toBe(['required']);
});

it('offers the exact budget ranges from the handoff, in order', function () {
    Functions\when('get_posts')->justReturn([]);

    expect(array_keys(briefFields()['budget']['options']))->toBe([
        '', 'under-1k', '1k-5k', '5k-15k', '15k-plus', 'not-sure',
    ]);
});

it('falls back to the four fixed services when none are published', function () {
    Functions\when('get_posts')->justReturn([]);

    expect(array_keys(briefFields()['services']['options']))->toBe([
        'video-editing', 'motion-graphics', 'graphic-design', 'website-making',
    ]);
});

it('builds the chooser from published services keyed by their canonical slug', function () {
    $post = new WP_Post();
    $post->ID = 7;
    $post->post_name = 'video-editing-2'; // Polylang-deduplicated slug

    Functions\when('get_posts')->justReturn([$post]);
    Functions\when('get_post_meta')->justReturn('video-editing'); // canonical slug from meta
    Functions\when('get_the_title')->justReturn('Video Editing');

    $options = (new ProjectBriefForm())->fields()['services']['options'];

    expect($options)->toBe(['video-editing' => 'Video Editing']);
});

it('preselects a valid ?service= and ignores a spoofed one', function () {
    Functions\when('get_posts')->justReturn([]);

    $_GET['service'] = 'graphic-design';
    expect((new ProjectBriefForm())->fields()['services']['default_value'])->toBe(['graphic-design']);

    $_GET['service'] = 'evil-slug';
    expect((new ProjectBriefForm())->fields()['services']['default_value'])->toBe([]);
});
