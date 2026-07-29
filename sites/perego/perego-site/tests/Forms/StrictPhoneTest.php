<?php

/**
 * Perego's stricter `phone`, which overrides the one CoreX v0.40.0 introduced.
 *
 * Upstream's rule makes the `+` optional and accepts as few as two digits, so a bare local number
 * passes. That is the exact complaint the strict rule exists for: the team receives numbers it cannot
 * dial from outside the country they were typed in. These cases pin the difference, so a future
 * framework update that quietly re-loosens it shows up here rather than in the inbox.
 *
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\Forms\Rules\StrictPhone;

$phone = static fn (mixed $value): ?string => (new StrictPhone())->validate($value, [], []);

it('accepts an international number, however it was punctuated', function () use ($phone) {
    // People really type these; a rule that rejects a correct number for its spaces teaches them the
    // form is broken rather than that their number is.
    expect($phone('+201016999700'))->toBeNull()
        ->and($phone('+20 101 699 9700'))->toBeNull()
        ->and($phone('+1 (555) 019-9900'))->toBeNull()
        ->and($phone('+44.20.7946.0958'))->toBeNull();
});

it('rejects the bare local number that upstream would accept', function () use ($phone) {
    // The client's original report: correct in Egypt, undialable from anywhere else.
    expect($phone('01016999700'))->toBe('phone');
});

it('rejects the too-short values upstream lets through', function () use ($phone) {
    // Upstream's /^\+?[1-9]\d{1,14}$/ passes every one of these.
    expect($phone('5'))->toBe('phone')
        ->and($phone('+5'))->toBe('phone')
        ->and($phone('12345'))->toBe('phone')
        ->and($phone('+1234'))->toBe('phone');
});

it('still rejects text, and a leading zero after the plus', function () use ($phone) {
    expect($phone('call me'))->toBe('phone')
        ->and($phone('+0201016999'))->toBe('phone');
});

it('leaves emptiness to the required rule, so an optional phone field stays possible', function () use ($phone) {
    expect($phone(''))->toBeNull()
        ->and($phone('   '))->toBeNull()
        ->and($phone(null))->toBeNull();
});

it('ignores a non-scalar rather than casting it into a false pass', function () use ($phone) {
    expect($phone(['+201016999700']))->toBeNull();
});

/*
 * The registration is the half that actually matters, and it is not the obvious one.
 *
 * `RuleRegistry::register()` THROWS on a duplicate name and offers no unregister, so a built-in cannot
 * be replaced — registering this as `phone` took the whole site down with an uncaught
 * InvalidArgumentException at `init`, found by loading the site rather than by any test. The rule is
 * therefore registered under a prefixed name and layered onto the field alongside CoreX's. Prefixed,
 * because the registry is shared framework surface — v0.40.0 alone added `phone`, `mime` and
 * `max_size` to it, so an unprefixed name we own is a collision waiting for the next release.
 */
it('is registered under a prefixed name, because the registry rejects a duplicate', function () {
    $provider = file_get_contents(dirname(__DIR__, 2) . '/src/PeregoSiteServiceProvider.php');

    expect($provider)->not->toBeFalse()
        ->and((string) $provider)->toContain("register('perego_phone', new \\PeregoSite\\Forms\\Rules\\StrictPhone())")
        ->and((string) $provider)->not->toContain("register('phone', new");
});

it('is actually applied to the brief form, not merely registered', function () {
    // A registered rule no field requests validates nothing at all.
    $form = file_get_contents(dirname(__DIR__, 2) . '/src/Forms/ProjectBriefForm.php');

    expect($form)->not->toBeFalse()
        ->and((string) $form)->toContain("'rules' => ['phone', 'perego_phone']");
});
