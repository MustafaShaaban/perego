<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\Forms\CountryCodes;

it('leads with the studio\'s primary markets rather than an alphabet', function () {
    $iso = array_column(CountryCodes::options(), 'iso');

    // The Gulf leads, because that is the segment being sold to — a visitor in Dubai should not
    // scroll past Afghanistan, and the fallback should favour the audience over the studio's own
    // address (owner decision 2026-07-28).
    expect(array_slice($iso, 0, 3))->toBe(['AE', 'SA', 'EG'])
        ->and($iso[0])->toBe(CountryCodes::DEFAULT_ISO);
});

it('labels each option with the country and its dial code, in the page language', function () {
    $en = CountryCodes::options('en');
    $ar = CountryCodes::options('ar');

    expect($en[0]['label'])->toBe('United Arab Emirates (+971)')
        ->and($ar[0]['label'])->toBe('الإمارات (+971)')
        // The dial code itself never localizes — it is what gets dialled.
        ->and($ar[0]['dial'])->toBe('+971');
});

it('carries a short label for the closed picker, distinct from the list label', function () {
    $en = CountryCodes::options('en');
    $ar = CountryCodes::options('ar');

    // The picker sits in a compact control beside the number; a trigger rendering "United Arab
    // Emirates (+971)" squeezed the input out (client report 2026-07-28). The open list keeps the
    // full name, which is where the room is.
    expect($en[0]['short'])->toBe('AE +971')
        ->and($en[0]['label'])->toBe('United Arab Emirates (+971)')
        // An ISO code and a dial code are what you read off a SIM; localizing them helps nobody dial.
        ->and($ar[0]['short'])->toBe('AE +971');
});

it('carries an example number for the markets it claims to serve, and none where it would be a guess', function () {
    $examples = array_column(CountryCodes::options(), 'example', 'iso');

    // The placeholder follows the picker, so the hint has to be right for the country selected.
    expect($examples['AE'])->toBe('+971 50 123 4567')
        ->and($examples['SA'])->toBe('+966 50 123 4567')
        ->and($examples['EG'])->toBe('+20 100 123 4567');

    // Absent rather than invented: a wrong format teaches worse than no format, and the field falls
    // back to its translated copy where this is empty.
    expect($examples['US'])->toBe('')
        ->and($examples['JP'] ?? '')->toBe('');

    // Every example that IS published must start with its own country's dial code.
    foreach (CountryCodes::options() as $option) {
        if ($option['example'] !== '') {
            expect($option['example'])->toStartWith($option['dial'] . ' ');
        }
    }
});

it('maps timezones only to countries the picker actually offers', function () {
    $zones = CountryCodes::zones();
    $known = array_column(CountryCodes::options(), 'iso');

    // The map is written beside the country list so the two cannot drift; this is the guard that
    // says so. A zone pointing at a country the picker does not list would silently do nothing.
    expect($zones)->not->toBeEmpty();
    foreach ($zones as $zone => $iso) {
        expect($known)->toContain($iso)
            ->and($zone)->toMatch('#^[A-Za-z_]+/[A-Za-z_+\-]+$#');
    }

    expect($zones['Asia/Dubai'])->toBe('AE')
        ->and($zones['Asia/Riyadh'])->toBe('SA')
        ->and($zones['Africa/Cairo'])->toBe('EG');
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
