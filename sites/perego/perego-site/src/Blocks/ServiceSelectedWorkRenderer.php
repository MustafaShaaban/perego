<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use Corex\Assets\Image;

/**
 * Server-renders the perego-theme/service-selected-work block: the service single's own "Selected
 * work" masonry (handoff `service-*.html`, e.g. `service-video-editing.html:130-256`) — real published
 * projects filtered to the current service's category, opening the site-wide media lightbox
 * (perego-theme/media-lightbox). Unlike the Services archive's version, the handoff's per-service
 * masonry has no heading of its own.
 *
 * The handoff mosaic is a 7-column grid of 15 exactly-placed tiles (`.m1`–`.m15`,
 * perego-reference.scss) with a non-interactive brand card beside the lead tile, then a hidden
 * 3-column overflow grid behind a "Load more" button (its reveal behaviour lives in this block's
 * view.js). `masonry()` is shared with ServicesOverviewRenderer so the archive's "Selected work"
 * gets the identical designed placement.
 *
 * `render()` takes an already-resolved project list so it stays a pure, unit-testable function; the
 * block's render callback does the category-filtered WP_Query.
 */
final class ServiceSelectedWorkRenderer
{
    /** The handoff mosaic has exactly 15 designed placements; everything after is "Load more". */
    public const MASONRY_TILES = 15;

    /**
     * How many projects a service single asks for, by service.
     *
     * These were a bare `? 23 : 6` in the block's render callback. They are here because the editor
     * canvas has to know them: a canvas that renders fifteen tiles where the front end renders six is
     * not a preview of anything, and `src/Editor/projectSlots.js` mirrors these two values with a test
     * that parses this file.
     *
     * Website Making asks for far more because it does not use this mosaic at all — it branches to
     * {@see WebShowcaseRenderer}, a uniform grid of browser-chrome cards with its own filter row.
     */
    public const WORK_CAP_DEFAULT = 6;
    public const WORK_CAP_WEB = 23;

    /**
     * @param list<array{title: string, thumbUrl: string, thumbAlt: string, gallerySrcs: list<string>, videoUrl?: string}> $projects
     * @param array{loadMore?: string, galleryBadge?: string} $labels locale-resolved UI strings (ServiceContent)
     */
    public function render(array $projects, array $labels = []): string
    {
        $masonry = $this->masonry($projects, $labels);

        if ($masonry === '') {
            return '';
        }

        // Client request 2026-07-26: the same wavy pattern the services teaser and clients carousel
        // already use, so the grid sits on the brand background rather than flat colour.
        return '<section class="portfolio page-section">'
            . '<div class="wavy-bg" aria-hidden="true">'
            . Image::picture('images/wavy-corners.png', [
                'base' => 'perego-theme',
                'alt' => '',
                'width' => 2560,
                'height' => 1440,
            ])
            . '</div>'
            . '<div class="container">'
            . $masonry
            . '</div>'
            . '</section>';
    }

