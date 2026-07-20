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
     * @param array{close: string, prev: string, next: string} $strings
     */
    public function render(array $strings): string
    {
        $html = '<div class="lightbox" id="perego-media-lightbox" role="dialog" aria-modal="true" '
            . 'aria-label="' . esc_attr($strings['close']) . '" hidden>';

        $html .= '<div class="lightbox__backdrop" data-lightbox-close></div>';

        $html .= '<div class="lightbox__inner">';
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
}
