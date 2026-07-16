<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Forms\QuickMessageForm;

beforeEach(function () {
    Functions\when('__')->returnArg();
});

it('exposes the perego-quick-message slug the footer block references', function () {
    expect((new QuickMessageForm())->slug)->toBe('perego-quick-message')
        ->and(QuickMessageForm::SLUG)->toBe('perego-quick-message');
});

it('defines name, email, and message with server-enforceable rules', function () {
    $fields = (new QuickMessageForm())->fields();

    expect(array_keys($fields))->toBe(['name', 'email', 'message'])
        ->and($fields['name']['type'])->toBe('text')
        ->and($fields['name']['rules'])->toContain('required')
        ->and($fields['name']['rules'])->toContain('max:120')
        ->and($fields['email']['type'])->toBe('email')
        ->and($fields['email']['rules'])->toContain('email')
        ->and($fields['message']['type'])->toBe('textarea')
        // The handoff caps the message at 1200 (spec 020 D2) — server rule + browser maxlength agree.
        ->and($fields['message']['rules'])->toContain('max:1200')
        ->and($fields['message']['attrs'])->toBe(['maxlength' => '1200']);
});

it('bounds every field so no rule is unlimited (spec Phase 7 limits)', function () {
    foreach ((new QuickMessageForm())->fields() as $name => $field) {
        $hasMax = false;
        foreach ($field['rules'] as $rule) {
            if (str_starts_with($rule, 'max:')) {
                $hasMax = true;
            }
        }
        expect($hasMax)->toBeTrue("field {$name} must declare a max length");
    }
});

it('inherits the engine default listeners so submissions store and email', function () {
    $listeners = (new QuickMessageForm())->listeners();

    expect($listeners)->toContain(\Corex\Forms\Listeners\StoreSubmissionListener::class)
        ->and($listeners)->toContain(\Corex\Forms\Listeners\SendEmailListener::class);
});

it('gives the form a human label for the block selector', function () {
    expect((new QuickMessageForm())->label())->toBe('Quick message');
});
