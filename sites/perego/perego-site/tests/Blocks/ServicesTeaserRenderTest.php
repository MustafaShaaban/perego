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
    Functions\when('wp_kses_post')->returnArg();
    Functions\when('esc_html__')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_attr__')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('__')->returnArg();
    Functions\when('home_url')->alias(fn (string $path = '') => 'https://perego.local' . $path);
    Functions\when('get_stylesheet_directory_uri')->justReturn('https://perego.local/wp-content/themes/perego-theme');
    Functions\when('wp_reset_postdata')->justReturn(null);
});

/** @param array<string,string> $attributes */
function renderServicesTeaser(string $locale = 'en', array $attributes = []): string
{
    $cookie  = $locale === 'en' ? [] : ['perego_lang' => $locale];
    $service = new LanguageService(cookie: $cookie, requestUri: '/', polylangActive: false);

    return (new ServicesTeaserRenderer($service))->render($attributes);
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

    expect(substr_count($html, 'class="service-card reveal"'))->toBe(4)
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

it('gives each card an accessible label region and an alt-described image', function () {
    $html = renderServicesTeaser();

    expect($html)->toContain('service-card__label')
        ->and($html)->toContain('service-card__overlay')
        ->and($html)->toMatch('/<img [^>]*alt="Video editing timeline"/');
});

it('exposes the heading via aria-labelledby for the section landmark', function () {
    $html = renderServicesTeaser();

    expect($html)->toMatch('/aria-labelledby="[^"]+"/')
        ->and($html)->toMatch('/class="services-teaser__title(?: [^"]+)?" id="[^"]+"/');
});

it('preserves the handoff container and staggered reveal contract', function () {
    $html = renderServicesTeaser();

    expect($html)->toContain('class="container services-teaser__inner"')
        ->and($html)->toContain('class="link-arrow services-teaser__link reveal" data-delay="1"')
        ->and($html)->toContain('class="service-card reveal" data-delay="3"');
});

it('renders localized Arabic copy when the locale resolves to ar', function () {
    $html = renderServicesTeaser('ar');

    expect($html)->toContain('عرض كل الخدمات')
        ->and($html)->toContain('مونتاج الفيديو');
});

it('uses no hardcoded hex colors in the rendered markup', function () {
    expect(renderServicesTeaser())->not->toMatch('/#[0-9a-fA-F]{6}/');
});

it('prefers the editor-set En/Ar heading/seeAll block attribute over the seed, per locale', function () {
    $attributes = [
        'headingEn' => 'Ways We Can Help',
        'headingAr' => 'كيف يمكننا المساعدة',
    ];

    $htmlEn = renderServicesTeaser('en', $attributes);
    $htmlAr = renderServicesTeaser('ar', $attributes);

    expect($htmlEn)->toContain('Ways We Can Help')->not->toContain('Services we can help you with')
        // "See All" copy is untouched — still falls back to the seed.
        ->and($htmlEn)->toContain('See All Services')
        ->and($htmlAr)->toContain('كيف يمكننا المساعدة')->not->toContain('خدمات يمكننا مساعدتك بها');
});

it('uses the manually ordered Service selection when the composer is set to manual', function () {
    $service = new WP_Post();
    $service->ID = 51;
    $service->post_type = \PeregoSite\PostTypes\ServicePostType::POST_TYPE;
    $service->post_status = 'publish';

    Functions\when('get_post')->alias(fn (int $id) => $id === 51 ? $service : null);
    Functions\when('get_post_meta')->alias(function (int $id, string $key) {
        return match ($key) {
            \PeregoSite\PostTypes\ServicePostType::META_SERVICE_SLUG => 'website-making',
            \PeregoSite\PostTypes\ServicePostType::META_TEASER_LABEL => 'Web experiences',
            default => '',
        };
    });

    $html = renderServicesTeaser('en', [
        'servicesMode' => 'manual',
        'serviceOrder' => [51],
    ]);

    expect(substr_count($html, 'class="service-card reveal"'))->toBe(1)
        ->and($html)->toContain('Web experiences')
        ->and($html)->toContain('/services/website-making')
        ->and($html)->not->toContain('/services/video-editing');
});

it('places manual Services before the automatic cards in hybrid mode', function () {
    $service = new WP_Post();
    $service->ID = 51;
    $service->post_type = \PeregoSite\PostTypes\ServicePostType::POST_TYPE;
    $service->post_status = 'publish';

    Functions\when('get_post')->alias(fn (int $id) => $id === 51 ? $service : null);
    Functions\when('get_post_meta')->alias(fn (int $id, string $key) => $key === \PeregoSite\PostTypes\ServicePostType::META_SERVICE_SLUG ? 'website-making' : '');

    $html = renderServicesTeaser('en', [
        'servicesMode' => 'hybrid',
        'serviceOrder' => [51],
    ]);

    expect(strpos($html, '/services/website-making'))->toBeLessThan(strpos($html, '/services/video-editing'))
        ->and(substr_count($html, '/services/website-making'))->toBe(1);
});
