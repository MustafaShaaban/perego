<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\SiteHeaderRenderer;
use PeregoSite\Services\LanguageService;
use PeregoSite\PostTypes\ServicePostType;

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
    Functions\when('wp_get_attachment_image')->justReturn('');
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

// Journal, not Work: since 2026-07-26 the Work item is the `/#work` home-section anchor, so it has no
// page path of its own to be "current" on. Journal is the remaining plain-route nav item.
it('marks the active nav item with aria-current', function () {
    $html = renderHeader('/journal');

    expect($html)->toContain('aria-current="page"')
        ->and($html)->toMatch('/<li class="[^"]*is-active[^"]*"[^>]*><a [^>]*aria-current="page"[^>]*>Journal/');
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
            ->and($html)->not->toContain('href="https://perego.local/start-a-project">Contact Us');
    }
});

it('renders the Start a Project CTA', function () {
    expect(renderHeader())->toContain('Start a Project');
});

it('uses a custom CTA label per locale when set, falling back to the default otherwise', function () {
    expect(renderHeader('/', ['ctaLabelEn' => 'Get Started'], 'en'))
        ->toContain('>Get Started</a>')
        ->and(renderHeader('/', ['ctaLabelEn' => 'Get Started'], 'en'))->not->toContain('Start a Project');

    expect(renderHeader('/', ['ctaLabelAr' => 'ابدأ الآن'], 'ar'))->toContain('>ابدأ الآن</a>');

    expect(renderHeader('/', [], 'en'))->toContain('>Start a Project</a>');
});

it('links the CTA to the default start-a-project route, or a custom internal path localized through the driver', function () {
    expect(renderHeader('/', [], 'en'))->toContain('href="https://perego.local/start-a-project"');

    expect(renderHeader('/', ['ctaUrl' => '/quote'], 'en'))
        ->toContain('href="https://perego.local/quote"');
});

it('keeps an external or same-page-anchor CTA URL as-is, never localized to a homepage URL', function () {
    expect(renderHeader('/', ['ctaUrl' => 'https://cal.com/perego'], 'en'))
        ->toContain('href="https://cal.com/perego"')
        ->and(renderHeader('/', ['ctaUrl' => 'https://cal.com/perego'], 'en'))
        ->not->toContain('perego.local/https');

    expect(renderHeader('/', ['ctaUrl' => '#contact'], 'en'))->toContain('href="#contact"');
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
    $html = renderHeader();

    expect($html)->toContain('logo-full.png')
        ->and($html)->toContain('logo-full.webp')
        ->and(substr_count($html, 'width="552" height="170"'))->toBe(2)
        ->and(substr_count($html, 'loading="eager"'))->toBeGreaterThanOrEqual(2);
});

