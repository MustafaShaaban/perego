<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\NotFoundRenderer;
use PeregoSite\Content\GlobalContent;

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('__')->returnArg();
    Functions\when('home_url')->alias(fn (string $path = '') => 'https://perego.local' . $path);
    Functions\when('get_stylesheet_directory_uri')->justReturn('https://perego.local/wp-content/themes/perego-theme');
});

function renderNotFound(string $locale = 'en'): string
{
    return (new NotFoundRenderer(new GlobalContent($locale)))->render();
}

it('renders the 404 code, the title as the single H1, body, and two actions', function () {
    $html = renderNotFound();

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('This page took a creative detour')
        ->and($html)->toContain('>404<')
        ->and($html)->toContain('class="error-page"')
        ->and($html)->toContain('class="error-grid" aria-hidden="true"')
        ->and($html)->toContain('class="error-orbs" aria-hidden="true"')
        ->and(substr_count($html, 'class="orb orb--'))->toBe(4)
        ->and($html)->toContain('class="error-page__inner" id="main" tabindex="-1"')
        ->and($html)->toContain('alt="Perego بيريجو"')
        ->and($html)->toMatch('/href="[^"]*\/"/')
        ->and($html)->toMatch('/href="[^"]*\/contact"/')
        ->and($html)->toContain('Back to Home');
});

it('localizes the 404 into Arabic', function () {
    $html = renderNotFound('ar');

    expect($html)->toContain('هذه الصفحة سلكت منعطفًا إبداعيًا')
        ->and($html)->toContain('العودة إلى الرئيسية')
        ->and($html)->toContain('تواصل معنا');
});
