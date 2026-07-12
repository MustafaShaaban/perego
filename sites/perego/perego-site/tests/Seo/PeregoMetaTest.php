<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Seo\PeregoMeta;

beforeEach(function () {
    Functions\when('wp_strip_all_tags')->alias(fn ($s) => trim(strip_tags((string) $s)));
});

it('uses a singular page excerpt as the description, trimmed and tag-stripped', function () {
    $meta = new PeregoMeta('en');

    $desc = $meta->describe(true, '<p>A short summary of the project.</p>', 'Brand fallback.');

    expect($desc)->toBe('A short summary of the project.');
});

it('falls back to the brand description on a singular page with no excerpt', function () {
    $meta = new PeregoMeta('en');

    expect($meta->describe(true, '   ', 'Brand fallback.'))->toBe('Brand fallback.');
});

it('uses the brand description on non-singular routes (front, archives, search, 404)', function () {
    $meta = new PeregoMeta('en');

    expect($meta->describe(false, 'ignored excerpt', 'Brand fallback.'))->toBe('Brand fallback.');
});

it('caps an over-long excerpt at 160 characters', function () {
    $meta = new PeregoMeta('en');
    $long = str_repeat('word ', 60); // 300 chars

    $desc = $meta->describe(true, $long, 'Brand fallback.');

    expect(mb_strlen($desc))->toBeLessThanOrEqual(160)
        ->and($desc)->toEndWith('…');
});

it('provides a locale-specific brand description', function () {
    expect((new PeregoMeta('en'))->brandDescription())->toContain('Perego')
        ->and((new PeregoMeta('ar'))->brandDescription())->toContain('بيريجو');
});
