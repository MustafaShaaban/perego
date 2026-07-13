<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\ServicesOverviewRenderer;
use PeregoSite\Content\ServiceContent;

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_html__')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_attr__')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('home_url')->alias(fn (string $path = '') => 'https://perego.local' . $path);
    Functions\when('get_stylesheet_directory_uri')->justReturn('https://perego.local/wp-content/themes/perego-theme');
    Functions\when('add_query_arg')->alias(fn (string $key, string $value, string $url) => $url . '?' . $key . '=' . rawurlencode($value));
});

function renderServicesOverview(string $locale = 'en'): string
{
    return (new ServicesOverviewRenderer())->render(new ServiceContent($locale));
}

it('renders exactly one H1 (the services archive title)', function () {
    $html = renderServicesOverview();

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('Our Services');
});

it('renders the hero background image and four service tabs linking to the singles', function () {
    $html = renderServicesOverview();

    expect($html)->toMatch('/<img [^>]*svc-hero-bg\.png/')
        ->and(substr_count($html, 'class="svc-tab"'))->toBe(4)
        ->and($html)->toContain('/services/video-editing')
        ->and($html)->toContain('/services/website-making')
        ->and($html)->toMatch('/href="[^"]*\/contact\?service=/');
});

it('renders the what-we-do intro with its media image, process, and closing CTA', function () {
    $html = renderServicesOverview();

    expect($html)->toContain('One studio, four services')
        ->and($html)->toContain('svc-whatwedo')
        ->and($html)->toMatch('/<img [^>]*ui-video-editing\.png/')
        ->and($html)->toContain('class="process"')
        ->and($html)->toContain('Have a project in mind?')
        ->and($html)->toMatch('/href="[^"]*\/contact"/');
});

it('renders the four-step process as designed icon cards with arrows, not a plain list', function () {
    $html = renderServicesOverview();

    expect($html)->toContain('class="process-list"')
        ->and(substr_count($html, 'class="process-step reveal"'))->toBe(4)
        ->and(substr_count($html, 'class="process-arrow"'))->toBe(3)
        ->and($html)->toMatch('/<img [^>]*icon-clapper\.png/')
        ->and($html)->toMatch('/<img [^>]*icon-film-l\.png/')
        ->and($html)->toMatch('/<img [^>]*icon-star\.png/')
        ->and($html)->toMatch('/<img [^>]*icon-film-h\.png/')
        ->and($html)->toContain('class="process-step__label"')
        ->and($html)->toContain('class="process-step__desc"');
});

it('localizes the whole overview into Arabic', function () {
    $html = renderServicesOverview('ar');

    expect($html)->toContain('خدماتنا')
        ->and($html)->toContain('استوديو واحد، أربع خدمات')
        ->and($html)->toContain('هل لديك مشروع في ذهنك؟');
});

it('uses no hardcoded hex colors in the rendered markup', function () {
    expect(renderServicesOverview())->not->toMatch('/#[0-9a-fA-F]{6}/');
});
