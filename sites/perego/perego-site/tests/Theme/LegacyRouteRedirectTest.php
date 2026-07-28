<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\Theme\LegacyRouteRedirect;
use PeregoSite\Theme\SiteRoutes;

it('sends the old contact path to the start-a-project page', function () {
    expect((new LegacyRouteRedirect())->destinationFor('/contact'))->toBe(SiteRoutes::START_PROJECT);
});

it('matches the old path with or without a trailing slash, and case-insensitively', function () {
    $moved = new LegacyRouteRedirect();

    expect($moved->destinationFor('/contact/'))->toBe(SiteRoutes::START_PROJECT)
        ->and($moved->destinationFor('/Contact'))->toBe(SiteRoutes::START_PROJECT);
});

it('redirects the Arabic translation to the Arabic page, not the English one', function () {
    expect((new LegacyRouteRedirect())->destinationFor('/ar/contact-2'))->toBe('/ar/start-a-project-2');
});

it('ignores a query string when matching, so ?service= links still resolve', function () {
    // The caller re-attaches the query; matching must not be defeated by it.
    expect((new LegacyRouteRedirect())->destinationFor('/contact?service=video-editing'))
        ->toBe(SiteRoutes::START_PROJECT);
});

it('leaves every other path alone, including the new one', function () {
    $moved = new LegacyRouteRedirect();

    expect($moved->destinationFor(SiteRoutes::START_PROJECT))->toBeNull()
        ->and($moved->destinationFor('/'))->toBeNull()
        ->and($moved->destinationFor('/journal'))->toBeNull()
        // Guards against a prefix match swallowing an unrelated route.
        ->and($moved->destinationFor('/contact-us-old'))->toBeNull();
});
