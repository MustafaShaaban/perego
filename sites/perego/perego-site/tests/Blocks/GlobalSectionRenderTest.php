<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\GlobalSectionRenderer;
use PeregoSite\Content\GlobalSectionResolver;

beforeEach(function () {
    Functions\when('__')->returnArg();
    Functions\when('esc_html')->returnArg();
    Functions\when('sanitize_key')->alias(fn ($v) => strtolower(preg_replace('/[^a-z0-9_\-]/', '', (string) $v)));
    Functions\when('do_blocks')->returnArg();
    // Two published header records, one per language.
    Functions\when('get_posts')->justReturn([12, 13]);
    Functions\when('get_post_meta')->justReturn('header');
    Functions\when('pll_get_post_language')->alias(fn ($id) => $id === 12 ? 'en' : 'ar');
    Functions\when('get_post')->alias(fn ($id) => (object) [
        'post_content' => "<!-- wp:paragraph --><p>Record {$id}</p><!-- /wp:paragraph -->",
    ]);
});

function renderSection(string $locale, string $role = 'header'): string
{
    return (new GlobalSectionRenderer($locale, new GlobalSectionResolver()))->render(['role' => $role]);
}

it('renders the current-language record block content for the requested role', function () {
    expect(renderSection('en'))->toContain('<p>Record 12</p>')
        ->and(renderSection('ar'))->toContain('<p>Record 13</p>');
});

it('renders nothing for a visitor when the requested language has no record (no mixing)', function () {
    Functions\when('get_posts')->justReturn([12]);            // only the EN record exists
    Functions\when('pll_get_post_language')->justReturn('en');
    Functions\when('current_user_can')->justReturn(false);

    expect(renderSection('ar'))->toBe('');
});

it('shows an admin-visible notice when the requested language record is missing', function () {
    Functions\when('get_posts')->justReturn([12]);
    Functions\when('pll_get_post_language')->justReturn('en');
    Functions\when('current_user_can')->justReturn(true);

    $html = renderSection('ar');

    expect($html)->toContain('header')
        ->and($html)->toContain('ar')
        ->and($html)->toContain('perego-global-section--missing');
});

it('renders nothing when no role is given', function () {
    expect(renderSection('en', ''))->toBe('');
});
