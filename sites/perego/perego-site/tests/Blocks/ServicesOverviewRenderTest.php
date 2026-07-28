<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\ServiceSelectedWorkRenderer;
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
    Functions\when('__')->returnArg();
});

function sampleSelectedWork(): array
{
    return [
        ['title' => 'Brand Film', 'thumbUrl' => 'https://perego.local/a.jpg', 'thumbAlt' => 'Brand Film', 'gallerySrcs' => []],
        ['title' => 'Explainer Series', 'thumbUrl' => 'https://perego.local/b.jpg', 'thumbAlt' => 'Explainer Series', 'gallerySrcs' => ['https://perego.local/b.jpg', 'https://perego.local/b2.jpg']],
        ['title' => 'No Media Project', 'thumbUrl' => '', 'thumbAlt' => '', 'gallerySrcs' => []],
    ];
}

function renderServicesOverview(string $locale = 'en', ?array $selectedWork = null): string
{
    return (new ServicesOverviewRenderer(new ServiceSelectedWorkRenderer()))
        ->render(new ServiceContent($locale), $selectedWork ?? sampleSelectedWork());
}

it('renders exactly one H1 (the services archive title)', function () {
    $html = renderServicesOverview();

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('Our Services');
});

it('renders the hero background image and four service tabs linking to the singles', function () {
    $html = renderServicesOverview();

    expect($html)->toMatch('/<img [^>]*svc-hero-bg\.webp/')
        ->and(substr_count($html, 'class="svc-tab"'))->toBe(4)
        ->and($html)->toContain('/services/video-editing')
        ->and($html)->toContain('/services/website-making')
        // The CTA must carry the canonical service *slug* (what the contact chooser whitelists),
        // not the localized name — otherwise ?service= preselection never matches.
        ->and($html)->toContain('/start-a-project?service=video-editing')
        ->and($html)->toContain('/start-a-project?service=website-making')
        ->and($html)->not->toMatch('/contact\?service=Video(%20| )Editing/');
});

it('uses the editable tab labels when provided, per field over the ServiceContent seed', function () {
    $html = (new ServicesOverviewRenderer(new ServiceSelectedWorkRenderer()))->render(
        new ServiceContent('en'),
        sampleSelectedWork(),
        ['video-editing' => 'VE', 'website-making' => 'WM'],
    );

    expect($html)->toMatch('/svc-tab__label">VE</')            // edited
        ->and($html)->toMatch('/svc-tab__label">WM</')         // edited
        ->and($html)->toContain('2D Motion Graphics')          // unset -> seed
        ->and($html)->toContain('Graphic Design');             // unset -> seed
});

it('renders the what-we-do intro with its media image, process, and closing CTA', function () {
    $html = renderServicesOverview();

    expect($html)->toContain('One studio, four services')
        ->and($html)->toContain('svc-whatwedo')
        ->and($html)->toMatch('/<img [^>]*ui-video-editing\.png/')
        ->and($html)->toContain('class="process"')
        ->and($html)->toContain('Have a project in mind?')
        ->and($html)->toMatch('/href="[^"]*\/start-a-project"/');
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

it('renders selected work as real project cards opening the site-wide media lightbox', function () {
    $html = renderServicesOverview();

    expect($html)->toContain('class="portfolio page-section"')
        ->and($html)->toContain('class="work-masonry"')
        // The no-media project is skipped; the two usable ones get designed mosaic placements.
        ->and($html)->toContain('class="work-card m1 reveal"')
        ->and($html)->toContain('class="work-card m2 reveal"')
        ->and($html)->toContain('class="work-card work-brand"')
        ->and($html)->toContain('data-image="https://perego.local/a.jpg"')
        ->and($html)->toContain('data-gallery="https://perego.local/b.jpg,https://perego.local/b2.jpg"')
        ->and($html)->toContain('Selected work');
});

it('renders no selected-work section at all when no project has usable media', function () {
    $html = renderServicesOverview('en', [
        ['title' => 'No Media', 'thumbUrl' => '', 'thumbAlt' => '', 'gallerySrcs' => []],
    ]);

    expect($html)->not->toContain('class="portfolio page-section"')
        ->and($html)->not->toContain('class="work-masonry"');
});
