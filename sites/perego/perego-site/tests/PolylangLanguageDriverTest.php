<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Language\PolylangLanguageDriver;

it('reports the locale Polylang currently reports', function () {
    Functions\when('pll_current_language')->justReturn('ar');

    $driver = new PolylangLanguageDriver();

    expect($driver->currentLocale())->toBe('ar')
        ->and($driver->isRtl())->toBeTrue();
});

it('defaults to English when Polylang has no current language yet', function () {
    Functions\when('pll_current_language')->justReturn('');

    $driver = new PolylangLanguageDriver();

    expect($driver->currentLocale())->toBe('en')
        ->and($driver->isRtl())->toBeFalse();
});

it('lists the locales Polylang reports as configured', function () {
    Functions\when('pll_languages_list')->justReturn(['en', 'ar']);

    $driver = new PolylangLanguageDriver();

    expect($driver->availableLocales())->toBe(['en', 'ar']);
});

it('resolves the switch URL from the raw translations Polylang reports for the current page', function () {
    Functions\when('pll_the_languages')->justReturn([
        ['slug' => 'en', 'url' => 'https://perego.local/services/'],
        ['slug' => 'ar', 'url' => 'https://perego.local/ar/services/'],
    ]);

    $driver = new PolylangLanguageDriver();

    expect($driver->urlFor('ar'))->toBe('https://perego.local/ar/services/');
});

it('reports that it manages language through the URL (Polylang directory URLs carry the language)', function () {
    expect((new PolylangLanguageDriver())->managesLanguageViaUrl())->toBeTrue();
});

it('rejects a locale Polylang does not offer a translation for', function () {
    Functions\when('pll_the_languages')->justReturn([
        ['slug' => 'en', 'url' => 'https://perego.local/services/'],
    ]);

    $driver = new PolylangLanguageDriver();

    expect(fn () => $driver->urlFor('ar'))->toThrow(InvalidArgumentException::class);
});
