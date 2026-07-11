<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\Language\FallbackLanguageDriver;
use PeregoSite\Language\PolylangLanguageDriver;
use PeregoSite\Services\LanguageService;

it('resolves the Polylang driver when Polylang is reported active', function () {
    $service = new LanguageService(cookie: [], requestUri: '/', polylangActive: true);

    expect($service->driver())->toBeInstanceOf(PolylangLanguageDriver::class);
});

it('falls back when Polylang is not active, per constitution IX', function () {
    $service = new LanguageService(cookie: ['perego_lang' => 'ar'], requestUri: '/', polylangActive: false);

    expect($service->driver())->toBeInstanceOf(FallbackLanguageDriver::class)
        ->and($service->driver()->currentLocale())->toBe('ar');
});

it('resolves the driver only once per instance', function () {
    $service = new LanguageService(cookie: [], requestUri: '/', polylangActive: false);

    expect($service->driver())->toBe($service->driver());
});

it('auto-detects Polylang via function_exists when not told explicitly', function () {
    $service = new LanguageService(cookie: [], requestUri: '/');

    // Neither driver is installed in this headless suite, so auto-detection must fall back.
    expect($service->driver())->toBeInstanceOf(FallbackLanguageDriver::class);
});
