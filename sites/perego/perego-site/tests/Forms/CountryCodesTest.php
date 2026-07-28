<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\Forms\CountryCodes;

it('leads with the studio\'s primary markets rather than an alphabet', function () {
    $iso = array_column(CountryCodes::options(), 'iso');

    // A visitor in Cairo should not scroll past Afghanistan to find Egypt.
    expect(array_slice($iso, 0, 3))->toBe(['EG', 'SA', 'AE'])
        ->and($iso[0])->toBe(CountryCodes::DEFAULT_ISO);
});

it('labels each option with the country and its dial code, in the page language', function () {
    $en = CountryCodes::options('en');
    $ar = CountryCodes::options('ar');

    expect($en[0]['label'])->toBe('Egypt (+20)')
        ->and($ar[0]['label'])->toBe('مصر (+20)')
        // The dial code itself never localizes — it is what gets dialled.
        ->and($ar[0]['dial'])->toBe('+20');
});

it('carries a short label for the closed picker, distinct from the list label', function () {
    $en = CountryCodes::options('en');
    $ar = CountryCodes::options('ar');

    // The picker sits in a ~96px control inside a half-width field; a trigger rendering "United Arab
    // Emirates (+971)" squeezed the number input out (client report 2026-07-28). The open list keeps
    // the full name, which is where the room is.
    expect($en[0]['short'])->toBe('EG +20')
        ->and($en[0]['label'])->toBe('Egypt (+20)')
        // An ISO code and a dial code are what you read off a SIM; localizing them helps nobody dial.
        ->and($ar[0]['short'])->toBe('EG +20');
});

it('gives every country a short label of the form "ISO +dial"', function () {
    foreach (CountryCodes::options() as $option) {
        expect($option['short'])->toBe($option['iso'] . ' ' . $option['dial'])
            ->and($option['short'])->toMatch('/^[A-Z]{2} \+[1-9]\d{0,3}$/');
    }
});

it('offers no duplicate country, and a dial code for every one', function () {
    $options = CountryCodes::options();
    $iso = array_column($options, 'iso');

    expect($iso)->toBe(array_unique($iso));
    foreach ($options as $option) {
        expect($option['dial'])->toMatch('/^\+[1-9]\d{0,3}$/');
    }
});

it('matches the longest dial code, so a prefix does not claim the number', function () {
    // +20 is a prefix of nothing here, but +1 prefixes +1… and +7 prefixes +7…; a shortest-first
    // scan would hand every one of those to whichever short code appears first in the list.
    expect(CountryCodes::fromNumber('+201016999700'))->toBe('EG')
        ->and(CountryCodes::fromNumber('+971 4 123 4567'))->toBe('AE')
        ->and(CountryCodes::fromNumber('+966501234567'))->toBe('SA')
        ->and(CountryCodes::fromNumber('+44 20 7946 0958'))->toBe('GB');
});

it('ignores the punctuation people actually type', function () {
    expect(CountryCodes::fromNumber('+20 (101) 699-9700'))->toBe('EG')
        ->and(CountryCodes::fromNumber('+971.4.123.4567'))->toBe('AE');
});

it('returns nothing for a number that carries no country code at all', function () {
    // A local number is not wrong yet — the picker prepends a code on blur. It simply cannot be
    // attributed to a country, and guessing one would put a wrong code on a real phone number.
    expect(CountryCodes::fromNumber('01016999700'))->toBe('')
        ->and(CountryCodes::fromNumber(''))->toBe('')
        ->and(CountryCodes::fromNumber('call me'))->toBe('')
        ->and(CountryCodes::fromNumber('+999123456789'))->toBe('');
});
