<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Language\FallbackLanguageDriver;

it('resolves a nav path to the plain site URL (single URL set, language swapped client-side)', function () {
    Functions\when('home_url')->alias(fn (string $path = '') => 'https://perego.local' . $path);

    $driver = new FallbackLanguageDriver(cookie: ['perego_lang' => 'ar']);

    expect($driver->localizedUrl('/work'))->toBe('https://perego.local/work')
        ->and($driver->localizedUrl('/'))->toBe('https://perego.local/');
});

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

/*
 * spec 021 T036 — the link picker's dynamic mode with no translation plugin: one record per piece of
 * content, so the current locale's permalink is simply the record's permalink.
 */

it('resolves a picked record to its own permalink', function () {
    Functions\when('get_permalink')->alias(fn (int $id): string => 'https://perego.local/contact/');

    expect((new FallbackLanguageDriver([]))->localizedPermalink(57))
        ->toBe('https://perego.local/contact/');
});

it('returns an empty permalink for a missing record, so callers can fall back to a custom URL', function () {
    Functions\when('get_permalink')->justReturn(false);

    $driver = new FallbackLanguageDriver([]);

    expect($driver->localizedPermalink(0))->toBe('')
        ->and($driver->localizedPermalink(-1))->toBe('')
        ->and($driver->localizedPermalink(999))->toBe('');
});
