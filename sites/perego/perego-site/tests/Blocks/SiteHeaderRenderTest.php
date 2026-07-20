<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\SiteHeaderRenderer;
use PeregoSite\Services\LanguageService;

beforeEach(function () {
    Functions\when('__')->returnArg();
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_html__')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_attr__')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('home_url')->alias(fn (string $path = '') => 'https://perego.local' . $path);
    Functions\when('get_stylesheet_directory_uri')->justReturn('https://perego.local/wp-content/themes/perego-theme');
    Functions\when('wp_json_encode')->alias('json_encode');
    Functions\when('wp_get_attachment_image_url')->justReturn('');
});

/** @param array<string,mixed> $attributes */
function renderHeader(string $currentPath = '/', array $attributes = [], string $locale = 'en'): string
{
    $cookie  = $locale === 'en' ? [] : ['perego_lang' => $locale];
    $service = new LanguageService(cookie: $cookie, requestUri: $currentPath, polylangActive: false);

    return (new SiteHeaderRenderer($service))->render($currentPath, $attributes);
}

it('renders the nav items in the documented order', function () {
    $html = renderHeader();

    $home     = strpos($html, '>Home<');
    $about    = strpos($html, '>About Us<');
    $services = strpos($html, '>Services');
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

it('renders the handoff skip link before the header landmark', function () {
    $html = renderHeader();

    expect($html)->toStartWith('<a class="skip-link" href="#main">Skip to content</a><header');
});

it('marks the active nav item with aria-current', function () {
    $html = renderHeader('/work');

    expect($html)->toContain('aria-current="page"')
        ->and($html)->toMatch('/<li class="[^"]*is-active[^"]*"[^>]*><a [^>]*aria-current="page"[^>]*>Work/');
});

it('links About Us and Services to homepage anchors, never a hard-coded /about route', function () {
    $html = renderHeader('/');

    expect($html)->toContain('href="https://perego.local/#about"')
        ->and($html)->toContain('href="https://perego.local/#services"')
        ->and($html)->not->toContain('href="https://perego.local/about"');
});

it('marks the ancestor nav item active on a descendant route (single post under an archive)', function () {
    $html = renderHeader('/journal/some-post');

    // The Journal archive item lights up for its child single post, via prefix/ancestor matching.
    expect($html)->toContain('aria-current="page"')
        ->and($html)->toMatch('/<li class="[^"]*is-active[^"]*"[^>]*><a [^>]*aria-current="page"[^>]*>Journal/');
});

it('does not mark any item active on a path outside every nav route', function () {
    $html = renderHeader('/legal/privacy-policy');

    expect($html)->not->toContain('aria-current="page"');
});

it('links Contact Us to the footer anchor as a bare same-page fragment on every page and locale', function () {
    foreach ([['/', 'en'], ['/services/graphic-design', 'en'], ['/ar/services/graphic-design-2', 'ar']] as [$path, $locale]) {
        $html = renderHeader($path, [], $locale);

        // Never localized/absolutized (a homepage URL would break the same-page anchor), never active.
        expect($html)->toMatch('/<a class="main-nav__link" href="#contact">Contact Us</')
            ->and($html)->not->toContain('href="https://perego.local/#contact"')
            ->and($html)->not->toContain('href="https://perego.local/contact">Contact Us');
    }
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
    expect(renderHeader())->toContain('id="navToggle"')
        ->and(renderHeader())->toContain('class="nav-toggle"');
});

it('renders the current locale as a non-link marked aria-current, and the other as a real switch link', function () {
    $html = renderHeader(); // fallback driver, current locale = en

    // The current language (EN) is not a link — it is the current-state marker.
    expect($html)->toContain('class="lang-toggle"')
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

    expect($html)->toContain('id="mainNav"')
        ->and($html)->toContain('id="navBackdrop"')
        ->and($html)->toContain('data-wp-on--keydown="actions.handleMenuKeydown"');
});

it('wires the mobile Services item to the tap-accordion action', function () {
    $html = renderHeader();

    expect($html)->toContain('data-wp-on--click="actions.toggleMobileDropdown"');
});

it('renders sticky by default (no inline position override)', function () {
    expect(renderHeader())->not->toContain('style="position:static"');
});

it('renders position:static inline when the isSticky attribute is turned off', function () {
    expect(renderHeader('/', ['isSticky' => false]))->toContain('style="position:static"');
});

it('uses the default logo asset when no logoId attribute is set', function () {
    expect(renderHeader())->toContain('logo-full.png');
});

it('uses the logoId attribute\'s attachment URL when set', function () {
    Functions\when('wp_get_attachment_image_url')->alias(
        fn (int $id) => $id === 42 ? 'https://perego.local/uploads/new-logo.png' : ''
    );

    $html = renderHeader('/', ['logoId' => 42]);

    expect($html)->toContain('https://perego.local/uploads/new-logo.png')
        ->and($html)->not->toContain('logo-full.png');
});

it('prefers the editor-set En/Ar navItems block attribute over the hardcoded seed, per locale', function () {
    $navEn = json_encode([['label' => 'Custom Link', 'href' => '/custom']]);
    $navAr = json_encode([['label' => 'رابط مخصص', 'href' => '/custom']]);

    $htmlEn = renderHeader('/', ['navItemsEn' => $navEn], 'en');
    $htmlAr = renderHeader('/', ['navItemsAr' => $navAr], 'ar');

    expect($htmlEn)->toContain('Custom Link')->not->toContain('>Home<')
        ->and($htmlAr)->toContain('رابط مخصص');
});

it('falls back to the hardcoded seed nav when the attribute is empty or invalid JSON', function () {
    expect(renderHeader('/', ['navItemsEn' => '']))->toContain('>Home<')
        ->and(renderHeader('/', ['navItemsEn' => 'not-json']))->toContain('>Home<')
        ->and(renderHeader('/', ['navItemsEn' => '[]']))->toContain('>Home<');
});
