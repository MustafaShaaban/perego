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
    Functions\when('__')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('home_url')->alias(fn (string $path = '') => 'https://perego.local' . $path);
    Functions\when('wp_json_encode')->alias('json_encode');
});

/**
 * One project per lightbox trigger branch: a video, a real multi-image gallery, and an image-only card
 * (which here has no thumb and no gallery either, so it exercises the no-media case too).
 */
function sampleProjects(): array
{
    return [
        ['title' => 'Brand Film', 'url' => '/work/brand-film', 'category' => 'video', 'categoryLabel' => 'Video Editing', 'excerpt' => 'Client: Sample · 2026', 'thumbUrl' => '', 'thumbAlt' => '', 'gallerySrcs' => [], 'videoUrl' => 'https://perego.local/reel.mp4'],
        ['title' => 'Explainer Series', 'url' => '/work/explainer', 'category' => 'motion', 'categoryLabel' => '2D Motion Graphics', 'excerpt' => 'Client: Sample · 2026', 'thumbUrl' => 'https://perego.local/x.png', 'thumbAlt' => 'Explainer thumb', 'gallerySrcs' => ['https://perego.local/a.png', 'https://perego.local/b.png'], 'videoUrl' => ''],
        ['title' => 'Identity System', 'url' => '/work/identity', 'category' => 'design', 'categoryLabel' => 'Graphic Design', 'excerpt' => 'Client: Sample · 2025', 'thumbUrl' => '', 'thumbAlt' => '', 'gallerySrcs' => [], 'videoUrl' => ''],
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
        ->and($html)->toContain('class="web-filter portfolio-filter is-active"');
});

it('renders one card per project with its category and excerpt', function () {
    $html = renderGrid();

    expect(substr_count($html, 'class="post-card reveal"'))->toBe(3)
        ->and($html)->toContain('Brand Film')
        ->and($html)->toContain('data-category="video"')
        ->and($html)->toContain('Client: Sample · 2026');
});

it('combines a gallery and a video into one mixed lightbox gallery', function () {
    $html = renderGrid([
        ['title' => 'Mixed', 'url' => '/work/mixed', 'category' => 'video', 'categoryLabel' => 'Video Editing', 'excerpt' => '', 'thumbUrl' => 'https://perego.local/t.png', 'thumbAlt' => 'T', 'gallerySrcs' => ['https://perego.local/1.png', 'https://perego.local/2.png'], 'videoUrl' => 'https://perego.local/reel.mp4'],
    ]);

    // view.js picks a renderer per slide, so images and a video can share one gallery.
    expect($html)->toContain('data-gallery="https://perego.local/1.png,https://perego.local/2.png,https://perego.local/reel.mp4"')
        ->and($html)->not->toContain('data-video=');
});

it('opens each card in the lightbox instead of navigating to a project page', function () {
    $html = renderGrid();

    // A video alone wins, then a real multi-image gallery, then the single image.
    expect($html)->toContain('data-video="https://perego.local/reel.mp4"')
        ->and($html)->toContain('data-gallery="https://perego.local/a.png,https://perego.local/b.png"')
        // Cards are buttons now — no card should link to a single project page.
        ->and($html)->not->toMatch('/<a class="post-card/')
        ->and($html)->not->toContain('/work/brand-film')
        ->and(substr_count($html, '<button type="button" class="post-card reveal"'))->toBe(3)
        ->and($html)->toContain('aria-label="Open Brand Film"');
});

it('gives a card with no media at all no trigger, rather than opening an empty lightbox', function () {
    $html = renderGrid([
        ['title' => 'No Media', 'url' => '/work/none', 'category' => 'design', 'categoryLabel' => 'Graphic Design', 'excerpt' => '', 'thumbUrl' => '', 'thumbAlt' => '', 'gallerySrcs' => [], 'videoUrl' => ''],
    ]);

    expect($html)->toContain('class="post-card reveal"')
        ->and($html)->not->toContain('data-image')
        ->and($html)->not->toContain('data-gallery')
        ->and($html)->not->toContain('data-video');
});

