<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\Content\ClientsContent;

it('returns the English corporate and individual section copy', function () {
    $content = new ClientsContent('en');

    expect($content->get('corporateTitle'))->toBe('Corporate Clients')
        ->and($content->get('individualTitle'))->toBe('Individual Clients')
        ->and($content->get('sectionLabel'))->toBe('Clients')
        ->and($content->get('corporateSubtitle'))->not->toBe('');
});

it('localizes the clients copy into Arabic', function () {
    $content = new ClientsContent('ar');

    expect($content->get('corporateTitle'))->toBe('عملاء الشركات')
        ->and($content->get('individualTitle'))->toBe('عملاء أفراد')
        ->and($content->get('sectionLabel'))->toBe('العملاء');
});

it('falls back to English for an unknown locale', function () {
    $content = new ClientsContent('fr');

    expect($content->get('corporateTitle'))->toBe('Corporate Clients')
        ->and($content->all())->toHaveKey('individualSubtitle');
});

it('returns an empty string for an unknown key rather than erroring', function () {
    expect((new ClientsContent('en'))->get('nope'))->toBe('');
});
