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
    Functions\when('esc_html__')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_attr__')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('home_url')->alias(fn (string $path = '') => 'https://perego.local' . $path);
    Functions\when('get_stylesheet_directory_uri')->justReturn('https://perego.local/wp-content/themes/perego-theme');
    Functions\when('__')->alias(fn (string $text) => $text);
});

function renderFooter(bool $flat = false): string
{
    $service = new LanguageService(cookie: [], requestUri: '/', polylangActive: false);

    return (new SiteFooterRenderer($service))->render($flat);
}

it('renders the 3-column layout: contact, quick-message entry point, careers entry point', function () {
    $html = renderFooter();

    expect($html)->toContain('footer-col footer-contact')
        ->and($html)->toContain('footer-col footer-quick-message')
        ->and($html)->toContain('footer-col footer-careers');
});

it('renders the approved contact channels and social links', function () {
    $html = renderFooter();

    expect($html)->toContain('mailto:mostafa.emam3313@gmail.com')
        ->and($html)->toContain('tel:+996562932759')
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

it('renders the flat 2-column variant on the contact page: contact + careers, no quick-message', function () {
    // The handoff's contact.html keeps the contact and "Join us"/careers columns and drops the
    // quick-message form (the contact page already carries its own message form).
    $html = renderFooter(flat: true);

    expect($html)->toContain('site-footer--flat')
        ->and($html)->toContain('footer-col footer-contact')
        ->and($html)->toContain('footer-col footer-careers')
        ->and($html)->not->toContain('footer-col footer-quick-message');
});
