<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\ServicesTeaserRenderer;
use PeregoSite\Services\LanguageService;

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_html__')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_attr__')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('__')->returnArg();
    Functions\when('home_url')->alias(fn (string $path = '') => 'https://perego.local' . $path);
});

function renderServicesTeaser(string $locale = 'en'): string
{
    $cookie  = $locale === 'en' ? [] : ['perego_lang' => $locale];
    $service = new LanguageService(cookie: $cookie, requestUri: '/', polylangActive: false);

    return (new ServicesTeaserRenderer($service))->render();
}

it('renders the section heading and a See All Services link to the services archive', function () {
    $html = renderServicesTeaser();

    expect($html)->toContain('Services we can help you with')
        ->and($html)->toContain('See All Services')
        ->and($html)->toMatch('/href="[^"]*\/services"/');
});

it('renders exactly four service cards in the fixed order', function () {
    $html = renderServicesTeaser();

    $video   = strpos($html, '/services/video-editing');
    $motion  = strpos($html, '/services/motion-graphics');
    $design  = strpos($html, '/services/graphic-design');
    $website = strpos($html, '/services/website-making');

    expect(substr_count($html, 'class="service-card"'))->toBe(4)
        ->and([$video, $motion, $design, $website])->each->toBeInt()
        ->and($video)->toBeLessThan($motion)
        ->and($motion)->toBeLessThan($design)
        ->and($design)->toBeLessThan($website);
});

it('labels each card with the localized service name', function () {
    $html = renderServicesTeaser();

    expect($html)->toContain('Video Editing')
        ->and($html)->toContain('2D Motion Graphics')
        ->and($html)->toContain('Graphic Design')
        ->and($html)->toContain('Website Making');
});

it('gives each card an accessible label region and a decorative media placeholder', function () {
    $html = renderServicesTeaser();

    expect($html)->toContain('service-card__label')
        ->and($html)->toContain('service-card__media')
        ->and($html)->toContain('service-card__overlay');
});

it('exposes the heading via aria-labelledby for the section landmark', function () {
    $html = renderServicesTeaser();

    expect($html)->toMatch('/aria-labelledby="[^"]+"/')
        ->and($html)->toMatch('/id="[^"]+"[^>]*class="services-teaser__title"|class="services-teaser__title" id="[^"]+"/');
});

it('renders localized Arabic copy when the locale resolves to ar', function () {
    $html = renderServicesTeaser('ar');

    expect($html)->toContain('عرض كل الخدمات')
        ->and($html)->toContain('مونتاج الفيديو');
});

it('uses no hardcoded hex colors in the rendered markup', function () {
    expect(renderServicesTeaser())->not->toMatch('/#[0-9a-fA-F]{6}/');
});
