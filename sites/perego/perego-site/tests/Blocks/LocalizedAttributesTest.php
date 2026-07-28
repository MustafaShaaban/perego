<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\Blocks\LocalizedAttributes;

$attributes = [
    'headingEn' => 'Our Work',
    'headingAr' => 'أعمالنا',
    'introEn' => '',
    'introAr' => 'مقدمة',
    'ctaButtonEn' => '  ',
];

it('picks the English half by default', function () use ($attributes) {
    expect(LocalizedAttributes::pick($attributes, 'en', ['heading']))->toBe(['heading' => 'Our Work']);
});

it('picks the Arabic half for the ar locale', function () use ($attributes) {
    expect(LocalizedAttributes::pick($attributes, 'ar', ['heading', 'intro']))
        ->toBe(['heading' => 'أعمالنا', 'intro' => 'مقدمة']);
});

it('treats any unknown locale as English, matching the renderers', function () use ($attributes) {
    expect(LocalizedAttributes::pick($attributes, 'fr', ['heading']))->toBe(['heading' => 'Our Work']);
});

it('omits empty, whitespace-only, missing, and non-string values so callers can merge over seed copy', function () use ($attributes) {
    expect(LocalizedAttributes::pick($attributes, 'en', ['intro', 'ctaButton', 'ctaTitle']))->toBe([])
        ->and(LocalizedAttributes::pick(['headingEn' => 42], 'en', ['heading']))->toBe([]);
});

it('returns an empty array when no keys are requested', function () use ($attributes) {
    expect(LocalizedAttributes::pick($attributes, 'en', []))->toBe([]);
});
