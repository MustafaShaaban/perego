<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\Content\ServicePortfolioSelection;

function servicePortfolioPost(int $id): WP_Post
{
    $post = new WP_Post();
    $post->ID = $id;

    return $post;
}

function servicePortfolioIds(array $posts): array
{
    return array_map(static fn (WP_Post $post): int => $post->ID, $posts);
}

it('retains the automatic category result order by default', function () {
    $result = (new ServicePortfolioSelection())->resolve(
        [servicePortfolioPost(1), servicePortfolioPost(2)],
        [servicePortfolioPost(9)],
        'automatic',
    );

    expect(servicePortfolioIds($result))->toBe([1, 2]);
});

it('uses the explicit Project order in manual mode', function () {
    $result = (new ServicePortfolioSelection())->resolve(
        [servicePortfolioPost(1), servicePortfolioPost(2)],
        [servicePortfolioPost(9), servicePortfolioPost(7)],
        'manual',
    );

    expect(servicePortfolioIds($result))->toBe([9, 7]);
});

it('puts manual Projects before the remaining automatic category results in hybrid mode', function () {
    $result = (new ServicePortfolioSelection())->resolve(
        [servicePortfolioPost(1), servicePortfolioPost(2), servicePortfolioPost(3)],
        [servicePortfolioPost(3), servicePortfolioPost(9)],
        'hybrid',
        [2],
    );

    expect(servicePortfolioIds($result))->toBe([3, 9, 1]);
});

it('normalizes an unsupported source to automatic and applies exclusions', function () {
    $result = (new ServicePortfolioSelection())->resolve(
        [servicePortfolioPost(1), servicePortfolioPost(2)],
        [servicePortfolioPost(9)],
        'unsupported',
        ['2'],
    );

    expect(servicePortfolioIds($result))->toBe([1]);
});
