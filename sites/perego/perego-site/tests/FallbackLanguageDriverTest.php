<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\Language\FallbackLanguageDriver;

it('defaults to English when no cookie is set', function () {
    $driver = new FallbackLanguageDriver(cookie: []);

    expect($driver->currentLocale())->toBe('en')
        ->and($driver->isRtl())->toBeFalse();
});

it('reads the current locale from the cookie when present', function () {
    $driver = new FallbackLanguageDriver(cookie: ['perego_lang' => 'ar']);

    expect($driver->currentLocale())->toBe('ar')
        ->and($driver->isRtl())->toBeTrue();
});

it('ignores a cookie value that is not an offered locale', function () {
    $driver = new FallbackLanguageDriver(cookie: ['perego_lang' => 'fr']);

    expect($driver->currentLocale())->toBe('en');
});

it('reads the current locale from a ?lang query var, which beats the cookie', function () {
    $driver = new FallbackLanguageDriver(cookie: ['perego_lang' => 'en'], requestUri: '/work?lang=ar');

    expect($driver->currentLocale())->toBe('ar')
        ->and($driver->isRtl())->toBeTrue();
});

it('ignores a ?lang query var that is not an offered locale and falls back to the cookie', function () {
    $driver = new FallbackLanguageDriver(cookie: ['perego_lang' => 'ar'], requestUri: '/work?lang=fr');

    expect($driver->currentLocale())->toBe('ar');
});

it('reports that it does NOT manage language through the URL (needs client-side persistence)', function () {
    expect((new FallbackLanguageDriver(cookie: []))->managesLanguageViaUrl())->toBeFalse();
});

it('lists English then Arabic as the available locales', function () {
    $driver = new FallbackLanguageDriver(cookie: []);

    expect($driver->availableLocales())->toBe(['en', 'ar']);
});

it('builds a switch URL carrying a lang query var for the requested locale', function () {
    $driver = new FallbackLanguageDriver(cookie: [], requestUri: '/services?foo=bar');

    expect($driver->urlFor('ar'))->toBe('/services?foo=bar&lang=ar');
});

it('replaces an existing lang query var rather than duplicating it', function () {
    $driver = new FallbackLanguageDriver(cookie: [], requestUri: '/services?lang=en');

    expect($driver->urlFor('ar'))->toBe('/services?lang=ar');
});

it('rejects a locale that is not offered', function () {
    $driver = new FallbackLanguageDriver(cookie: []);

    expect(fn () => $driver->urlFor('fr'))->toThrow(InvalidArgumentException::class);
});
