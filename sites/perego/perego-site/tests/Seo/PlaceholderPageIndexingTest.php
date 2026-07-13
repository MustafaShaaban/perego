<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Seo\PlaceholderPageIndexing;

// WP_Post double comes from tests/bootstrap.php (shared).

it('adds noindex,nofollow when the WordPress sample page is the queried page', function () {
    Functions\when('is_page')->alias(fn ($slug) => $slug === 'sample-page');

    $robots = (new PlaceholderPageIndexing())->noindexPlaceholder(['max-image-preview' => 'large']);

    expect($robots)
        ->toHaveKey('noindex', true)
        ->toHaveKey('nofollow', true)
        ->toHaveKey('max-image-preview', 'large'); // existing directives preserved
});

it('leaves robots untouched on any other page', function () {
    Functions\when('is_page')->justReturn(false);

    $robots = (new PlaceholderPageIndexing())->noindexPlaceholder(['max-image-preview' => 'large']);

    expect($robots)
        ->not->toHaveKey('noindex')
        ->toHaveKey('max-image-preview', 'large');
});

it('excludes the sample page id from the pages sitemap query', function () {
    $page = new WP_Post();
    $page->ID = 2;
    Functions\when('get_page_by_path')->justReturn($page);

    $args = (new PlaceholderPageIndexing())->excludeFromSitemap([], 'page');

    expect($args['post__not_in'])->toContain(2);
});

it('appends to any existing post__not_in rather than clobbering it', function () {
    $page = new WP_Post();
    $page->ID = 2;
    Functions\when('get_page_by_path')->justReturn($page);

    $args = (new PlaceholderPageIndexing())->excludeFromSitemap(['post__not_in' => [99]], 'page');

    expect($args['post__not_in'])->toContain(99)->toContain(2);
});

it('does not touch the query for non-page post types', function () {
    Functions\when('get_page_by_path')->justReturn(null);

    $args = (new PlaceholderPageIndexing())->excludeFromSitemap([], 'post');

    expect($args)->not->toHaveKey('post__not_in');
});

it('leaves the query unchanged when the sample page does not exist', function () {
    Functions\when('get_page_by_path')->justReturn(null);

    $args = (new PlaceholderPageIndexing())->excludeFromSitemap([], 'page');

    expect($args)->not->toHaveKey('post__not_in');
});
