<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\SiteHeaderRenderer;
use PeregoSite\Services\LanguageService;

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_html__')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_attr__')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('home_url')->alias(fn (string $path = '') => 'https://perego.local' . $path);
    Functions\when('wp_json_encode')->alias('json_encode');
});

function renderHeader(string $currentPath = '/'): string
{
    $service = new LanguageService(cookie: [], requestUri: $currentPath, polylangActive: false);

    return (new SiteHeaderRenderer($service))->render($currentPath);
}

it('renders the nav items in the documented order', function () {
    $html = renderHeader();

    $home     = strpos($html, '>Home<');
    $about    = strpos($html, '>About Us<');
    $services = strpos($html, '>Services<');
    $work     = strpos($html, '>Work<');
    $journal  = strpos($html, '>Journal<');
    $clients  = strpos($html, '>Clients<');
    $contact  = strpos($html, '>Contact Us<');

    expect([$home, $about, $services, $work, $journal, $clients, $contact])
        ->each->toBeInt()
        ->and($home)->toBeLessThan($about)
        ->and($about)->toBeLessThan($services)
        ->and($services)->toBeLessThan($work)
        ->and($work)->toBeLessThan($journal)
        ->and($journal)->toBeLessThan($clients)
        ->and($clients)->toBeLessThan($contact);
});

it('marks the active nav item with aria-current', function () {
    $html = renderHeader('/services');

    expect($html)->toContain('aria-current="page"')
        ->and($html)->toMatch('/<li class="[^"]*is-active[^"]*"[^>]*><a [^>]*aria-current="page"[^>]*>Services</');
});

it('does not mark any item active on an unmatched path', function () {
    $html = renderHeader('/journal/some-post');

    expect($html)->not->toContain('aria-current="page"');
});

it('renders the Start a Project CTA', function () {
    expect(renderHeader())->toContain('Start a Project');
});

it('renders the services dropdown linking to all four service pages', function () {
    $html = renderHeader();

    expect($html)->toContain('/services/video-editing')
        ->and($html)->toContain('/services/motion-graphics')
        ->and($html)->toContain('/services/graphic-design')
        ->and($html)->toContain('/services/website-making');
});

it('renders a hamburger control for the mobile menu', function () {
    expect(renderHeader())->toContain('perego-header__hamburger');
});

it('renders the current locale as a non-link marked aria-current, and the other as a real switch link', function () {
    $html = renderHeader(); // fallback driver, current locale = en

    // The current language (EN) is not a link — it is the current-state marker.
    expect($html)->toContain('perego-language-toggle')
        ->and($html)->toMatch('/<span[^>]*aria-current="true"[^>]*>EN<\/span>/');

    // The other language (AR) is a real anchor to its switch URL — never a JS-only button.
    expect($html)->toMatch('/<a [^>]*href="[^"]*lang=ar[^"]*"[^>]*>AR<\/a>/')
        ->and($html)->toContain('data-locale="ar"')
        ->and($html)->not->toContain('data-wp-on--click="actions.switchLanguage"');
});

it('declares whether the language driver manages language via URL (client persistence flag)', function () {
    // Fallback driver → not URL-managed → the client must persist the choice.
    expect(renderHeader())->toContain('data-lang-url-managed="0"');
});

it('declares the initial Interactivity API context', function () {
    $html = renderHeader();

    expect($html)->toMatch('/data-wp-context=\'\{"isScrolled":false,"isMenuOpen":false\}\'/');
});

it('renders the mobile nav panel with a matching id, backdrop, and keydown trap', function () {
    $html = renderHeader();

    expect($html)->toContain('id="perego-mobile-nav"')
        ->and($html)->toContain('perego-header__nav-backdrop')
        ->and($html)->toContain('data-wp-on--keydown="actions.handleMenuKeydown"');
});

it('wires the mobile Services item to the tap-accordion action', function () {
    $html = renderHeader();

    expect($html)->toContain('data-wp-on--click="actions.toggleMobileDropdown"');
});
