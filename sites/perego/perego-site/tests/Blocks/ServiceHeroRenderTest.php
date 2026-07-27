<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\ServiceHeroRenderer;
use PeregoSite\Content\ServiceContent;

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('home_url')->alias(fn (string $path = '') => 'https://perego.local' . $path);
    Functions\when('get_stylesheet_directory_uri')->justReturn('https://perego.local/wp-content/themes/perego-theme');
    Functions\when('add_query_arg')->alias(fn (string $key, string $value, string $url) => $url . '?' . $key . '=' . rawurlencode($value));
});

function renderServiceHero(string $currentSlug = 'video-editing', string $locale = 'en'): string
{
    return (new ServiceHeroRenderer())->render(new ServiceContent($locale), $currentSlug);
}

it('renders the eyebrow, the current service full name as the single H1, and no extra H1', function () {
    $html = renderServiceHero('video-editing');

    expect($html)->toContain('svc-hero__eyebrow')
        ->and($html)->toContain('Our Services')
        ->and($html)->toMatch('/<img [^>]*svc-hero-bg\.webp/')
        ->and($html)->toMatch('/<div class="container svc-hero__inner">/')
        ->and($html)->toMatch('/<h1 class="svc-hero__title reveal" data-delay="1">Video Editing &amp; Post-Production|<h1 class="svc-hero__title reveal" data-delay="1">Video Editing & Post-Production/')
        ->and(substr_count($html, '<h1'))->toBe(1);
});

it('renders exactly four service tabs in fixed order with main + start-project CTA links', function () {
    $html = renderServiceHero('motion-graphics');

    expect(substr_count($html, 'svc-tab__main'))->toBe(4)
        ->and(substr_count($html, 'svc-tab__cta'))->toBe(4)
        ->and($html)->toContain('/services/video-editing')
        ->and($html)->toContain('/services/website-making')
        // The CTA must carry the canonical service slug (what the brief-form chooser whitelists),
        // not the localized service name — otherwise ?service= preselection never matches.
        ->and($html)->toContain('/start-a-project?service=video-editing')
        ->and($html)->toContain('/start-a-project?service=website-making');
});

it('marks only the current service tab active with aria-current', function () {
    $html = renderServiceHero('graphic-design');

    expect(substr_count($html, 'svc-tab is-active'))->toBe(1)
        ->and(substr_count($html, 'aria-current="page"'))->toBe(1)
        ->and($html)->toMatch('/svc-tab is-active"><a class="svc-tab__main" href="[^"]*\/services\/graphic-design"/');
});

it('localizes the hero into Arabic', function () {
    $html = renderServiceHero('video-editing', 'ar');

    expect($html)->toContain('خدماتنا')
        ->and($html)->toContain('مونتاج الفيديو وما بعد الإنتاج')
        ->and($html)->toContain('ابدأ مشروعك');
});

it('falls back to the eyebrow as the title when no current service is resolved', function () {
    $html = renderServiceHero('');

    expect($html)->toMatch('/<h1 class="svc-hero__title reveal" data-delay="1">Our Services<\/h1>/')
        ->and(substr_count($html, 'svc-tab is-active'))->toBe(0);
});

it('uses the CPT post title for the H1 and the editable tab labels when provided (per-field over seed)', function () {
    // The Service post title + a couple of edited tab labels override the seed; unset labels keep it.
    $html = (new ServiceHeroRenderer())->render(
        new ServiceContent('en'),
        'video-editing',
        'Video Editing PRO',
        ['video-editing' => 'VE', 'graphic-design' => 'GD'],
    );

    expect($html)->toContain('<h1 class="svc-hero__title reveal" data-delay="1">Video Editing PRO</h1>')
        ->and($html)->toMatch('/svc-tab__label">VE</')          // edited
        ->and($html)->toMatch('/svc-tab__label">GD</')          // edited
        ->and($html)->toContain('2D Motion Graphics')           // unset -> ServiceContent seed
        ->and($html)->toContain('Website Making');              // unset -> seed
});
