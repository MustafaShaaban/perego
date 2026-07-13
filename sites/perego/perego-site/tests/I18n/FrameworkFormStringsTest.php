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
