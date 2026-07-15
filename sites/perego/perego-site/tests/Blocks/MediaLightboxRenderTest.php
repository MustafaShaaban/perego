<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\MediaLightboxRenderer;

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_attr')->returnArg();
});

function lightboxStrings(): array
{
    return ['close' => 'Close', 'prev' => 'Previous', 'next' => 'Next'];
}

function renderLightbox(): string
{
    return (new MediaLightboxRenderer())->render(lightboxStrings());
}

it('renders a single hidden dialog with modal semantics', function () {
    $html = renderLightbox();

    expect($html)->toContain('id="perego-media-lightbox"')
        ->and($html)->toContain('role="dialog"')
        ->and($html)->toContain('aria-modal="true"')
        ->and($html)->toContain('hidden')
        ->and(substr_count($html, 'class="lightbox"'))->toBe(1);
});

it('wires the close/prev/next controls and the backdrop with data hooks, not Interactivity API directives', function () {
    $html = renderLightbox();

    expect(substr_count($html, 'data-lightbox-close'))->toBe(2) // backdrop + close button
        ->and($html)->toContain('data-lightbox-prev')
        ->and($html)->toContain('data-lightbox-next')
        ->and($html)->toContain('data-lightbox-dots')
        ->and($html)->toContain('data-lightbox-counter')
        ->and($html)->not->toContain('data-wp-interactive');
});

it('localizes the close/prev/next labels', function () {
    $html = renderLightbox();

    expect($html)->toContain('aria-label="Close"')
        ->and($html)->toContain('aria-label="Previous"')
        ->and($html)->toContain('aria-label="Next"');
});

it('starts with prev/next hidden until a gallery with more than one item opens', function () {
    $html = renderLightbox();

    expect($html)->toMatch('/lightbox__nav--prev"[^>]*hidden/')
        ->and($html)->toMatch('/lightbox__nav--next"[^>]*hidden/');
});
