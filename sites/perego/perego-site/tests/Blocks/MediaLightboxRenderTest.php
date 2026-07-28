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
    // The full set `GlobalContent::lightbox()` supplies. The zoom/full-screen/document labels joined
    // it when the dialog gained those controls (owner, 2026-07-28).
    return [
        'close' => 'Close',
        'prev' => 'Previous',
        'next' => 'Next',
        'zoomIn' => 'Zoom in',
        'zoomOut' => 'Zoom out',
        'zoomReset' => 'Reset zoom',
        'fullscreen' => 'Full screen',
        'openDocument' => 'Open the document',
    ];
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

it('renders the position counter visibly (handoff "3 / 8"), still announced as a live region', function () {
    $html = renderLightbox();

    expect($html)->toContain('class="lightbox__counter"')
        ->and($html)->not->toContain('screen-reader-text')
        ->and($html)->toMatch('/lightbox__counter"[^>]*aria-live="polite"/');
});

/*
 * The zoom controls ship hidden and are unhidden by view.js only for a slide that can use them — an
 * image can be magnified, a video or a document cannot. Rendering them always (rather than injecting
 * them per slide) is what lets the existing focus trap pick them up with no change: `focusableIn()`
 * already selects `button:not([disabled])` and skips anything not painted.
 */
it('renders the zoom controls hidden, and the full-screen toggle visible', function () {
    $html = renderLightbox();

    expect(substr_count($html, 'data-lightbox-zoom='))->toBe(3)
        ->and($html)->toContain('data-lightbox-zoom="zoom-in" aria-label="Zoom in" hidden')
        ->and($html)->toContain('data-lightbox-zoom="zoom-out" aria-label="Zoom out" hidden')
        ->and($html)->toContain('data-lightbox-zoom="zoom-reset" aria-label="Reset zoom" hidden')
        ->and($html)->toContain('data-lightbox-fullscreen aria-pressed="false" aria-label="Full screen"')
        // The toggle itself is never hidden: full screen applies to the whole dialog, so it suits
        // every slide type — including the two the zoom buttons sit out.
        ->and($html)->toMatch('/data-lightbox-fullscreen(?![^>]*\bhidden\b)[^>]*>/');
});

it('carries the document label on the dialog, since view.js builds that link itself', function () {
    expect(renderLightbox())->toContain('data-lightbox-document-label="Open the document"');
});

it('keeps every control inside .lightbox__inner, so the focus trap already covers them', function () {
    $html = renderLightbox();
    $inner = substr($html, (int) strpos($html, 'lightbox__inner'));

    foreach (['data-lightbox-zoom', 'data-lightbox-fullscreen', 'data-lightbox-close', 'data-lightbox-prev'] as $hook) {
        expect($inner)->toContain($hook);
    }
});
