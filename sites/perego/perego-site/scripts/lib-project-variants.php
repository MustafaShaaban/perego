<?php

/**
 * Shared demo-project variant contract (spec 020 round 6) — used by `seed-projects.php`,
 * `seed-project-galleries.php`, and `migrate-project-video-meta.php` so the three never disagree
 * about which demo project renders as which handoff work-card variant. A plain include with no
 * top-level side effects: safe to `require` from any script.
 *
 * The handoff masonry (service-video-editing.html et al.) distributes its 23 cards per service as:
 * mosaic m1–m15 = video ▶ everywhere except m3 (gallery) and m8 (single image); the 8-card
 * "Load more" overflow = video except its 3rd and 6th cards (single images) — absolute positions
 * 18 and 21.
 *
 * @package PeregoSite
 */

declare(strict_types=1);

if (! function_exists('peregoProjectVariant')) {
    /**
     * The handoff work-card variant for a demo project's 1-based position within its category.
     *
     * @return 'video'|'gallery'|'image'
     */
    function peregoProjectVariant(int $position): string
    {
        if ($position === 3) {
            return 'gallery';
        }
        if (in_array($position, [8, 18, 21], true)) {
            return 'image';
        }

        return 'video';
    }
}

if (! function_exists('peregoDemoVideoUrl')) {
    /**
     * The prototype's own placeholder embed (handoff service-*.html data-video) — replace with the
     * project's real video when real work lands.
     */
    function peregoDemoVideoUrl(): string
    {
        return 'https://www.youtube.com/embed/dQw4w9WgXcQ';
    }
}
