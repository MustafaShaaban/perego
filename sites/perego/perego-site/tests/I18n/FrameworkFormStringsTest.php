<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\I18n\FrameworkFormStrings;

it('translates a known corex form string to Arabic when the locale is Arabic', function () {
    Functions\when('get_locale')->justReturn('ar');

    $out = (new FrameworkFormStrings())->translate('Send', 'Send', 'corex');

    expect($out)->toBe('إرسال');
});

it('leaves the string untouched on non-Arabic locales', function () {
    Functions\when('get_locale')->justReturn('en_US');

    $out = (new FrameworkFormStrings())->translate('Send', 'Send', 'corex');

    expect($out)->toBe('Send');
});

it('never touches a string from another text domain', function () {
    Functions\when('get_locale')->justReturn('ar');

    // Same source text, but a different domain must pass through unchanged.
    $out = (new FrameworkFormStrings())->translate('Send', 'Send', 'perego-site');

    expect($out)->toBe('Send');
});

it('leaves unknown corex strings untouched', function () {
    Functions\when('get_locale')->justReturn('ar');

    $out = (new FrameworkFormStrings())->translate('Save changes', 'Save changes', 'corex');

    expect($out)->toBe('Save changes');
});

it('maps every string it claims to, on an ar-variant locale', function () {
    Functions\when('get_locale')->justReturn('ar');

    $subject = new FrameworkFormStrings();

    expect($subject->translate('Thank you — your message has been sent.', 'Thank you — your message has been sent.', 'corex'))
        ->toBe('شكرًا لك — تم إرسال رسالتك.')
        ->and($subject->translate('Please review the highlighted fields and try again.', 'Please review the highlighted fields and try again.', 'corex'))
        ->toBe('يرجى مراجعة الحقول المميزة والمحاولة مرة أخرى.');
});

/*
 * The durable guard, added when CoreX v0.40.0 reworded three of these strings (2026-07-29).
 *
 * This map keys on the EXACT English source, which means a rewording upstream does not fail loudly —
 * `isset(self::AR[$text])` simply misses and the Arabic page silently serves English. That is the worst
 * way for a translation to break: nothing errors, nothing logs, and it is only ever caught by someone
 * reading the Arabic site. v0.40.0 did exactly that to `url`, `phone` and the new `default`.
 *
 * Parsing the framework's own source rather than importing the class, because `ValidationMessages::all()`
 * calls `__()` at runtime and these tests have no WordPress. The same parse-the-other-language trick
 * `EditorPanels/schema.test.js` uses.
 */
it('has an Arabic entry for every message CoreX can render on a form', function () {
    $source = file_get_contents(dirname(__DIR__, 5) . '/plugins/corex-forms/src/Block/ValidationMessages.php');

    expect($source)->not->toBeFalse();

    preg_match_all("/=> *__\('([^']+)', *'corex'\)/", (string) $source, $matches);
    $messages = $matches[1];

    expect($messages)->not->toBeEmpty('ValidationMessages::all() parsed as empty — the format changed.');

    $translator = new FrameworkFormStrings();
    Functions\when('get_locale')->justReturn('ar');

    $untranslated = array_values(array_filter(
        $messages,
        static fn (string $text): bool => $translator->translate($text, $text, 'corex') === $text,
    ));

    expect($untranslated)->toBe([], 'These CoreX messages would render English on /ar/: ' . implode(' | ', $untranslated));
});

it('has an Arabic entry for every submission-level message CoreX can return', function () {
    $source = file_get_contents(dirname(__DIR__, 5) . '/plugins/corex-forms/src/Submission/FormSubmissionService.php');

    // Asserted, not assumed: a wrong path returns false, which parses to zero messages and would let
    // this test pass vacuously while covering nothing.
    expect($source)->not->toBeFalse();

    preg_match_all("/__\('([^']+)', *'corex'\)/", (string) $source, $matches);

    $translator = new FrameworkFormStrings();
    Functions\when('get_locale')->justReturn('ar');

    $untranslated = array_values(array_filter(
        array_unique($matches[1]),
        static fn (string $text): bool => $translator->translate($text, $text, 'corex') === $text,
    ));

    expect($untranslated)->toBe([], 'These CoreX messages would render English on /ar/: ' . implode(' | ', $untranslated));
});
