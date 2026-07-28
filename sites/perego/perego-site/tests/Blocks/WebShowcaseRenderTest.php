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
        ['title' => 'Aurora Retail', 'shotUrl' => 'https://perego.local/p1-large.png', 'fullUrl' => 'https://perego.local/p1.png', 'siteType' => 'ecommerce', 'siteUrl' => 'https://aurora-retail.com', 'logoUrl' => '', 'logoAlt' => ''],
        ['title' => 'Meridian Group', 'shotUrl' => 'https://perego.local/p2-large.png', 'fullUrl' => 'https://perego.local/p2.png', 'siteType' => 'corporate', 'siteUrl' => 'https://www.meridiangroup.co/about', 'logoUrl' => '', 'logoAlt' => ''],
        ['title' => 'No Shot Project', 'shotUrl' => '', 'fullUrl' => '', 'siteType' => 'landing', 'siteUrl' => 'https://gone.example', 'logoUrl' => '', 'logoAlt' => ''],
        ['title' => 'Untyped Site', 'shotUrl' => 'https://perego.local/p3-large.png', 'fullUrl' => 'https://perego.local/p3.png', 'siteType' => '', 'siteUrl' => '', 'logoUrl' => '', 'logoAlt' => ''],
    ];
}

it('renders nothing at all when no project has a logo or a shot', function () {
    $html = (new WebShowcaseRenderer())->render(new ServiceContent('en'), [
        ['title' => 'Nothing', 'shotUrl' => '', 'fullUrl' => '', 'siteType' => 'landing', 'siteUrl' => '', 'logoUrl' => '', 'logoAlt' => ''],
    ]);

    expect($html)->toBe('');
});

it('keeps a project that has only a logo, which is now the point of the section', function () {
    $html = (new WebShowcaseRenderer())->render(new ServiceContent('en'), [
        ['title' => 'Logo Only', 'shotUrl' => '', 'fullUrl' => '', 'siteType' => 'corporate', 'siteUrl' => 'https://logo-only.test', 'logoUrl' => 'https://perego.local/mark.png', 'logoAlt' => 'Logo Only mark'],
    ]);

    // Requiring a screenshot would have dropped every site whose logo is the only asset held —
    // against the client's explicit "don't miss any site".
    expect($html)->toContain('web-card__shot--logo')
        ->and($html)->toContain('src="https://perego.local/mark.png"')
        ->and($html)->toContain('alt="Logo Only mark"');
});

it('prefers the logo over the screenshot when a project has both', function () {
    $html = (new WebShowcaseRenderer())->render(new ServiceContent('en'), [
        ['title' => 'Both', 'shotUrl' => 'https://perego.local/shot.png', 'fullUrl' => '', 'siteType' => 'corporate', 'siteUrl' => 'https://both.test', 'logoUrl' => 'https://perego.local/mark.png', 'logoAlt' => ''],
    ]);

    // The client asked this section to show the marks of the sites they built, not pictures of them.
    expect($html)->toContain('mark.png')
        ->and($html)->not->toContain('shot.png');
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
        ['title' => 'Solo', 'shotUrl' => 'https://perego.local/p1.png', 'fullUrl' => '', 'siteType' => 'ecommerce', 'siteUrl' => '', 'logoUrl' => '', 'logoAlt' => ''],
    ]);

    expect($html)->not->toContain('web-filters')
        ->and($html)->toContain('web-grid');
});

it('links the shot to the live site rather than opening a lightbox', function () {
    $html = (new WebShowcaseRenderer())->render(new ServiceContent('en'), sampleWebCards());

    expect($html)->toContain('<a class="web-card__shot" href="https://aurora-retail.com" target="_blank" rel="noopener"')
        ->and($html)->toContain('aria-label="Visit Aurora Retail"')
        ->and($html)->toContain('<span class="web-card__view"><span>Visit</span></span>')
        // The section no longer participates in the site-wide lightbox at all. Scoped to the shot:
        // the type-filter pills above the grid are <button>s of their own.
        ->and($html)->not->toContain('data-image')
        ->and($html)->not->toContain('<button type="button" class="web-card__shot');
});

it('renders an inert shot with no hover label when a project has no live site', function () {
    $html = (new WebShowcaseRenderer())->render(new ServiceContent('en'), sampleWebCards());

    $cards = array_filter(explode('<article', $html), fn (string $chunk) => str_contains($chunk, 'Untyped Site'));
    $untyped = array_values($cards)[0] ?? '';

    expect($untyped)->not->toBe('')
        ->and($untyped)->toContain('<div class="web-card__shot">')
        ->and($untyped)->toContain('src="https://perego.local/p3-large.png"')
        ->and($untyped)->not->toContain('web-card__view');
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
        ->and($html)->toContain('زيارة');
});
