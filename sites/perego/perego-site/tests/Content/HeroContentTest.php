<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Content\HeroContent;

it('returns the HomeContent seed when there is no front page', function () {
    $hero = (new HeroContent())->resolve(0, 'en');

    expect($hero['slides'])->toHaveCount(3)
        ->and($hero['slides'][0]['title'])->toBe('What We Believe')
        ->and($hero['cta'])->toBe('Say Hello!');
});

it('overlays only the front page meta that is set, per field, over the seed', function () {
    $meta = [
        '_perego_hero_s1_title' => 'A New Headline',
        '_perego_hero_cta'      => 'Get in touch',
        // slide 1 text, slides 2 & 3 left unset -> must fall back to the seed.
    ];
    Functions\when('get_post_meta')->alias(fn (int $id, string $key, bool $single = false): string => $meta[$key] ?? '');

    $hero = (new HeroContent())->resolve(42, 'en');

    expect($hero['slides'][0]['title'])->toBe('A New Headline')            // overridden
        ->and($hero['slides'][0]['text'])->toStartWith('Not every artist') // seed (unset)
        ->and($hero['slides'][1]['title'])->toBe('Ideas, In Motion')       // seed (unset)
        ->and($hero['cta'])->toBe('Get in touch');                         // overridden
});

it('keeps the Arabic seed for unset fields on the Arabic front page', function () {
    Functions\when('get_post_meta')->alias(fn (int $id, string $key, bool $single = false): string => '');

    $hero = (new HeroContent())->resolve(97, 'ar');

    expect($hero['slides'][0]['title'])->toBe('بماذا نؤمن')
        ->and($hero['cta'])->toBe('قل مرحبًا!');
});

it('prefers a block attribute over front-page meta and the seed, per field and per locale', function () {
    $meta = ['_perego_hero_s1_title' => 'Meta headline'];
    Functions\when('get_post_meta')->alias(fn (int $id, string $key, bool $single = false): string => $meta[$key] ?? '');

    $hero = (new HeroContent())->resolve(42, 'en', ['slide1TitleEn' => 'Attribute headline']);

    expect($hero['slides'][0]['title'])->toBe('Attribute headline'); // attribute beats meta
});

it('falls back to front-page meta when the attribute is empty, then the seed when both are empty', function () {
    $meta = ['_perego_hero_s1_title' => 'Meta headline'];
    Functions\when('get_post_meta')->alias(fn (int $id, string $key, bool $single = false): string => $meta[$key] ?? '');

    $hero = (new HeroContent())->resolve(42, 'en', ['slide1TitleEn' => '']);

    expect($hero['slides'][0]['title'])->toBe('Meta headline')             // meta beats seed
        ->and($hero['slides'][1]['title'])->toBe('Ideas, In Motion');      // both empty -> seed
});

it('picks the Ar-suffixed attribute on the Arabic locale, not the En one', function () {
    Functions\when('get_post_meta')->alias(fn (int $id, string $key, bool $single = false): string => '');

    $hero = (new HeroContent())->resolve(97, 'ar', [
        'slide1TitleEn' => 'Should not be used',
        'slide1TitleAr' => 'يجب استخدام هذا',
        'ctaAr' => 'تواصل معنا',
    ]);

    expect($hero['slides'][0]['title'])->toBe('يجب استخدام هذا')
        ->and($hero['cta'])->toBe('تواصل معنا');
});

it('uses a bounded composed slide collection while retaining locale-specific content', function () {
    $hero = (new HeroContent())->resolve(0, 'ar', ['slides' => [
        ['titleEn' => 'English only', 'textEn' => 'English supporting copy', 'titleAr' => 'عنوان عربي', 'textAr' => 'نص عربي'],
        ['titleEn' => 'Second', 'textEn' => 'Second text', 'titleAr' => 'ثانٍ', 'textAr' => 'النص الثاني'],
        ['titleEn' => 'Third', 'textEn' => 'Third text', 'titleAr' => 'ثالث', 'textAr' => 'النص الثالث'],
        ['titleEn' => 'Fourth', 'textEn' => 'Fourth text', 'titleAr' => 'رابع', 'textAr' => 'النص الرابع'],
    ]]);

    expect($hero['slides'])->toHaveCount(4)
        ->and($hero['slides'][0]['title'])->toBe('عنوان عربي')
        ->and($hero['slides'][3]['text'])->toBe('النص الرابع');
});

it('registers the hero meta on the page type with sanitisation and auth', function () {
    $captured = [];
    Functions\when('register_post_meta')->alias(function (string $type, string $key, array $args) use (&$captured): void {
        $captured[$key] = ['type' => $type, 'args' => $args];
    });

    (new HeroContent())->register();

    expect(array_keys($captured))->toBe([
        '_perego_hero_s1_title', '_perego_hero_s2_title', '_perego_hero_s3_title',
        '_perego_hero_s1_text', '_perego_hero_s2_text', '_perego_hero_s3_text',
        '_perego_hero_cta',
    ]);
    expect($captured['_perego_hero_cta']['type'])->toBe('page')
        ->and($captured['_perego_hero_cta']['args']['sanitize_callback'])->toBe('sanitize_text_field')
        ->and($captured['_perego_hero_cta']['args']['show_in_rest'])->toBeTrue()
        ->and($captured['_perego_hero_cta']['args']['auth_callback'])->toBe([HeroContent::class, 'authEdit']);
});
