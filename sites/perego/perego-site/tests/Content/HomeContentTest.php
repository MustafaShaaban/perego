<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\Content\HomeContent;

it('returns the three English hero slides in order with the first titled', function () {
    $slides = (new HomeContent('en'))->heroSlides();

    expect($slides)->toHaveCount(3)
        ->and($slides[0]['title'])->toBe('What We Believe')
        ->and($slides[1]['title'])->toBe('Ideas, In Motion')
        ->and($slides[2]['title'])->toBe('Built To Be Seen')
        ->and($slides[0]['text'])->toContain('Not every artist holds a brush');
});

it('returns Arabic copy when the locale is ar', function () {
    $content = new HomeContent('ar');

    expect($content->heroSlides())->toHaveCount(3)
        ->and($content->heroCta())->toBe('قل مرحبًا!')
        ->and($content->servicesTeaserTitle())->toBe('خدمات يمكننا مساعدتك بها');
});

it('falls back to English for an unknown locale', function () {
    $content = new HomeContent('fr');

    expect($content->heroCta())->toBe('Say Hello!')
        ->and($content->servicesTeaserSeeAll())->toBe('See All Services');
});

it('returns the four services in fixed order with slug, localized name, and alt', function () {
    $services = (new HomeContent('en'))->services();

    expect($services)->toHaveCount(4)
        ->and(array_column($services, 'slug'))->toBe([
            'video-editing',
            'motion-graphics',
            'graphic-design',
            'website-making',
        ])
        ->and($services[0]['name'])->toBe('Video Editing')
        ->and($services[0]['alt'])->not->toBe('')
        ->and($services[0]['image'])->toBe('card-video-editing');
});

it('localizes service names for ar while keeping the same slugs and order', function () {
    $en = (new HomeContent('en'))->services();
    $ar = (new HomeContent('ar'))->services();

    expect(array_column($ar, 'slug'))->toBe(array_column($en, 'slug'))
        ->and($ar[0]['name'])->toBe('مونتاج الفيديو')
        ->and($ar[0]['name'])->not->toBe($en[0]['name']);
});
