<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

/**
 * Server-renders the perego/home-about-bg block (spec 004 T012): the homepage About section's
 * full-bleed hooded-figure background image, absolutely positioned behind the editor-canvas panel
 * content that follows it as a sibling in front-page.html. Ported from the handoff prototype's
 * `.home-about__bg`. Static (no locale dependency — purely decorative chrome, not editorial content),
 * no view script.
 */
final class HomeAboutBgRenderer
{
    public function render(): string
    {
        return '<div class="home-about__bg" aria-hidden="true">'
            . '<img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/about-hooded.png') . '" alt="" loading="lazy" />'
            . '</div>';
    }
}
