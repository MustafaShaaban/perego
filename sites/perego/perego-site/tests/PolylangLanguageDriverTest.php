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

/**
 * Shared stubs for localizedUrl(): a small in-memory site where EN 57=contact, 43=journal (posts page),
 * and their AR translations are 58 and 96; the AR home is /ar/, and the default language is English.
 */
function stubLocalizedUrlSite(string $locale): void
{
    Functions\when('pll_current_language')->justReturn($locale);
    Functions\when('pll_default_language')->justReturn('en');
    Functions\when('pll_home_url')->alias(fn (string $l) => "https://perego.local/$l/");
    Functions\when('home_url')->alias(fn (string $p = '') => 'https://perego.local' . $p);
    Functions\when('untrailingslashit')->alias(fn (string $u) => rtrim($u, '/'));
    Functions\when('get_option')->alias(fn (string $k) => $k === 'page_for_posts' ? 43 : false);
    Functions\when('url_to_postid')->alias(fn (string $url) => $url === 'https://perego.local/contact' ? 57 : 0);
    // A post's own-language lookup returns itself; the AR lookup returns the translation.
    Functions\when('pll_get_post')->alias(fn (int $id, string $l) => $l === 'en' ? $id : ([57 => 58, 43 => 96][$id] ?? 0));
    Functions\when('get_permalink')->alias(fn (int $id) => [
        57 => 'https://perego.local/contact/',
        58 => 'https://perego.local/ar/contact-2/',
        43 => 'https://perego.local/journal/',
        96 => 'https://perego.local/ar/blog/',
    ][$id] ?? false);
}

it('resolves the home path and its in-page anchors against the localized home', function () {
    stubLocalizedUrlSite('ar');
    $driver = new PolylangLanguageDriver();

    expect($driver->localizedUrl('/'))->toBe('https://perego.local/ar/')
        ->and($driver->localizedUrl('/#about'))->toBe('https://perego.local/ar/#about');
});

it('resolves a real page/CPT-single path to its translation permalink', function () {
    stubLocalizedUrlSite('ar');

    expect((new PolylangLanguageDriver())->localizedUrl('/contact'))->toBe('https://perego.local/ar/contact-2/');
});

it('resolves the blog index to the posts page translation, not a language-prefixed guess', function () {
    stubLocalizedUrlSite('ar');

    expect((new PolylangLanguageDriver())->localizedUrl('/journal'))->toBe('https://perego.local/ar/blog/');
});

it('resolves a CPT archive under the language directory prefix', function () {
    stubLocalizedUrlSite('ar');

    expect((new PolylangLanguageDriver())->localizedUrl('/work'))->toBe('https://perego.local/ar/work/');
});

it('leaves paths bare in the default language (no prefix, no broken translation)', function () {
    stubLocalizedUrlSite('en');
    $driver = new PolylangLanguageDriver();

    expect($driver->localizedUrl('/work'))->toBe('https://perego.local/work')
        ->and($driver->localizedUrl('/contact'))->toBe('https://perego.local/contact/');
});

/*
 * spec 021 T036 — the link picker's dynamic mode. An editor picks a record in whichever language they
 * happen to be working in; the link must resolve to the translation for the language being SERVED.
 */

it('resolves a picked record to its translation for the current locale', function () {
    Functions\when('pll_current_language')->justReturn('ar');
    // The editor picked the English Service (id 25); Polylang links it to the Arabic one (id 26).
    Functions\when('pll_get_post')->alias(fn (int $id, string $locale): int => $id === 25 && $locale === 'ar' ? 26 : $id);
    Functions\when('get_permalink')->alias(fn (int $id): string => 'https://perego.local/' . ($id === 26 ? 'ar/khadamat/' : 'services/video-editing/'));

    expect((new PolylangLanguageDriver())->localizedPermalink(25))
        ->toBe('https://perego.local/ar/khadamat/');
});

it('falls back to the picked record when it has no translation for the current locale', function () {
    Functions\when('pll_current_language')->justReturn('ar');
    // A half-translated site: pll_get_post returns falsy, so the record itself must still be linked.
    Functions\when('pll_get_post')->justReturn(0);
    Functions\when('get_permalink')->alias(fn (int $id): string => 'https://perego.local/work/only-in-english/');

    expect((new PolylangLanguageDriver())->localizedPermalink(25))
        ->toBe('https://perego.local/work/only-in-english/');
});

it('returns an empty permalink for a missing record, so callers can fall back to a custom URL', function () {
    Functions\when('pll_current_language')->justReturn('en');
    Functions\when('pll_get_post')->justReturn(0);
    Functions\when('get_permalink')->justReturn(false);

    $driver = new PolylangLanguageDriver();

    expect($driver->localizedPermalink(0))->toBe('')
        ->and($driver->localizedPermalink(-1))->toBe('')
        ->and($driver->localizedPermalink(999))->toBe('');
});
