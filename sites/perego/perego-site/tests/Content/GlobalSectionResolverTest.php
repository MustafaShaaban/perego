<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\Content\GlobalSectionResolver;

/** @return list<array{id:int,role:string,locale:string}> */
function candidates(): array
{
    return [
        ['id' => 12, 'role' => 'header', 'locale' => 'en'],
        ['id' => 13, 'role' => 'header', 'locale' => 'ar'],
        ['id' => 20, 'role' => 'standard-footer', 'locale' => 'en'],
        ['id' => 21, 'role' => 'standard-footer', 'locale' => 'ar'],
    ];
}

it('resolves the record matching the requested role and locale', function () {
    $resolver = new GlobalSectionResolver();

    expect($resolver->resolve('header', 'en', candidates()))->toBe(12)
        ->and($resolver->resolve('header', 'ar', candidates()))->toBe(13)
        ->and($resolver->resolve('standard-footer', 'ar', candidates()))->toBe(21);
});

it('returns null when the role has no record in the requested locale — never mixes languages', function () {
    $resolver = new GlobalSectionResolver();

    // Only an EN header exists; asking for AR must not silently fall back to the EN record.
    $onlyEn = [['id' => 12, 'role' => 'header', 'locale' => 'en']];

    expect($resolver->resolve('header', 'ar', $onlyEn))->toBeNull();
});

it('returns null for a role that has no records at all', function () {
    $resolver = new GlobalSectionResolver();

    expect($resolver->resolve('global-cta', 'en', candidates()))->toBeNull();
});

it('picks the lowest id deterministically when a role+locale is duplicated (singleton stability)', function () {
    $resolver = new GlobalSectionResolver();

    $dupes = [
        ['id' => 99, 'role' => 'header', 'locale' => 'en'],
        ['id' => 40, 'role' => 'header', 'locale' => 'en'],
        ['id' => 77, 'role' => 'header', 'locale' => 'en'],
    ];

    expect($resolver->resolve('header', 'en', $dupes))->toBe(40);
});
