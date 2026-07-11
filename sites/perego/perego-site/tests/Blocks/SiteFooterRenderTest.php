<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\SiteFooterRenderer;

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_html__')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_attr__')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('home_url')->alias(fn (string $path = '') => 'https://perego.local' . $path);
});

it('renders the 3-column layout: contact, quick-message entry point, careers entry point', function () {
    $html = (new SiteFooterRenderer())->render();

    expect($html)->toContain('perego-footer__contact')
        ->and($html)->toContain('perego-footer__quick-message')
        ->and($html)->toContain('perego-footer__careers');
});

it('renders the bottom bar with the current year and legal links', function () {
    $html = (new SiteFooterRenderer())->render();

    expect($html)->toContain((string) gmdate('Y'))
        ->and($html)->toContain('/terms')
        ->and($html)->toContain('/privacy');
});

it('renders the flat 2-column variant on the contact page', function () {
    $html = (new SiteFooterRenderer())->render(flat: true);

    expect($html)->toContain('perego-footer--flat')
        ->and($html)->not->toContain('perego-footer__careers');
});
