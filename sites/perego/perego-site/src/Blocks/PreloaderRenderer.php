<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use Corex\Assets\Image;

/**
 * Server-renders the perego/preloader block: a branded overlay shown only on a visitor's first
 * homepage view of a session, cleared ~0.9s after first paint (independent of full asset load),
 * with a hard 2.5s safety timeout, and never shown under `prefers-reduced-motion` (spec 001 FR-009).
 * All of that timing/session/reduced-motion logic lives in view.js — this class only emits the
 * markup, decorative to assistive tech (`aria-hidden`) since it carries no content of its own.
 */
final class PreloaderRenderer
{
    public function render(): string
    {
        $logo = Image::picture('images/logo-full.png', [
            'base' => 'perego-theme',
            'alt' => '',
            'class' => 'preloader__logo',
            'width' => 552,
            'height' => 170,
            'loading' => 'eager',
        ]);

        // The locked handoff preloader structure (stage → three rings + glow + logo, progress bar, and
        // the bilingual wordmark). All styling comes from the authoritative reference stylesheet's
        // `.preloader*` rules; view.js only toggles `is-hidden` on the root (session/timeout/reduced-motion).
        return '<div class="preloader" data-wp-interactive="perego/preloader" '
            . "data-wp-context='" . esc_attr((string) wp_json_encode(['isHidden' => false])) . "' "
            . 'data-wp-class--is-hidden="context.isHidden" '
            . 'data-wp-init="callbacks.init" '
            . 'aria-hidden="true">'
            . '<div class="preloader__stage">'
            . '<span class="preloader__ring preloader__ring--1"></span>'
            . '<span class="preloader__ring preloader__ring--2"></span>'
            . '<span class="preloader__ring preloader__ring--3"></span>'
            . '<span class="preloader__glow"></span>'
            . $logo
            . '</div>'
            . '<div class="preloader__bar" aria-hidden="true"><span></span></div>'
            . '<p class="preloader__word">بيريجو · PEREGO</p>'
            . '</div>';
    }
}
