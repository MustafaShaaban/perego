<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\Blocks\LinkTarget;
use PeregoSite\Language\LanguageDriver;

/**
 * A driver double that records what it was asked for, so the tests assert the RULE (which resolution
 * path a link takes) rather than WordPress's permalink output.
 */
function linkTargetDriver(string $permalink = '', string $locale = 'en'): LanguageDriver
{
    return new class ($permalink, $locale) implements LanguageDriver {
        public function __construct(private string $permalink, private string $locale)
        {
        }

        public function currentLocale(): string
        {
            return $this->locale;
        }

        public function isRtl(): bool
        {
            return $this->locale === 'ar';
        }

        public function availableLocales(): array
        {
            return ['en', 'ar'];
        }

        public function urlFor(string $locale): string
        {
            return '/?lang=' . $locale;
        }

        public function managesLanguageViaUrl(): bool
        {
            return true;
        }

        public function localizedUrl(string $path): string
        {
            return 'https://perego.test/' . $this->locale . ltrim($path, '/');
        }

        public function localizedPermalink(int $postId): string
        {
            return $postId > 0 ? $this->permalink : '';
        }
    };
}

it('localizes an internal path for a custom link', function () {
    $target = new LinkTarget(linkTargetDriver());

    expect($target->href(['href' => '/contact']))->toBe('https://perego.test/encontact');
});

it('uses an external, mailto, tel, or anchor URL verbatim', function () {
    $target = new LinkTarget(linkTargetDriver());

    expect($target->href(['href' => 'https://example.com/x']))->toBe('https://example.com/x')
        ->and($target->href(['href' => '//cdn.example.com/x']))->toBe('//cdn.example.com/x')
        ->and($target->href(['href' => 'mailto:hi@perego.test']))->toBe('mailto:hi@perego.test')
        ->and($target->href(['href' => 'tel:+201000000000']))->toBe('tel:+201000000000')
        ->and($target->href(['href' => '#about']))->toBe('#about');
});

it('falls back to the caller-supplied route when the link is empty', function () {
    $target = new LinkTarget(linkTargetDriver());

    expect($target->href([], '/contact'))->toBe('https://perego.test/encontact')
        ->and($target->href(['href' => '   '], '/contact'))->toBe('https://perego.test/encontact');
});

it('treats a link with no linkKind as custom, so links saved before T036 are unchanged', function () {
    $target = new LinkTarget(linkTargetDriver('https://perego.test/should-not-be-used'));

    expect($target->href(['href' => '/work', 'postId' => 42]))->toBe('https://perego.test/enwork');
});

it('resolves a dynamic link to the current locale permalink', function () {
    $target = new LinkTarget(linkTargetDriver('https://perego.test/ar/khadamat/', 'ar'));

    expect($target->href(['linkKind' => 'dynamic', 'postId' => 42, 'href' => '/ignored']))
        ->toBe('https://perego.test/ar/khadamat/');
});

it('falls back to the custom href when a dynamic target no longer resolves', function () {
    // A deleted or unpublished record: the picker still holds its id, but there is no permalink.
    $target = new LinkTarget(linkTargetDriver(''));

    expect($target->href(['linkKind' => 'dynamic', 'postId' => 999, 'href' => '/work']))
        ->toBe('https://perego.test/enwork')
        // …and with nothing stored at all, the caller's route — never an empty href.
        ->and($target->href(['linkKind' => 'dynamic', 'postId' => 0], '/contact'))
        ->toBe('https://perego.test/encontact');
});

it('emits target and rel only when the link opens in a new tab', function () {
    $target = new LinkTarget(linkTargetDriver());

    expect($target->targetAttributes(['openInNewTab' => true]))->toBe(' target="_blank" rel="noopener"')
        ->and($target->targetAttributes(['openInNewTab' => false]))->toBe('')
        ->and($target->targetAttributes([]))->toBe('');
});

/*
 * `hrefIfSet` exists because a pure renderer owns its own default route, and resolving that route
 * through the driver is NOT a no-op — `home_url('/contact')` and the driver's localized permalink for
 * the same page differ by a trailing slash. A curl-diff of `/work/` caught exactly that during T036.
 */

it('answers with nothing for an unconfigured link, so a pure renderer keeps its own default route', function () {
    $target = new LinkTarget(linkTargetDriver('https://perego.test/should-not-be-used'));

    expect($target->hrefIfSet([]))->toBe('')
        ->and($target->hrefIfSet(['href' => '']))->toBe('')
        ->and($target->hrefIfSet(['href' => '  ']))->toBe('')
        // "dynamic" with nothing picked yet is still unconfigured.
        ->and($target->hrefIfSet(['linkKind' => 'dynamic', 'postId' => 0]))->toBe('');
});

it('answers normally as soon as the link is configured either way', function () {
    $target = new LinkTarget(linkTargetDriver('https://perego.test/ar/page/', 'ar'));

    expect($target->hrefIfSet(['href' => '/quote']))->toBe('https://perego.test/arquote')
        ->and($target->hrefIfSet(['linkKind' => 'dynamic', 'postId' => 7]))->toBe('https://perego.test/ar/page/');
});
