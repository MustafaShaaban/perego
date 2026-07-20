<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\WebShowcaseRenderer;
use PeregoSite\Content\ServiceContent;

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('__')->returnArg();
});

/** @return list<array{title: string, shotUrl: string, fullUrl: string, siteType: string, siteUrl: string}> */
function sampleWebCards(): array
{
    return [
        ['title' => 'Aurora Retail', 'shotUrl' => 'https://perego.local/p1-large.png', 'fullUrl' => 'https://perego.local/p1.png', 'siteType' => 'ecommerce', 'siteUrl' => 'https://aurora-retail.com'],
        ['title' => 'Meridian Group', 'shotUrl' => 'https://perego.local/p2-large.png', 'fullUrl' => 'https://perego.local/p2.png', 'siteType' => 'corporate', 'siteUrl' => 'https://www.meridiangroup.co/about'],
        ['title' => 'No Shot Project', 'shotUrl' => '', 'fullUrl' => '', 'siteType' => 'landing', 'siteUrl' => 'https://gone.example'],
        ['title' => 'Untyped Site', 'shotUrl' => 'https://perego.local/p3-large.png', 'fullUrl' => 'https://perego.local/p3.png', 'siteType' => '', 'siteUrl' => ''],
    ];
}

it('renders nothing at all when no project has a usable shot', function () {
    $html = (new WebShowcaseRenderer())->render(new ServiceContent('en'), [
        ['title' => 'No Shot', 'shotUrl' => '', 'fullUrl' => '', 'siteType' => 'landing', 'siteUrl' => ''],
    ]);

    expect($html)->toBe('');
});

it('renders the handoff web-showcase shell: head, filter pills, and browser-chrome cards', function () {
    $html = (new WebShowcaseRenderer())->render(new ServiceContent('en'), sampleWebCards());

    expect($html)->toContain('class="web-showcase page-section"')
        ->and($html)->toContain('Websites we’ve built')
        ->and($html)->toContain('class="web-filters reveal"')
        ->and($html)->toContain('<div class="web-grid" id="webGrid">')
        ->and(substr_count($html, '<article class="web-card reveal"'))->toBe(3) // no-shot card skipped
        ->and(substr_count($html, 'class="web-card__dots"'))->toBe(3)
        ->and($html)->toContain('class="web-card__title">Aurora Retail</h3>');
});

it('emits filter pills only for site types present in the data, in the handoff order', function () {
    $html = (new WebShowcaseRenderer())->render(new ServiceContent('en'), sampleWebCards());

    expect($html)->toContain('data-filter="all"')
        ->and($html)->toContain('data-filter="ecommerce"')
        ->and($html)->toContain('data-filter="corporate"')
        ->and($html)->not->toContain('data-filter="landing"')   // only on the skipped no-shot card
        ->and($html)->not->toContain('data-filter="webapp"')
        ->and(strpos($html, 'data-filter="ecommerce"'))->toBeLessThan(strpos($html, 'data-filter="corporate"'));
});

it('hides the filter group entirely when fewer than two types are present', function () {
    $html = (new WebShowcaseRenderer())->render(new ServiceContent('en'), [
        ['title' => 'Solo', 'shotUrl' => 'https://perego.local/p1.png', 'fullUrl' => '', 'siteType' => 'ecommerce', 'siteUrl' => ''],
    ]);

    expect($html)->not->toContain('web-filters')
        ->and($html)->toContain('web-grid');
});

it('opens the full-size shot in the site-wide lightbox from the Preview button', function () {
    $html = (new WebShowcaseRenderer())->render(new ServiceContent('en'), sampleWebCards());

    expect($html)->toContain('class="web-card__shot" data-image="https://perego.local/p1.png"')
        ->and($html)->toContain('aria-label="Preview Aurora Retail"')
        ->and($html)->toContain('<span class="web-card__view"><span>Preview</span></span>');
});

it('links Visit to the live site in a new tab, showing the bare host in the browser bar', function () {
    $html = (new WebShowcaseRenderer())->render(new ServiceContent('en'), sampleWebCards());

    expect($html)->toContain('href="https://aurora-retail.com" target="_blank" rel="noopener"')
        ->and($html)->toContain('<span>aurora-retail.com</span>')
        ->and($html)->toContain('<span>meridiangroup.co</span>'); // www. stripped, path dropped
});

it('omits the Visit link and browser-bar URL when a project has no live site', function () {
    $html = (new WebShowcaseRenderer())->render(new ServiceContent('en'), sampleWebCards());

    // The untyped/URL-less card still renders (reachable via "All") but with no external link.
    $cards = array_filter(explode('<article', $html), fn (string $chunk) => str_contains($chunk, 'Untyped Site'));
    $untyped = array_values($cards)[0] ?? '';

    expect($untyped)->not->toBe('')
        ->and($untyped)->not->toContain('web-card__visit')
        ->and($untyped)->not->toContain('web-card__url');
});

it('localizes the showcase copy into Arabic', function () {
    $html = (new WebShowcaseRenderer())->render(new ServiceContent('ar'), sampleWebCards());

    expect($html)->toContain('مواقع أنشأناها')
        ->and($html)->toContain('متجر إلكتروني')
        ->and($html)->toContain('معاينة')
        ->and($html)->toContain('زيارة');
});
