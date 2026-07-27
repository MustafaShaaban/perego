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
    Functions\when('wp_kses_post')->returnArg();
    Functions\when('esc_html__')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_attr__')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('__')->returnArg();
    Functions\when('home_url')->alias(fn (string $path = '') => 'https://perego.local' . $path);
    Functions\when('wp_json_encode')->alias('json_encode');
    Functions\when('get_stylesheet_directory_uri')->justReturn('https://perego.local/wp-content/themes/perego-theme');
});

/** @param array<string,string> $attributes */
function renderHero(string $locale = 'en', array $attributes = []): string
{
    $cookie  = $locale === 'en' ? [] : ['perego_lang' => $locale];
    $service = new LanguageService(cookie: $cookie, requestUri: '/', polylangActive: false);

    return (new HeroSliderRenderer($service))->render($attributes);
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

it('renders dots only — no prev/next or pause/play controls (owner-directed divergence from the handoff)', function () {
    $html = renderHero();

    expect($html)->not->toContain('hero__controls')
        ->and($html)->not->toContain('data-wp-on--click="actions.prev"')
        ->and($html)->not->toContain('data-wp-on--click="actions.next"')
        ->and($html)->not->toContain('data-wp-on--click="actions.togglePlay"');
});

it('renders a polite live region for slide announcements', function () {
    $html = renderHero();

    expect($html)->toContain('aria-live="polite"')
        ->and($html)->toContain('data-wp-text="state.currentSlideLabel"');
});

it('renders the Say Hello CTA linking to contact', function () {
    $html = renderHero();

    expect($html)->toContain('Say Hello!')
        ->and($html)->toContain('/start-a-project')
        ->and($html)->toContain('class="btn btn--accent"');
});

it('preserves the handoff container and hero entry class contract', function () {
    $html = renderHero();

    expect($html)->toContain('class="container hero__inner"')
        ->and($html)->toContain('class="hero__content hero-enter"');
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
    expect(renderHero('ar'))->toContain('قل مرحبًا!');
});

it('prefers an editor-set En/Ar block attribute over the seed, per locale', function () {
    $htmlEn = renderHero('en', ['slide1TitleEn' => 'A Bold New Headline', 'ctaEn' => 'Talk to us']);
    $htmlAr = renderHero('ar', ['slide1TitleAr' => 'عنوان جديد جريء', 'ctaAr' => 'تحدث معنا']);

    expect($htmlEn)->toContain('A Bold New Headline')->toContain('Talk to us')->not->toContain('What We Believe')
        ->and($htmlAr)->toContain('عنوان جديد جريء')->toContain('تحدث معنا');
});
