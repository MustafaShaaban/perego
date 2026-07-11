<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

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
        return '<div class="perego-preloader" data-wp-interactive="perego/preloader" '
            . "data-wp-context='" . esc_attr((string) wp_json_encode(['isHidden' => false])) . "' "
            . 'data-wp-class--is-hidden="context.isHidden" '
            . 'data-wp-init="callbacks.init" '
            . 'aria-hidden="true">'
            . '<span class="screen-reader-text">' . esc_html__('Loading…', 'perego-site') . '</span>'
            . '</div>';
    }
}
