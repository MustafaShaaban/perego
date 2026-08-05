<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\ServiceSelectedWorkRenderer;

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('__')->returnArg();
    Functions\when('get_stylesheet_directory_uri')->justReturn('https://perego.local/wp-content/themes/perego-theme');
});

function sampleServiceWork(): array
{
    return [
        ['title' => 'Brand Film', 'thumbUrl' => 'https://perego.local/a.jpg', 'thumbAlt' => 'Brand Film', 'gallerySrcs' => []],
        ['title' => 'Product Teaser', 'icon' => 'gallery', 'thumbUrl' => 'https://perego.local/b.jpg', 'thumbAlt' => 'Product Teaser', 'gallerySrcs' => ['https://perego.local/b.jpg', 'https://perego.local/b2.jpg']],
        ['title' => 'No Media Project', 'thumbUrl' => '', 'thumbAlt' => '', 'gallerySrcs' => []],
    ];
}

/** @return list<array{title: string, thumbUrl: string, thumbAlt: string, gallerySrcs: list<string>}> */
function manyServiceProjects(int $count): array
{
    return array_map(
        static fn (int $i): array => [
            'title' => "Project {$i}",
            'thumbUrl' => "https://perego.local/p{$i}.jpg",
            'thumbAlt' => "Project {$i}",
            'gallerySrcs' => [],
        ],
        range(1, $count),
    );
}

it('renders no section at all when there is no usable project media', function () {
    $html = (new ServiceSelectedWorkRenderer())->render([
        ['title' => 'No Media', 'thumbUrl' => '', 'thumbAlt' => '', 'gallerySrcs' => []],
    ]);

    expect($html)->toBe('');
});

it('renders a work-masonry of real projects, opening the site-wide media lightbox', function () {
    $html = (new ServiceSelectedWorkRenderer())->render(sampleServiceWork());

    expect($html)->toContain('class="portfolio page-section"')
        ->and($html)->toContain('class="work-masonry"')
        ->and($html)->toContain('data-image="https://perego.local/a.jpg"')
        ->and($html)->toContain('data-gallery="https://perego.local/b.jpg,https://perego.local/b2.jpg"')
        ->and($html)->toContain('/assets/images/wavy-corners.webp')
        ->and($html)->toContain('width="2560" height="1440"');
});

it('assigns the handoff\'s designed mosaic placements (m1, m2, …) in project order', function () {
    $html = (new ServiceSelectedWorkRenderer())->render(sampleServiceWork());

    expect($html)->toContain('class="work-card m1 reveal"')
        ->and($html)->toContain('class="work-card m2 reveal"')
        ->and($html)->not->toContain('class="work-card m3 reveal"'); // the no-media project is skipped
});

it('places the non-interactive brand card right after the lead tile', function () {
    $html = (new ServiceSelectedWorkRenderer())->render(sampleServiceWork());

    $brand = '<div class="work-card work-brand" aria-hidden="true">'
        . '<img class="work-brand__logo" src="https://perego.local/wp-content/themes/perego-theme/assets/images/logo-full.png" alt="" /></div>';

    expect($html)->toContain($brand)
        ->and(strpos($html, $brand))->toBeGreaterThan(strpos($html, 'work-card m1 reveal'))
        ->and(strpos($html, $brand))->toBeLessThan(strpos($html, 'work-card m2 reveal'));
});

it('renders a video project as the handoff\'s ▶ play card (data-video + play-btn)', function () {
    $html = (new ServiceSelectedWorkRenderer())->render([
        ['title' => 'Reel', 'icon' => 'play', 'thumbUrl' => 'https://perego.local/a.jpg', 'thumbAlt' => 'Reel', 'gallerySrcs' => [], 'videoUrl' => 'https://www.youtube.com/embed/abc123'],
    ]);

    expect($html)->toContain('data-video="https://www.youtube.com/embed/abc123"')
        ->and($html)->toContain('<span class="play-btn" aria-hidden="true"></span>')
        ->and($html)->not->toContain('work-zoom')
        ->and($html)->not->toContain('work-badge')
        ->and($html)->not->toContain('data-image=');
});