it('uses WordPress attachment markup and dimensions when a logoId is set', function () {
    Functions\when('wp_get_attachment_image')->alias(
        fn (int $id, string $size, bool $icon, array $attributes) => $id === 42
            ? '<img width="640" height="200" src="https://perego.local/uploads/new-logo.png" class="'
                . $attributes['class'] . '" alt="' . $attributes['alt'] . '" loading="' . $attributes['loading'] . '" />'
            : ''
    );

    $html = renderHeader('/', ['logoId' => 42]);

    expect($html)->toContain('https://perego.local/uploads/new-logo.png')
        ->and($html)->toContain('width="640" height="200"')
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

it('uses published Service posts for the dropdown only when automatic mode is selected', function () {
    $first = new WP_Post();
    $first->ID = 31;
    $first->post_type = ServicePostType::POST_TYPE;
    $first->post_status = 'publish';
    $second = new WP_Post();
    $second->ID = 32;
    $second->post_type = ServicePostType::POST_TYPE;
    $second->post_status = 'publish';

    Functions\when('get_posts')->alias(fn (array $query) => $query['lang'] === 'en' ? [$first, $second] : []);
    Functions\when('get_permalink')->alias(fn (WP_Post $post) => 'https://perego.local/services/' . $post->ID);
    Functions\when('get_the_title')->alias(fn (WP_Post $post) => 'Service ' . $post->ID);

    $html = renderHeader('/', ['servicesMenuMode' => 'automatic']);

    expect($html)->toContain('>Service 31<')
        ->and($html)->toContain('href="https://perego.local/services/31"')
        ->and($html)->not->toContain('>Video Editing<');
});

it('keeps the legacy Services links in manual mode until an editor chooses Services', function () {
    expect(renderHeader('/', ['servicesMenuMode' => 'manual', 'servicesMenuOrder' => []]))
        ->toContain('/services/video-editing');
});

it('renders manually ordered Services and excludes configured source records', function () {
    $first = new WP_Post();
    $first->ID = 41;
    $first->post_type = ServicePostType::POST_TYPE;
    $first->post_status = 'publish';
    $second = new WP_Post();
    $second->ID = 42;
    $second->post_type = ServicePostType::POST_TYPE;
    $second->post_status = 'publish';

    Functions\when('get_post')->alias(fn (int $id) => $id === 41 ? $first : ($id === 42 ? $second : null));
    Functions\when('get_permalink')->alias(fn (WP_Post $post) => 'https://perego.local/services/' . $post->ID);
    Functions\when('get_the_title')->alias(fn (WP_Post $post) => 'Service ' . $post->ID);

    $html = renderHeader('/', [
        'servicesMenuMode' => 'manual',
        'servicesMenuOrder' => [42, 41],
        'servicesMenuExcludeIds' => [41],
    ]);

    expect($html)->toContain('>Service 42<')
        ->and($html)->not->toContain('>Service 41<');
});

it('resolves a manually selected Service to the current Polylang translation', function () {
    $arabic = new WP_Post();
    $arabic->ID = 72;
    $arabic->post_type = ServicePostType::POST_TYPE;
    $arabic->post_status = 'publish';

    Functions\when('pll_get_post')->alias(fn (int $id, string $locale) => $id === 71 && $locale === 'ar' ? 72 : 0);
    Functions\when('get_post')->alias(fn (int $id) => $id === 72 ? $arabic : null);
    Functions\when('get_permalink')->alias(fn (WP_Post $post) => 'https://perego.local/ar/services/video-editing-2');
    Functions\when('get_the_title')->alias(fn (WP_Post $post) => 'Arabic service');

    $html = renderHeader('/ar/', ['servicesMenuMode' => 'manual', 'servicesMenuOrder' => [71]], 'ar');

    expect($html)->toContain('>Arabic service<')
        ->and($html)->toContain('href="https://perego.local/ar/services/video-editing-2"');
});

/*
 * spec 021 T036 — the link picker. `ctaUrl` and each nav item's `href` stay the custom-URL value, so
 * every header saved before the picker existed keeps resolving through the path it always did; the
 * new keys only add the "point at a page" and "open in a new tab" behaviours on top.
 */

it('resolves a CTA that points at a page to that page permalink', function () {
    Functions\when('get_permalink')->alias(fn (int $id): string => 'https://perego.local/contact/');

    expect(renderHeader('/', ['ctaLinkKind' => 'dynamic', 'ctaPostId' => 57], 'en'))
        ->toContain('href="https://perego.local/contact/"');
});

it('falls back to the stored custom URL when a picked CTA page no longer exists', function () {
    Functions\when('get_permalink')->justReturn(false);

    expect(renderHeader('/', ['ctaLinkKind' => 'dynamic', 'ctaPostId' => 999, 'ctaUrl' => '/quote'], 'en'))
        ->toContain('href="https://perego.local/quote"');
});

it('opens the CTA in a new tab only when asked, always pairing rel=noopener', function () {
    expect(renderHeader('/', ['ctaOpenInNewTab' => true], 'en'))
        ->toContain('target="_blank" rel="noopener"');

    expect(renderHeader('/', [], 'en'))->not->toContain('target="_blank"');
});

it('resolves a nav item that points at a page, and can open it in a new tab', function () {
    Functions\when('get_permalink')->alias(fn (int $id): string => 'https://perego.local/work/case-study/');

    $html = renderHeader('/', [
        'navItemsEn' => json_encode([
            ['label' => 'Case study', 'href' => '/fallback', 'linkKind' => 'dynamic', 'postId' => 31, 'openInNewTab' => true],
        ]),
    ], 'en');

    expect($html)->toContain('href="https://perego.local/work/case-study/"')
        ->and($html)->toContain('target="_blank" rel="noopener"');
});

it('leaves a nav item with no linkKind on the original localized-path behaviour', function () {
    $html = renderHeader('/', [
        'navItemsEn' => json_encode([['label' => 'Work', 'href' => '/work']]),
    ], 'en');

    expect($html)->toContain('href="https://perego.local/work"')
        ->and($html)->not->toContain('target="_blank"');
});
