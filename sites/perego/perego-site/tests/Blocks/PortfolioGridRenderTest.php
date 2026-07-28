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
    Functions\when('wp_parse_url')->alias(static fn (string $u, int $c = -1) => parse_url($u, $c));
});

/**
 * A grid card carrying every key the renderer reads, so each test spells out only what it exercises.
 * Mirrors `ProjectRepository::toGridCard()`'s return shape.
 */
function projectCard(array $overrides = []): array
{
    return array_merge([
        'title' => 'Sample Project',
        'url' => '/work/sample',
        'category' => 'design',
        'categoryLabel' => 'Graphic Design',
        'excerpt' => '',
        'thumbUrl' => '',
        'thumbAlt' => '',
        'gallerySrcs' => [],
        'videoUrl' => '',
        'logoUrl' => '',
        'logoAlt' => '',
        'siteUrl' => '',
        'role' => '',
    ], $overrides);
}

/**
 * One project per lightbox trigger branch: a video, a real multi-image gallery, and an image-only card
 * (which here has no thumb and no gallery either, so it exercises the no-media case too).
 */
function sampleProjects(): array
{
    return [
        projectCard(['title' => 'Brand Film', 'url' => '/work/brand-film', 'category' => 'video', 'categoryLabel' => 'Video Editing', 'excerpt' => 'Client: Sample · 2026', 'videoUrl' => 'https://perego.local/reel.mp4']),
        projectCard(['title' => 'Explainer Series', 'url' => '/work/explainer', 'category' => 'motion', 'categoryLabel' => '2D Motion Graphics', 'excerpt' => 'Client: Sample · 2026', 'thumbUrl' => 'https://perego.local/x.png', 'thumbAlt' => 'Explainer thumb', 'gallerySrcs' => ['https://perego.local/a.png', 'https://perego.local/b.png']]),
        projectCard(['title' => 'Identity System', 'url' => '/work/identity', 'excerpt' => 'Client: Sample · 2025']),
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
        projectCard(['title' => 'Mixed', 'category' => 'video', 'thumbUrl' => 'https://perego.local/t.png', 'thumbAlt' => 'T', 'gallerySrcs' => ['https://perego.local/1.png', 'https://perego.local/2.png'], 'videoUrl' => 'https://perego.local/reel.mp4']),
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

it('renders a web project with a logo as a link to the live site, not a lightbox trigger', function () {
    $html = renderGrid([
        projectCard([
            'title' => 'Aurora Retail',
            'category' => 'web',
            'categoryLabel' => 'Website Making',
            'thumbUrl' => 'https://perego.local/screenshot.png',
            'thumbAlt' => 'Screenshot',
            'logoUrl' => 'https://perego.local/aurora-logo.png',
            'logoAlt' => 'Aurora Retail logo',
            'siteUrl' => 'https://aurora-retail.com',
        ]),
    ]);

    expect($html)->toContain('<a class="post-card reveal post-card--logo" href="https://aurora-retail.com" target="_blank" rel="noopener"')
        ->and($html)->toContain('aria-label="Visit Aurora Retail"')
        ->and($html)->toContain('data-category="web"')
        // The logo replaces the screenshot, and the card no longer opens the lightbox.
        ->and($html)->toContain('src="https://perego.local/aurora-logo.png"')
        ->and($html)->toContain('alt="Aurora Retail logo"')
        ->and($html)->not->toContain('screenshot.png')
        ->and($html)->not->toContain('data-image')
        // Scoped to the card: the filter chips and pager are <button>s of their own.
        ->and($html)->not->toContain('<button type="button" class="post-card');
});

it('renders a logo card with no live site as inert markup rather than a focusable dead control', function () {
    $html = renderGrid([
        projectCard([
            'title' => 'Unlaunched',
            'category' => 'web',
            'categoryLabel' => 'Website Making',
            'logoUrl' => 'https://perego.local/logo.png',
            'logoAlt' => 'Unlaunched logo',
        ]),
    ]);

    // Assert on the card itself — the surrounding filter group carries <button>s and an aria-label.
    $card = substr($html, (int) strpos($html, '<article class="post-card'));

    expect($html)->toContain('<article class="post-card reveal post-card--logo" data-category="web">')
        ->and($html)->not->toContain('<button type="button" class="post-card')
        ->and($html)->not->toContain('<a class="post-card')
        ->and($card)->not->toContain('aria-label')
        ->and($card)->not->toContain('href=');
});

it('gives a web project awaiting its logo a name plate, not a screenshot', function () {
    $html = renderGrid([
        projectCard([
            'title' => 'No Logo Yet',
            'category' => 'web',
            'categoryLabel' => 'Website Making',
            'thumbUrl' => 'https://perego.local/screenshot.png',
            'thumbAlt' => 'Screenshot',
            'siteUrl' => 'https://example.com/path',
        ]),
    ]);

    // Falling back to the screenshot put one photograph in a wall of brand marks, which reads as a
    // mistake. The plate keeps the card on-brand and still links to the live site.
    expect($html)->toContain('post-card--logo')
        ->and($html)->toContain('post-card--plate')
        ->and($html)->toContain('<span class="post-card__plate-name">No Logo Yet</span>')
        ->and($html)->toContain('<span class="post-card__plate-host">example.com</span>')
        ->and($html)->toContain('href="https://example.com/path"')
        ->and($html)->not->toContain('screenshot.png');
});

it('strips the www prefix from the host shown on a name plate', function () {
    $html = renderGrid([
        projectCard(['title' => 'WWW Site', 'category' => 'web', 'siteUrl' => 'https://www.example.com/'])
    ]);

    expect($html)->toContain('>example.com<')
        ->and($html)->not->toContain('>www.example.com<');
});

it('shows the contribution role in place of the excerpt when one is set', function () {
    $html = renderGrid([
        projectCard([
            'title' => 'e& / Etisalat UAE',
            'category' => 'web',
            'excerpt' => 'Some generic excerpt',
            'siteUrl' => 'https://www.eand.ae/',
            'role' => 'Framework upgrade participation',
        ]),
    ]);

    // A portfolio has to say when the work was a contribution rather than a build the studio owned.
    expect($html)->toContain('<p class="post-card__role">Framework upgrade participation</p>')
        ->and($html)->not->toContain('Some generic excerpt');
});

it('gives a card with no media at all no trigger, rather than opening an empty lightbox', function () {
    $html = renderGrid([projectCard(['title' => 'No Media'])]);

    expect($html)->toContain('class="post-card reveal"')
        ->and($html)->not->toContain('data-image')
        ->and($html)->not->toContain('data-gallery')
        ->and($html)->not->toContain('data-video');
});

it('falls back to the single featured image when a project has one image and no video', function () {
    $html = renderGrid([
        projectCard(['title' => 'One Shot', 'thumbUrl' => 'https://perego.local/shot.png', 'thumbAlt' => 'Shot']),
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
        $projects[] = projectCard([
            'title' => 'Project ' . $i,
            'url' => '/work/p-' . $i,
            'category' => 'video',
            'categoryLabel' => 'Video Editing',
            'excerpt' => 'Client: Sample · 2026',
        ]);
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
