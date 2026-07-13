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
