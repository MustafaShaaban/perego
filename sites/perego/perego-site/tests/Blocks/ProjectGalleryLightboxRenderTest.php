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
    Functions\when('wp_json_encode')->alias('json_encode');
});

function sampleImages(): array
{
    return [
        ['src' => 'https://perego.local/a.jpg', 'thumb' => 'https://perego.local/a-thumb.jpg', 'alt' => 'Frame A'],
        ['src' => 'https://perego.local/b.jpg', 'thumb' => '', 'alt' => 'Frame B'],
    ];
}

function galleryStrings(): array
{
    return ['sectionLabel' => 'Project gallery', 'close' => 'Close', 'prev' => 'Previous', 'next' => 'Next', 'counter' => '%1$s / %2$s'];
}

function renderGallery(?array $images = null): string
{
    return (new ProjectGalleryLightboxRenderer())->render($images ?? sampleImages(), galleryStrings());
}

it('renders nothing when there are no images', function () {
    expect(renderGallery([]))->toBe('');
});

it('renders one thumbnail button per image, opening the lightbox', function () {
    $html = renderGallery();

    expect(substr_count($html, 'project-gallery__thumb'))->toBe(2)
        ->and($html)->toContain('data-wp-on--click="actions.open"')
        ->and($html)->toContain('Frame A');
});

it('renders the localized gallery heading as the section label', function () {
    expect(renderGallery())->toContain('<h2 id="project-gallery-title"')
        ->and(renderGallery())->toContain('Project gallery');
});

it('falls back to the full src for a thumbnail when no thumb is set', function () {
    $html = renderGallery();

    // Second image has no thumb → its src is used for the thumbnail.
    expect($html)->toContain('https://perego.local/b.jpg');
});

it('renders an accessible dialog with a focus-trap keydown and modal semantics', function () {
    $html = renderGallery();

    expect($html)->toContain('role="dialog"')
        ->and($html)->toContain('aria-modal="true"')
        ->and($html)->toContain('data-wp-on--keydown="actions.onKeydown"')
        ->and($html)->toContain('data-wp-bind--hidden="callbacks.lightboxHidden"');
});

it('wires prev/next/close and binds the image + counter to the store', function () {
    $html = renderGallery();

    expect($html)->toContain('data-wp-on--click="actions.prev"')
        ->and($html)->toContain('data-wp-on--click="actions.next"')
        ->and($html)->toContain('data-wp-on--click="actions.close"')
        ->and($html)->toContain('data-wp-bind--src="state.currentSrc"')
        ->and($html)->toContain('data-wp-text="state.counterLabel"');
});

it('embeds the image srcs + initial state in the interactivity context', function () {
    $html = renderGallery();

    expect($html)->toMatch('/"isOpen":false/')
        ->and($html)->toMatch('/"count":2/')
        ->and($html)->toContain('https:\/\/perego.local\/a.jpg');
});
