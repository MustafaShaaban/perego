<?php

/**
 * Unit tests for the headless validator (spec US1: FR-002, FR-003, SC-002, SC-006).
 *
 * @package Corex\Tests\Unit\Forms
 */

declare(strict_types=1);

use Corex\Forms\Schema\SchemaResolver;
use Corex\Forms\Validation\RuleRegistry;
use Corex\Forms\Validation\Validator;

/**
 * @param array<string,array{type?:string,rules?:list<string>,label?:string}> $fields
 * @param array<string,mixed>                                                  $values
 */
function validate(array $fields, array $values): \Corex\Forms\Validation\ValidationResult
{
    $registry = new RuleRegistry();
    $schema   = (new SchemaResolver($registry))->resolve($fields);

    return (new Validator($registry))->validate($schema, $values);
}

it('passes every rule on good input and yields no errors', function () {
    $result = validate(
        ['name' => ['rules' => ['required', 'max:80']], 'email' => ['rules' => ['email']], 'age' => ['rules' => ['numeric', 'min:18']]],
        ['name' => 'Mustafa', 'email' => 'm@example.com', 'age' => '21'],
    );

    expect($result->isValid())->toBeTrue()
        ->and($result->errors)->toBe([]);
});

it('returns the exact message key for each failing rule', function () {
    expect(validate(['f' => ['rules' => ['required']]], ['f' => ''])->errors)->toBe(['f' => 'required']);
    expect(validate(['f' => ['rules' => ['email']]], ['f' => 'nope'])->errors)->toBe(['f' => 'email']);
    expect(validate(['f' => ['rules' => ['max:3']]], ['f' => 'toolong'])->errors)->toBe(['f' => 'max']);
    expect(validate(['f' => ['rules' => ['min:3']]], ['f' => 'ab'])->errors)->toBe(['f' => 'min']);
    expect(validate(['f' => ['rules' => ['numeric']]], ['f' => 'x'])->errors)->toBe(['f' => 'numeric']);
});

it('bails at the first failing rule per field, honoring rule order', function () {
    // empty value: 'required' fails before 'email' is reached → only 'required'.
    $result = validate(['email' => ['rules' => ['required', 'email']]], ['email' => '']);

    expect($result->errors)->toBe(['email' => 'required']);
});

it('treats an absent optional field as valid', function () {
    $result = validate(['nickname' => ['rules' => ['max:20']]], []);

    expect($result->isValid())->toBeTrue();
});

it('reports a required field that is absent', function () {
    $result = validate(['name' => ['rules' => ['required']]], []);

    expect($result->errors)->toBe(['name' => 'required']);
});

it('ignores values whose field is not declared in the schema', function () {
    $result = validate(['name' => ['rules' => ['required']]], ['name' => 'A', 'sneaky' => 'x']);

    expect($result->isValid())->toBeTrue()
        ->and($result->values)->toBe(['name' => 'A']); // undeclared field dropped
});

it('accepts a phone number in E.164 form, however the visitor punctuated it', function (string $number) {
    // A phone field used to be length-capped only, so a local number nobody could dial from
    // abroad passed. Punctuation people naturally type is not an error — rejecting
    // "+20 101 699 9700" for its spaces would be pedantry, not validation.
    expect(validate(['phone' => ['rules' => ['phone']]], ['phone' => $number])->isValid())->toBeTrue();
})->with(['+201016999700', '+20 101 699 9700', '+971-4-123-4567', '+1 (415) 555-0100', '+966501234567']);

it('rejects a number with no country code, or too few or too many digits', function (string $number) {
    expect(validate(['phone' => ['rules' => ['phone']]], ['phone' => $number])->errors)
        ->toBe(['phone' => 'phone']);
})->with([
    '01016999700',        // national form: the country code is the part that matters
    '+0123456789',        // country codes never start at zero
    '+1234567',           // 7 digits — below the E.164 minimum
    '+1234567890123456',  // 16 digits — above the E.164 maximum of 15
    'call me',
    '++201016999700',
]);

it('leaves an empty phone to the required rule', function () {
    // Emptiness is one concern and format is another; conflating them would make every optional
    // phone field mandatory the moment it gained a format rule.
    expect(validate(['phone' => ['rules' => ['phone']]], ['phone' => ''])->isValid())->toBeTrue()
        ->and(validate(['phone' => ['rules' => ['phone']]], [])->isValid())->toBeTrue();
});
