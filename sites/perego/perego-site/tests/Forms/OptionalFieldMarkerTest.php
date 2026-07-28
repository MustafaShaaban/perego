<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Forms\OptionalFieldMarker;

beforeEach(function () {
    Functions\when('esc_html__')->returnArg();
});

/** A label in the exact shape `Corex\Forms\Block\FieldRenderer` emits. */
function label(string $id, string $text, bool $required = false): string
{
    $marker = $required ? ' <span class="corex-form__required" aria-hidden="true">*</span>' : '';

    return '<label for="' . $id . '" class="corex-form__label corex-form__label--visible">'
        . $text . $marker . '</label>';
}

it('marks a non-required label and leaves required ones alone', function () {
    $html = label('phone', 'Phone') . label('name', 'Full name', required: true);

    $out = (new OptionalFieldMarker())->mark($html);

    expect(substr_count($out, 'corex-form__optional'))->toBe(1)
        ->and($out)->toContain('Phone <span class="corex-form__optional">(optional)</span></label>')
        ->and($out)->toContain('Full name <span class="corex-form__required" aria-hidden="true">*</span></label>');
});

it('marks every optional label in a form, not just the first', function () {
    $html = label('phone', 'Phone') . label('company', 'Company') . label('budget', 'Estimated budget');

    expect(substr_count((new OptionalFieldMarker())->mark($html), 'corex-form__optional'))->toBe(3);
});

it('is idempotent — a second pass does not add a second marker', function () {
    $marker = new OptionalFieldMarker();
    $once = $marker->mark(label('phone', 'Phone'));

    expect($marker->mark($once))->toBe($once);
});

it('passes the translated string through the catalogue rather than hardcoding it', function () {
    Functions\when('esc_html__')->alias(fn (string $text) => $text === '(optional)' ? '(اختياري)' : $text);

    expect((new OptionalFieldMarker())->mark(label('phone', 'الهاتف')))
        ->toContain('(اختياري)');
});

it('leaves markup with no visible labels untouched', function () {
    $html = '<div class="corex-form__field"><input type="text" /></div>';

    expect((new OptionalFieldMarker())->mark($html))->toBe($html);
});

it('does not touch a screen-reader-only label, which has no --visible class', function () {
    $html = '<label for="x" class="corex-form__label screen-reader-text">Search</label>';

    expect((new OptionalFieldMarker())->mark($html))->toBe($html);
});
