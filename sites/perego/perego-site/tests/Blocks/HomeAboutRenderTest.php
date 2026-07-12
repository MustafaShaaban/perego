<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\HomeAboutRenderer;
use PeregoSite\Services\LanguageService;

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_html__')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_attr__')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('get_stylesheet_directory_uri')->justReturn('https://perego.local/wp-content/themes/perego-theme');
});

function renderHomeAbout(string $locale = 'en'): string
{
    $cookie  = $locale === 'en' ? [] : ['perego_lang' => $locale];
    $service = new LanguageService(cookie: $cookie, requestUri: '/', polylangActive: false);

    return (new HomeAboutRenderer($service))->render();
}

it('renders the About Us and Our mission panels with the background image', function () {
    $html = renderHomeAbout();

    expect($html)->toContain('About Us')
        ->and($html)->toContain('Our mission')
        ->and($html)->toContain('home-about__bg')
        ->and($html)->toMatch('/<img [^>]*about-hooded\.png/');
});

it('exposes the section heading via aria-labelledby for the landmark', function () {
    $html = renderHomeAbout();

    expect($html)->toMatch('/aria-labelledby="home-about-title"/')
        ->and($html)->toContain('id="home-about-title"');
});

it('renders localized Arabic copy when the locale resolves to ar', function () {
    $html = renderHomeAbout('ar');

    expect($html)->toContain('من نحن')
        ->and($html)->toContain('مهمتنا')
        ->and($html)->not->toContain('About Us');
});

it('uses no hardcoded hex colors in the rendered markup', function () {
    expect(renderHomeAbout())->not->toMatch('/#[0-9a-fA-F]{6}/');
});