    /**
     * The designed mosaic itself (placed tiles + brand card + hidden overflow grid + Load more),
     * without the section/heading wrapper — the service single and the Services archive wrap it
     * differently. Returns '' when no project has usable media.
     *
     * @param list<array{title: string, thumbUrl: string, thumbAlt: string, gallerySrcs: list<string>, videoUrl?: string}> $projects
     * @param array{loadMore?: string, galleryBadge?: string} $labels
     */
    public function masonry(array $projects, array $labels = []): string
    {
        $usable = array_values(array_filter(
            $projects,
            static fn (array $project): bool => $project['thumbUrl'] !== '' || count($project['gallerySrcs']) > 1,
        ));

        if ($usable === []) {
            return '';
        }

        $placed = array_slice($usable, 0, self::MASONRY_TILES);
        $overflow = array_slice($usable, self::MASONRY_TILES);
        $galleryBadge = $labels['galleryBadge'] ?? __('Gallery', 'perego-site');

        $html = '<div class="work-masonry">';
        foreach ($placed as $index => $project) {
            $slot = 'm' . ($index + 1);
            $html .= $this->card($project, 'work-card ' . $slot . ' reveal', $galleryBadge, ['slot' => $slot]);

            if ($index === 0) {
                // The handoff's non-interactive brand card sits right after the lead tile
                // (service-graphic-design.html:133-140); its placement + background come from
                // the reference `.work-brand` rules, and it disappears entirely on small screens.
                $html .= '<div class="work-card work-brand" aria-hidden="true">'
                    . '<img class="work-brand__logo" src="'
                    . esc_url(get_stylesheet_directory_uri() . '/assets/images/logo-full.png') . '" alt="" />'
                    . '</div>';
            }
        }
        $html .= '</div>';

        if ($overflow !== []) {
            $html .= '<div class="work-more-grid" id="workMore" hidden>';
            foreach ($overflow as $index => $project) {
                $delay = $index % 4;
                $html .= $this->card(
                    $project,
                    'work-card reveal',
                    $galleryBadge,
                    ['attrs' => $delay > 0 ? ' data-delay="' . $delay . '"' : ''],
                );
            }
            $html .= '</div>';
            $html .= '<div class="load-more-wrap">'
                . '<button type="button" class="btn btn--dark load-more-btn" id="loadMore">'
                . esc_html($labels['loadMore'] ?? __('Load more', 'perego-site'))
                . '</button>'
                . '</div>';
        }

        return $html;
    }

    /**
     * One masonry tile.
     *
     * The tile's TRIGGER is still derived from the project's media — a video opens `data-video`, a
     * multi-image project `data-gallery`, anything else `data-image`. What the tile ADVERTISES is now
     * the editor's explicit choice (`_perego_project_icon`), because those are different questions:
     * a project can carry a video without wanting a ▶ on every grid it appears in. Before this, a
     * video always forced the badge and nobody could turn it off.
     *
     * @param array{title: string, thumbUrl: string, thumbAlt: string, gallerySrcs: list<string>, videoUrl?: string, id?: int, icon?: string} $project
     * @param array{slot?: string, attrs?: string} $tile where this tile sits, and any extra attributes
     */
    private function card(array $project, string $classes, string $galleryBadge, array $tile = []): string
    {
        $slot = (string) ($tile['slot'] ?? '');
        $extraAttrs = (string) ($tile['attrs'] ?? '');

        $videoUrl = $project['videoUrl'] ?? '';
        $isGallery = $videoUrl === '' && count($project['gallerySrcs']) > 1;

        if ($videoUrl !== '') {
            $trigger = 'data-video="' . esc_attr($videoUrl) . '"';
        } elseif ($isGallery) {
            $trigger = 'data-gallery="' . esc_attr(implode(',', $project['gallerySrcs'])) . '"';
        } else {
            $trigger = 'data-image="' . esc_attr($project['thumbUrl']) . '"';
        }

        // A gallery project may have gallery images but no featured thumb; show its first image.
        $thumbUrl = $project['thumbUrl'] !== '' ? $project['thumbUrl'] : ($project['gallerySrcs'][0] ?? '');
        $media = ProjectTileImage::render(
            (int) ($project['id'] ?? 0),
            $slot,
            $project['thumbAlt'],
            $thumbUrl
        );

        /* translators: %s: project title. */
        $openLabel = sprintf(__('Open %s', 'perego-site'), $project['title']);

        return '<button type="button" class="' . esc_attr($classes) . '" ' . $trigger . $extraAttrs . ' '
            . 'aria-label="' . esc_attr($openLabel) . '">'
            . $media
            . '<span class="work-card__overlay"></span>'
            . self::affordance($project['icon'] ?? 'none', $galleryBadge)
            . '</button>';
    }

    /**
     * The tile's visible affordance for the editor's chosen icon.
     *
     * The ▶ is decorative (`aria-hidden`) because the tile is already a labelled button; the gallery
     * badge is real text, so it stays readable to assistive tech.
     */
    public static function affordance(string $icon, string $galleryBadge): string
    {
        return match ($icon) {
            'play' => '<span class="play-btn" aria-hidden="true"></span>',
            'gallery' => '<span class="work-badge">' . esc_html($galleryBadge) . '</span>',
            default => '',
        };
    }
}
