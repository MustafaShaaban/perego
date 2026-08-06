<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\SearchResultsRenderer;
use PeregoSite\Content\GlobalContent;

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_url')->returnArg();
    // The translating pair the renderer actually calls (2 × esc_attr__, 4 × esc_html__). Stubbed
    // here because this file has to stand on its own: Brain Monkey's `when()` defines the function
    // for the whole process, so these were being supplied by whichever sibling test file happened
    // to run first. The suite passed as a whole and this file failed alone — a green that depended
    // on run order, and would have broken the moment the suite was sharded or this file run singly.
    Functions\when('esc_html__')->returnArg();
    Functions\when('esc_attr__')->returnArg();
    Functions\when('home_url')->alias(fn (string $path = '') => 'https://perego.local' . $path);
    // Empty search query — exercises the no-query branch (no WP_Query needed).
    Functions\when('get_search_query')->justReturn('');
});

function renderSearch(string $locale = 'en'): string
{
    return (new SearchResultsRenderer(new GlobalContent($locale)))->render();
}

it('renders the search H1, a role=search form, and the hint when there is no query', function () {
    $html = renderSearch();

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('Search results')
        ->and($html)->toContain('role="search"')
        ->and($html)->toContain('name="s"')
        ->and($html)->toContain('type="submit"')
        ->and($html)->toContain('Check your spelling');
});

it('localizes the empty search page into Arabic', function () {
    $html = renderSearch('ar');

    expect($html)->toContain('نتائج البحث')
        ->and($html)->toContain('ابحث في الموقع')
        ->and($html)->toContain('تحقق من الإملاء');
});
