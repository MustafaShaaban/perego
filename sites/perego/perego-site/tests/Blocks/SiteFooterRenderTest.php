<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\SiteFooterRenderer;
use PeregoSite\Services\LanguageService;

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('wp_kses_post')->returnArg();
    Functions\when('esc_html__')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_attr__')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('home_url')->alias(fn (string $path = '') => 'https://perego.local' . $path);
    Functions\when('get_stylesheet_directory_uri')->justReturn('https://perego.local/wp-content/themes/perego-theme');
    Functions\when('__')->alias(fn (string $text) => $text);
});

/** @param array<string,string> $attributes */
function renderFooter(bool $flat = false, array $attributes = [], string $locale = 'en'): string
{
    $cookie  = $locale === 'en' ? [] : ['perego_lang' => $locale];
    $service = new LanguageService(cookie: $cookie, requestUri: '/', polylangActive: false);

    return (new SiteFooterRenderer($service))->render($flat, $attributes);
}

it('renders the 3-column layout: contact, quick-message entry point, careers entry point', function () {
    $html = renderFooter();

    expect($html)->toContain('footer-col footer-contact')
        ->and($html)->toContain('footer-col footer-quick-message')
        ->and($html)->toContain('footer-col footer-careers');
});

it('renders the approved contact channels and social links', function () {
    $html = renderFooter();

    expect($html)->toContain('mailto:info@peregoads.com')
        ->and($html)->toContain('tel:+966562932759')
        ->and($html)->toContain('tel:+201115485572')
        ->and($html)->toContain('footer-social');
});

it('renders the handoff bottom bar: full studio copyright, Journal, and the legal links', function () {
    $html = renderFooter();

    expect($html)->toContain((string) gmdate('Y'))
        ->and($html)->toContain('Perego Creative Studio — بيريجو. All rights reserved.')
        ->and($html)->toContain('/journal')
        ->and($html)->toContain('/terms')
        ->and($html)->toContain('/privacy');
});

it('uses an editor-set bottom-bar copyright, replacing the {year} token with the current year', function () {
    $html = renderFooter(false, ['copyrightEn' => '© {year} My Studio']);

    expect($html)->toContain('© ' . gmdate('Y') . ' My Studio')
        ->and($html)->not->toContain('Perego Creative Studio — بيريجو');
});

it('uses the Arabic bottom-bar copyright on the ar locale', function () {
    $html = renderFooter(false, ['copyrightEn' => 'EN line', 'copyrightAr' => 'حقوق {year}'], 'ar');

    expect($html)->toContain('حقوق ' . gmdate('Y'))
        ->and($html)->not->toContain('EN line');
});

it('renders editor-set legal links, replacing the default Journal/Terms/Privacy set', function () {
    $links = (string) json_encode([
        ['label' => 'Sitemap', 'href' => '/sitemap'],
        ['label' => 'Partner', 'href' => 'https://example.com'],
    ]);
    $html = renderFooter(false, ['legalLinksEn' => $links]);

    expect($html)->toContain('>Sitemap</a>')
        ->and($html)->toContain('/sitemap')
        ->and($html)->toContain('https://example.com')
        ->and($html)->not->toContain('Privacy Policy');
});

it('keeps the default legal links when no legalLinks attribute is set', function () {
    $html = renderFooter(false, ['copyrightEn' => 'anything']);

    expect($html)->toContain('/journal')
        ->and($html)->toContain('/terms')
        ->and($html)->toContain('/privacy');
});

it('renders the flat 2-column variant on the contact page: contact + careers, no quick-message', function () {
    // The handoff's contact.html keeps the contact and "Join us"/careers columns and drops the
    // quick-message form (the contact page already carries its own message form).
    $html = renderFooter(flat: true);

    expect($html)->toContain('site-footer--flat')
        ->and($html)->toContain('footer-col footer-contact')
        ->and($html)->toContain('footer-col footer-careers')
        ->and($html)->not->toContain('footer-col footer-quick-message');
});

it('prefers the editor-set contactChannels/socialLinks JSON attribute over the seed', function () {
    $channels = json_encode([['label' => 'hello@perego.com', 'href' => 'mailto:hello@perego.com']]);
    $social = json_encode([['network' => 'Instagram', 'href' => 'https://instagram.com/perego']]);

    $html = renderFooter(attributes: ['contactChannels' => $channels, 'socialLinks' => $social]);

    expect($html)->toContain('mailto:hello@perego.com')
        ->and($html)->not->toContain('info@peregoads.com')
        ->and($html)->toContain('https://instagram.com/perego');
});

it('supports more than the original 4/5 seed entries — the "increase contacts" ask', function () {
    $channels = json_encode([
        ['label' => 'one@perego.com', 'href' => 'mailto:one@perego.com'],
        ['label' => 'two@perego.com', 'href' => 'mailto:two@perego.com'],
        ['label' => 'three@perego.com', 'href' => 'mailto:three@perego.com'],
        ['label' => 'four@perego.com', 'href' => 'mailto:four@perego.com'],
        ['label' => 'five@perego.com', 'href' => 'mailto:five@perego.com'],
        ['label' => 'six@perego.com', 'href' => 'mailto:six@perego.com'],
    ]);

    $html = renderFooter(attributes: ['contactChannels' => $channels]);

    expect(substr_count($html, '<li><a href='))->toBeGreaterThanOrEqual(6);
});

it('skips a social link whose network has no matching icon, instead of rendering a broken one', function () {
    $social = json_encode([['network' => 'TikTok', 'href' => 'https://tiktok.com/@perego']]);

    $html = renderFooter(attributes: ['socialLinks' => $social]);

    expect($html)->not->toContain('tiktok.com');
});

it('falls back to the seed contact channels/social links when the attribute is empty or invalid JSON', function () {
    expect(renderFooter(attributes: ['contactChannels' => '']))->toContain('info@peregoads.com')
        ->and(renderFooter(attributes: ['contactChannels' => 'not-json']))->toContain('info@peregoads.com')
        ->and(renderFooter(attributes: ['socialLinks' => '[]']))->toContain('footer-social');
});

it('prefers the editor-set En/Ar blurb block attribute over the GlobalContent seed, per locale', function () {
    $htmlEn = renderFooter(attributes: ['blurbEn' => 'A custom English blurb.'], locale: 'en');
    $htmlAr = renderFooter(attributes: ['blurbAr' => 'نص مخصص بالعربية.'], locale: 'ar');

    expect($htmlEn)->toContain('A custom English blurb.')
        ->and($htmlAr)->toContain('نص مخصص بالعربية.');
});
