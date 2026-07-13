<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\HeroSliderRenderer;
use PeregoSite\Services\LanguageService;

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_html__')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_attr__')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('__')->returnArg();
    Functions\when('home_url')->alias(fn (string $path = '') => 'https://perego.local' . $path);
    Functions\when('wp_json_encode')->alias('json_encode');
    Functions\when('get_stylesheet_directory_uri')->justReturn('https://perego.local/wp-content/themes/perego-theme');
});

function renderHero(string $locale = 'en'): string
{
    $service = new LanguageService(cookie: [], requestUri: '/', polylangActive: false);

    return (new HeroSliderRenderer($service))->render();
}

it('renders exactly three slides, the first as an h1 and the rest hidden', function () {
    $html = renderHero();

    expect(substr_count($html, 'class="hero__slide"'))->toBe(3)
        ->and($html)->toMatch('/<h1 class="hero__title">What We Believe<\/h1>/')
        ->and(substr_count($html, 'hidden'))->toBeGreaterThanOrEqual(2);
});

it('declares the Interactivity API namespace and initial context', function () {
    $html = renderHero();

    expect($html)->toContain('data-wp-interactive="perego/hero-slider"')
        ->and($html)->toContain('data-wp-init="callbacks.init"')
        ->and($html)->toMatch('/"activeIndex":0/')
        ->and($html)->toMatch('/"isPlaying":true/');
});

it('renders a dot tablist with one tab per slide and the first selected', function () {
    $html = renderHero();

    expect($html)->toContain('role="tablist"')
        ->and(substr_count($html, 'role="tab"'))->toBe(3)
        ->and($html)->toContain('aria-selected="true"')
        ->and($html)->toContain('data-wp-on--click="actions.goTo"');
});

it('renders prev/next and a pause/play control', function () {
    $html = renderHero();

    expect($html)->toContain('data-wp-on--click="actions.prev"')
        ->and($html)->toContain('data-wp-on--click="actions.next"')
        ->and($html)->toContain('data-wp-on--click="actions.togglePlay"');
});

it('renders a polite live region for slide announcements', function () {
    $html = renderHero();

    expect($html)->toContain('aria-live="polite"')
        ->and($html)->toContain('data-wp-text="state.currentSlideLabel"');
});

it('renders the Say Hello CTA linking to contact', function () {
    $html = renderHero();

    expect($html)->toContain('Say Hello!')
        ->and($html)->toContain('/contact');
});

it('binds each slide hidden state and each dot selected state to the store', function () {
    $html = renderHero();

    expect($html)->toContain('data-wp-bind--hidden="callbacks.slideHidden"')
        ->and($html)->toContain('data-wp-bind--aria-selected="callbacks.dotSelected"');
});

it('uses no hardcoded hex colors in the rendered markup', function () {
    expect(renderHero())->not->toMatch('/#[0-9a-fA-F]{6}/');
});

it('renders Arabic hero copy when the locale resolves to ar', function () {
    $service = new LanguageService(cookie: ['perego_lang' => 'ar'], requestUri: '/', polylangActive: false);
    $html = (new HeroSliderRenderer($service))->render();

    expect($html)->toContain('قل مرحبًا!');
});