it('lets the video variant win over a gallery — one trigger per card, like the handoff', function () {
    $html = (new ServiceSelectedWorkRenderer())->render([
        ['title' => 'Reel', 'icon' => 'play', 'thumbUrl' => 'https://perego.local/a.jpg', 'thumbAlt' => 'Reel', 'gallerySrcs' => ['https://perego.local/a.jpg', 'https://perego.local/b.jpg'], 'videoUrl' => 'https://www.youtube.com/embed/abc123'],
    ]);

    expect($html)->toContain('data-video=')
        ->and($html)->toContain('play-btn')
        ->and($html)->not->toContain('data-gallery=');
});

/*
 * The affordance is the editor's choice now, not a consequence of the media (owner, 2026-07-28). A
 * video project used to force a ▶ on every grid it appeared in with no way to turn it off; what a
 * tile OPENS and what it ADVERTISES are separate questions and are now separately decided.
 */
it('shows no icon on a video project whose editor chose none, while still opening the video', function () {
    $html = (new ServiceSelectedWorkRenderer())->render([
        ['title' => 'Reel', 'icon' => 'none', 'thumbUrl' => 'https://perego.local/a.jpg', 'thumbAlt' => 'Reel', 'gallerySrcs' => [], 'videoUrl' => 'https://www.youtube.com/embed/abc123'],
    ]);

    expect($html)->toContain('data-video="https://www.youtube.com/embed/abc123"')
        ->and($html)->not->toContain('play-btn');
});

it('shows the gallery badge on a video project when that is what the editor chose', function () {
    $html = (new ServiceSelectedWorkRenderer())->render([
        ['title' => 'Reel', 'icon' => 'gallery', 'thumbUrl' => 'https://perego.local/a.jpg', 'thumbAlt' => 'Reel', 'gallerySrcs' => [], 'videoUrl' => 'https://www.youtube.com/embed/abc123'],
    ], ['galleryBadge' => 'Gallery']);

    expect($html)->toContain('<span class="work-badge">Gallery</span>')
        ->and($html)->not->toContain('play-btn');
});

// An absent icon key (a project not yet migrated) must be inert, never a guessed badge.
it('shows no icon when the project carries no icon at all', function () {
    $html = (new ServiceSelectedWorkRenderer())->render([
        ['title' => 'Reel', 'thumbUrl' => 'https://perego.local/a.jpg', 'thumbAlt' => 'Reel', 'gallerySrcs' => [], 'videoUrl' => 'https://www.youtube.com/embed/abc123'],
    ]);

    expect($html)->not->toContain('play-btn')
        ->and($html)->not->toContain('work-badge');
});

it('marks gallery projects with the handoff\'s Gallery badge', function () {
    $html = (new ServiceSelectedWorkRenderer())->render(sampleServiceWork(), ['galleryBadge' => 'Gallery']);

    expect(substr_count($html, '<span class="work-badge">Gallery</span>'))->toBe(1);
});

it('emits no hover "+" zoom glyph on gallery or image cards (owner review — video ▶ only)', function () {
    // sampleServiceWork() has an image card (data-image) and a gallery card (data-gallery), neither video.
    $html = (new ServiceSelectedWorkRenderer())->render(sampleServiceWork(), ['galleryBadge' => 'Gallery']);

    expect($html)->toContain('data-image=')
        ->and($html)->toContain('data-gallery=')
        ->and($html)->not->toContain('work-zoom');
});

it('has no Load more when every project fits the 15 designed placements', function () {
    $html = (new ServiceSelectedWorkRenderer())->render(manyServiceProjects(15));

    expect($html)->toContain('class="work-card m15 reveal"')
        ->and($html)->not->toContain('id="workMore"')
        ->and($html)->not->toContain('id="loadMore"');
});

it('moves projects beyond the 15 placements into the hidden Load-more grid', function () {
    $html = (new ServiceSelectedWorkRenderer())->render(manyServiceProjects(18), ['loadMore' => 'Load more']);

    expect($html)->toContain('<div class="work-more-grid" id="workMore" hidden>')
        ->and($html)->toContain('id="loadMore"')
        ->and($html)->toContain('>Load more</button>')
        ->and($html)->not->toContain('m16') // placements stop at the designed 15
        ->and(substr_count($html, 'data-image='))->toBe(18);
});

it('has no heading of its own, unlike the archive\'s Selected work section', function () {
    $html = (new ServiceSelectedWorkRenderer())->render(sampleServiceWork());

    expect($html)->not->toContain('<h2');
});
