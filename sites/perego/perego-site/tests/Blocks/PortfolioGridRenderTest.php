<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\PortfolioGridRenderer;

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_attr__')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('home_url')->alias(fn (string $path = '') => 'https://perego.local' . $path);
    Functions\when('wp_json_encode')->alias('json_encode');
});

function sampleProjects(): array
{
    return [
        ['title' => 'Brand Film', 'url' => '/work/brand-film', 'category' => 'video', 'categoryLabel' => 'Video Editing', 'excerpt' => 'Client: Sample · 2026', 'thumbUrl' => '', 'thumbAlt' => ''],
        ['title' => 'Explainer Series', 'url' => '/work/explainer', 'category' => 'motion', 'categoryLabel' => '2D Motion Graphics', 'excerpt' => 'Client: Sample · 2026', 'thumbUrl' => 'https://perego.local/x.png', 'thumbAlt' => 'Explainer thumb'],
        ['title' => 'Identity System', 'url' => '/work/identity', 'category' => 'design', 'categoryLabel' => 'Graphic Design', 'excerpt' => 'Client: Sample · 2025', 'thumbUrl' => '', 'thumbAlt' => ''],
    ];
}

function renderGrid(?array $projects = null): string
{
    $filters = ['all' => 'All Projects', 'video' => 'Video Editing', 'motion' => '2D Motion Graphics', 'design' => 'Graphic Design', 'web' => 'Website Making'];
    $strings = ['groupLabel' => 'Filter projects by service', 'noResults' => 'No projects in this category yet. Try another filter.'];

    return (new PortfolioGridRenderer())->render($projects ?? sampleProjects(), $filters, $strings);
}

it('renders a filter chip per label with All active by default', function () {
    $html = renderGrid();

    expect(substr_count($html, 'data-filter="'))->toBe(5)
        ->and($html)->toMatch('/data-filter="all"[^>]*aria-pressed="true"/')
        ->and($html)->toContain('data-wp-on--click="actions.setFilter"');
});

it('renders one card per project with its category and excerpt', function () {
    $html = renderGrid();

    expect(substr_count($html, 'class="post-card reveal"'))->toBe(3)
        ->and($html)->toContain('Brand Film')
        ->and($html)->toContain('data-category="video"')
        ->and($html)->toContain('Client: Sample · 2026');
});

it('binds each card hidden state and the no-results message to the store', function () {
    $html = renderGrid();

    expect($html)->toContain('data-wp-bind--hidden="callbacks.cardHidden"')
        ->and($html)->toContain('data-wp-bind--hidden="callbacks.noResultsHidden"');
});

it('records only the categories that actually have projects in the context', function () {
    $html = renderGrid();

    // sample has video/motion/design but no web project
    expect($html)->toMatch('/"present":\["video","motion","design"\]/');
});

it('uses an image when a thumbnail url is present and a placeholder otherwise', function () {
    $html = renderGrid();

    expect($html)->toContain('src="https://perego.local/x.png"')
        ->and($html)->toContain('post-card__media-placeholder');
});

it('renders an empty grid with no cards when there are no projects', function () {
    $html = renderGrid([]);

    expect(substr_count($html, 'class="post-card reveal"'))->toBe(0)
        ->and($html)->toContain('id="portfolioEmpty"')
        ->and($html)->toMatch('/"present":\[\]/');
});

it('renders an optional heading + intro when provided', function () {
    $filters = ['all' => 'All Projects', 'video' => 'Video Editing'];
    $strings = ['groupLabel' => 'Filter', 'noResults' => 'None', 'heading' => 'Our Work', 'intro' => 'A selection.'];

    $html = (new PortfolioGridRenderer())->render(sampleProjects(), $filters, $strings);

    expect($html)->toMatch('/<h1 class="post-title"[^>]*>Our Work<\/h1>/')
        ->and($html)->toContain('A selection.');
});

it('omits the heading when none is provided', function () {
    expect(renderGrid())->not->toContain('class="post-title"');
});

it('renders a Home breadcrumb and the demo-content note when provided', function () {
    $filters = ['all' => 'All Projects'];
    $strings = [
        'groupLabel' => 'Filter',
        'noResults' => 'None',
        'heading' => 'Our Work',
        'intro' => 'A selection.',
        'demoNote' => "Example projects shown below.",
        'uiHome' => 'Home',
    ];

    $html = (new PortfolioGridRenderer())->render(sampleProjects(), $filters, $strings);

    expect($html)->toContain('class="page-crumb"')
        ->and($html)->toMatch('/<a href="[^"]*\/">Home<\/a>/')
        ->and($html)->toContain('aria-current="page">Our Work')
        ->and($html)->toContain('class="section-lead"')
        ->and($html)->toContain('Example projects shown below.');
});

it('omits the breadcrumb when uiHome is not provided', function () {
    expect(renderGrid())->not->toContain('page-crumb');
});
