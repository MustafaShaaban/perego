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
 *
 * Uses `about-hooded-wide.webp`, the wider "zoomed out" framing the client supplied on 2026-07-26 —
 * deliberately NOT the original `about-hooded.png`, which the Contact page hero (`page-contact.html`)
 * still shares. Overwriting that file would have silently restyled Contact too.
 *
 * Sized 3200x1190 from a 2850x1060 source, which is deliberate: `object-fit: cover` on this section
 * renders the image ~3098px wide at a 1440 viewport and ~3180px at 1920 (the section is taller than
 * the viewport, so height drives the scale). Anything narrower is magnified by the browser before it
 * reaches the screen. WebP q95 because the art is dark with smooth gradients — the worst case for JPEG
 * blocking. This asset bypasses `npm run images`: that script does not resize and recompresses at its
 * own quality, which is exactly what produced the banding this replaces.
 */
final class HomeAboutBgRenderer
{
    public function render(): string
    {
        return '<div class="home-about__bg" aria-hidden="true">'
            . '<img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/about-hooded-wide.webp') . '" alt="" loading="lazy" />'
            . '</div>';
    }
}
