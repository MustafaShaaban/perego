<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

/**
 * Server-renders the perego-theme/media-lightbox block: the site-wide accessible dialog for any
 * [data-image]/[data-video]/[data-gallery] trigger. Ported from the handoff's main.js lightbox IIFE
 * (image/video/embed detection, prev/next, dots) with the accessible dialog contract already proven
 * by ProjectGalleryLightboxRenderer (role="dialog", focus trap, Escape/backdrop close, focus
 * restoration, scroll lock). Renders once, in the shared footer template part, so any block anywhere
 * on the page can open it without registering its own dialog.
 */
final class MediaLightboxRenderer
{
    /**
     * @param array{close: string, prev: string, next: string, zoomIn: string, zoomOut: string, zoomReset: string, fullscreen: string, openDocument: string} $strings
     */
    public function render(array $strings): string
    {
        // The document fallback link is built by view.js when a PDF slide opens, so its label has to
        // reach the client. It rides on the dialog rather than through a script-localization handle:
        // this block is server-rendered once per page and view.js is a plain view script with no
        // enqueued data of its own.
        $html = '<div class="lightbox" id="perego-media-lightbox" role="dialog" aria-modal="true" '
            . 'aria-label="' . esc_attr($strings['close']) . '" '
            . 'data-lightbox-document-label="' . esc_attr($strings['openDocument']) . '" hidden>';

        $html .= '<div class="lightbox__backdrop" data-lightbox-close></div>';

        $html .= '<div class="lightbox__inner">';
        $html .= $this->tools($strings);
        $html .= '<button type="button" class="lightbox__close" aria-label="' . esc_attr($strings['close']) . '" data-lightbox-close>&times;</button>';
        $html .= '<button type="button" class="lightbox__nav lightbox__nav--prev" aria-label="' . esc_attr($strings['prev']) . '" data-lightbox-prev hidden>&#8249;</button>';
        $html .= '<div class="lightbox__frame"></div>';
        $html .= '<button type="button" class="lightbox__nav lightbox__nav--next" aria-label="' . esc_attr($strings['next']) . '" data-lightbox-next hidden>&#8250;</button>';
        $html .= '<div class="lightbox__dots" data-lightbox-dots></div>';
        // Visible "n / total" position indicator (handoff GlobalMediaLightbox contract §3), doubling
        // as the live region announcing gallery movement.
        $html .= '<p class="lightbox__counter" data-lightbox-counter role="status" aria-live="polite"></p>';
        $html .= '</div>'; // .lightbox__inner

        $html .= '</div>'; // .lightbox

        return $html;
    }

    /**
     * The zoom and full-screen controls (owner, 2026-07-28).
     *
     * Rendered always and hidden by `view.js` when the current slide cannot use them — an image can be
     * zoomed, a video or a document cannot, and the browser's own PDF viewer brings its own zoom. They
     * are real `<button>`s inside `.lightbox__inner`, so the existing focus trap picks them up with no
     * change: `focusableIn()` already selects `button:not([disabled])` and skips anything not painted.
     *
     * Full screen applies to the whole dialog rather than the image, so a document and a video benefit
     * from it too, and it composes with zoom rather than competing with it.
     *
     * @param array{zoomIn: string, zoomOut: string, zoomReset: string, fullscreen: string} $strings
     */
    private function tools(array $strings): string
    {
        $html = '<div class="lightbox__tools" data-lightbox-tools>';

        foreach ([
            'zoom-out' => ['action' => 'zoom-out', 'label' => $strings['zoomOut'], 'glyph' => '&minus;'],
            'zoom-reset' => ['action' => 'zoom-reset', 'label' => $strings['zoomReset'], 'glyph' => '&#9673;'],
            'zoom-in' => ['action' => 'zoom-in', 'label' => $strings['zoomIn'], 'glyph' => '&plus;'],
        ] as $tool) {
            $html .= '<button type="button" class="lightbox__tool lightbox__tool--zoom" '
                . 'data-lightbox-zoom="' . esc_attr($tool['action']) . '" '
                . 'aria-label="' . esc_attr($tool['label']) . '" hidden>' . $tool['glyph'] . '</button>';
        }

        // aria-pressed, not a label swap: the control is one toggle whose state assistive technology
        // reads, and view.js keeps it in step with `fullscreenchange` so leaving full screen by Escape
        // or by the browser's own chrome never leaves the button lying.
        $html .= '<button type="button" class="lightbox__tool lightbox__tool--fullscreen" '
            . 'data-lightbox-fullscreen aria-pressed="false" '
            . 'aria-label="' . esc_attr($strings['fullscreen']) . '">&#9974;</button>';

        $html .= '</div>';

        return $html;
    }
}
