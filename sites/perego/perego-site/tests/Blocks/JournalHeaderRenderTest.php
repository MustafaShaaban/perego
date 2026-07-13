<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\JournalHeaderRenderer;
use PeregoSite\Content\GlobalContent;

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_attr__')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('home_url')->alias(fn (string $path = '') => 'https://perego.local' . $path);
});

it('renders the journal H1 and lead in English', function () {
    $html = (new JournalHeaderRenderer(new GlobalContent('en')))->render();

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('The Perego Journal')
        ->and($html)->toContain('from the studio floor');
});

it('renders a Home breadcrumb', function () {
    $html = (new JournalHeaderRenderer(new GlobalContent('en')))->render();

    expect($html)->toContain('class="page-crumb"')
        ->and($html)->toMatch('/<a href="[^"]*\/">Home<\/a>/')
        ->and($html)->toContain('aria-current="page">The Perego Journal');
});

it('localizes the journal header into Arabic', function () {
    $html = (new JournalHeaderRenderer(new GlobalContent('ar')))->render();

    expect($html)->toContain('مدونة بيريجو');
});