it('falls back to the single featured image when a project has one image and no video', function () {
    $html = renderGrid([
        ['title' => 'One Shot', 'url' => '/work/one', 'category' => 'design', 'categoryLabel' => 'Graphic Design', 'excerpt' => '', 'thumbUrl' => 'https://perego.local/shot.png', 'thumbAlt' => 'Shot', 'gallerySrcs' => [], 'videoUrl' => ''],
    ]);

    expect($html)->toContain('data-image="https://perego.local/shot.png"')
        ->and($html)->not->toContain('data-gallery');
});

it('exposes the per-page size on the grid and a no-results message for view.js', function () {
    $html = renderGrid();

    expect($html)->toContain('id="portfolioGrid" data-per-page="9"')
        ->and($html)->toContain('id="portfolioEmpty"')
        ->and($html)->toContain('hidden');
});

it('renders plain server markup with no Interactivity API directives', function () {
    // The filter/pagination is plain-DOM view.js (like service-selected-work); the block must not
    // emit data-wp-* directives, which crashed hydration and wiped the chips on WP 7.0.2.
    expect(renderGrid())->not->toContain('data-wp-');
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
        ->and($html)->not->toContain('class="pagination"');
});

function manyProjects(int $count): array
{
    $projects = [];
    for ($i = 0; $i < $count; $i++) {
        $projects[] = [
            'title' => 'Project ' . $i,
            'url' => '/work/p-' . $i,
            'category' => 'video',
            'categoryLabel' => 'Video Editing',
            'excerpt' => 'Client: Sample · 2026',
            'thumbUrl' => '',
            'thumbAlt' => '',
            'gallerySrcs' => [],
            'videoUrl' => '',
        ];
    }

    return $projects;
}

it('renders a numbered pager (prev, one button per page, next) when projects exceed the per-page cap', function () {
    $filters = ['all' => 'All Projects', 'video' => 'Video Editing'];
    $strings = ['groupLabel' => 'Filter', 'noResults' => 'None'];

    // 20 projects at 9/page → 3 pages.
    $html = (new PortfolioGridRenderer())->render(manyProjects(20), $filters, $strings);

    // Hidden until view.js runs (progressive enhancement), one button per page, prev/next controls.
    expect($html)->toContain('<nav class="pagination" hidden')
        ->and($html)->toContain('class="pagination__prev"')
        ->and($html)->toContain('class="pagination__next"')
        ->and(substr_count($html, 'data-page="'))->toBe(3)
        ->and($html)->toContain('data-page="3"')
        ->and($html)->not->toContain('data-page="4"');
});

it('omits the pager when all projects fit on a single page', function () {
    // sampleProjects has 3 (≤ 9/page).
    expect(renderGrid())->not->toContain('class="pagination"');
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

it('renders the closing "have a project in mind" CTA when cta strings are provided', function () {
    $filters = ['all' => 'All Projects'];
    $strings = [
        'groupLabel' => 'Filter',
        'noResults' => 'None',
        'ctaTitle' => 'Have a project in mind?',
        'ctaBody' => "Tell us what you're working on and we'll help you shape the plan.",
        'ctaButton' => 'Start a Project',
    ];

    $html = (new PortfolioGridRenderer())->render(sampleProjects(), $filters, $strings);

    expect($html)->toContain('id="pfCta"')
        ->and($html)->toContain('Have a project in mind?')
        ->and($html)->toContain("Tell us what you're working on and we'll help you shape the plan.")
        ->and($html)->toMatch('/<a class="btn btn--accent" href="[^"]*\/start-a-project">Start a Project<\/a>/');
});

it('omits the CTA section entirely when no cta title is provided', function () {
    expect(renderGrid())->not->toContain('id="pfCta"');
});
