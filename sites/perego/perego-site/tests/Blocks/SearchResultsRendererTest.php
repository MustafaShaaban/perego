<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\SearchResultsRenderer;
use PeregoSite\Content\GlobalContent;

beforeEach(function () {
    Functions\when('esc_attr__')->returnArg(1);
    Functions\when('esc_html__')->returnArg(1);
    Functions\when('esc_html')->returnArg(1);
    Functions\when('esc_attr')->returnArg(1);
    Functions\when('esc_url')->returnArg(1);
    Functions\when('home_url')->alias(fn (string $path = '/') => 'http://perego.local' . $path);
});

it('renders the no-query state (breadcrumb + title + form + hint) without running a query', function () {
    // Empty query: the renderer must short-circuit before constructing a WP_Query, so this exercises
    // the breadcrumb + search form path with no WordPress query layer mocked at all.
    Functions\when('get_search_query')->justReturn('');

    $html = (new SearchResultsRenderer(new GlobalContent('en')))->render();

    expect($html)
        ->toContain('class="page-crumb"')                       // breadcrumb present
        ->toContain('class="post-title"')                      // handoff heading contract
        ->toContain('class="search-bar"')                      // handoff form contract
        ->toContain('Check your spelling or use more general keywords.') // the emptyHint
        ->not->toContain('class="blog-grid"')                  // no result grid on a blank query
        ->not->toContain('&mdash; 0 matches found');             // no fabricated result summary
});

it('localizes the no-query state on the AR route', function () {
    Functions\when('get_search_query')->justReturn('');

    $html = (new SearchResultsRenderer(new GlobalContent('ar')))->render();

    expect($html)
        ->toContain('نتائج البحث')                               // AR "Search results" H1
        ->toContain('تحقق من الإملاء أو استخدم كلمات أعمّ.');     // AR emptyHint
});
