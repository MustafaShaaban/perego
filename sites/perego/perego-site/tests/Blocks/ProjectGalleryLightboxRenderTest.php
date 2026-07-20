<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\ProjectGalleryLightboxRenderer;

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('__')->returnArg();
});

function sampleImages(): array
{
    return [
        ['src' => 'https://perego.local/a.jpg', 'thumb' => 'https://perego.local/a-thumb.jpg', 'alt' => 'Frame A'],
        ['src' => 'https://perego.local/b.jpg', 'thumb' => '', 'alt' => 'Frame B'],
    ];
}

function renderGallery(?array $images = null): string
{
    return (new ProjectGalleryLightboxRenderer())->render($images ?? sampleImages(), 'Project gallery');
}

it('renders nothing when there are no images', function () {
    expect(renderGallery([]))->toBe('');
});

it('renders one thumbnail button per image as a site-wide lightbox gallery trigger', function () {
    $html = renderGallery();

    expect(substr_count($html, 'class="work-card reveal"'))->toBe(2)
        ->and($html)->toContain('class="work-masonry"')
        ->and($html)->toContain('data-gallery="https://perego.local/a.jpg,https://perego.local/b.jpg"')
        ->and($html)->toContain('Frame A');
});

it('tells the global dialog which image each thumb represents', function () {
    $html = renderGallery();

    expect($html)->toContain('data-gallery-index="0"')
        ->and($html)->toContain('data-gallery-index="1"');
});

it('renders NO embedded dialog of its own — the page has exactly one lightbox instance', function () {
    $html = renderGallery();

    expect($html)->not->toContain('class="lightbox"')
        ->and($html)->not->toContain('role="dialog"')
        ->and($html)->not->toContain('data-wp-interactive');
});

it('renders the localized gallery heading as the section label', function () {
    expect(renderGallery())->toContain('<h2 id="pjGallery" class="section-title"')
        ->and(renderGallery())->toContain('Project gallery');
});

it('falls back to the full src for a thumbnail when no thumb is set', function () {
    $html = renderGallery();

    // Second image has no thumb → its src is used for the thumbnail.
    expect($html)->toContain('https://perego.local/b.jpg');
});
