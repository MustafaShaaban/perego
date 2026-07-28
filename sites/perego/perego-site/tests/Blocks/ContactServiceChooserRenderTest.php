<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\ContactServiceChooserRenderer;
use PeregoSite\Services\LanguageService;

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_html__')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_attr__')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('__')->returnArg();
    Functions\when('get_stylesheet_directory_uri')->justReturn('https://perego.local/wp-content/themes/perego-theme');
    Functions\when('sanitize_key')->alias(fn ($v) => strtolower(preg_replace('/[^a-z0-9_\-]/', '', (string) $v)));
    Functions\when('wp_unslash')->returnArg();
    Functions\when('wp_json_encode')->alias(static fn ($v) => json_encode($v));
    // No published service posts in these tests: forces the ServicePostType::SERVICES fallback.
    Functions\when('get_posts')->justReturn([]);
});

function renderContactServiceChooser(string $locale = 'en'): string
{
    $cookie  = $locale === 'en' ? [] : ['perego_lang' => $locale];
    $service = new LanguageService(cookie: $cookie, requestUri: '/', polylangActive: false);

    return (new ContactServiceChooserRenderer($service))->render();
}

it('renders the section heading and all four service-chooser buttons', function () {
    $html = renderContactServiceChooser();

    expect($html)->toContain('Choose your service')
        ->and(preg_match_all('/data-service="[a-z-]+"/', $html))->toBe(4);
});

it('uses the home services-teaser concise English labels, not the full editorial names', function () {
    $html = renderContactServiceChooser();

    expect($html)->toContain('Video Editing')
        ->and($html)->toContain('2D Motion Graphics')
        ->and($html)->toContain('Graphic Design')
        ->and($html)->toContain('Website Making')
        ->and($html)->not->toContain('Video Editing &amp; Post-Production')
        ->and($html)->not->toContain('2D Motion Graphics &amp; Animation')
        ->and($html)->not->toContain('Graphic Design &amp; Brand Identity');
});

it('renders the same concise labels translated when the locale resolves to ar', function () {
    $html = renderContactServiceChooser('ar');

    expect($html)->toContain('مونتاج الفيديو')
        ->and($html)->toContain('موشن جرافيك ثنائي الأبعاد')
        ->and($html)->toContain('التصميم الجرافيكي')
        ->and($html)->toContain('إنشاء المواقع');
});

it('renders an empty live region the chooser can put a validation message into', function () {
    $html = renderContactServiceChooser();

    // Empty, and no `hidden` attribute: an author `display` rule beats the UA `[hidden]` sheet,
    // so the stylesheet hides it on `:empty` instead. `view.js` fills it.
    expect($html)->toContain('<p class="svc-choice-error" id="perego-services-error" role="alert"></p>')
        ->and($html)->toContain('aria-describedby="perego-services-error"')
        ->and($html)->toContain('data-error-required="Please choose at least one service."');
});

it('marks the service preselected via the ?service= query arg as selected', function () {
    $_GET['service'] = 'graphic-design';

    $html = renderContactServiceChooser();

    unset($_GET['service']);

    expect($html)->toMatch('/class="svc-choice is-selected" data-service="graphic-design" aria-pressed="true"/');
});
