<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

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
    private const MASONRY_TILES = 15;

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

        return '<section class="portfolio page-section">'
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
            $html .= $this->card($project, 'work-card m' . ($index + 1) . ' reveal', $galleryBadge);

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
                    $delay > 0 ? ' data-delay="' . $delay . '"' : '',
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
     * One masonry tile in its handoff variant: a video project is a ▶ `data-video` card
     * (always-visible play button), a multi-image project is a `data-gallery` card (with a "Gallery"
     * badge), anything else is a single-image `data-image` card. Owner review (spec 020): the hover
     * zoom "+" glyph (`.work-zoom`) is dropped from gallery and image cards — only video tiles carry a
     * visible affordance (the ▶); non-video tiles still open the lightbox on click, without an icon.
     *
     * @param array{title: string, thumbUrl: string, thumbAlt: string, gallerySrcs: list<string>, videoUrl?: string} $project
     */
    private function card(array $project, string $classes, string $galleryBadge, string $extraAttrs = ''): string
    {
        $videoUrl = $project['videoUrl'] ?? '';
        $isVideo = $videoUrl !== '';
        $isGallery = ! $isVideo && count($project['gallerySrcs']) > 1;

        if ($isVideo) {
            $trigger = 'data-video="' . esc_attr($videoUrl) . '"';
            $affordance = '<span class="play-btn" aria-hidden="true"></span>';
        } elseif ($isGallery) {
            $trigger = 'data-gallery="' . esc_attr(implode(',', $project['gallerySrcs'])) . '"';
            $affordance = '<span class="work-badge">' . esc_html($galleryBadge) . '</span>';
        } else {
            $trigger = 'data-image="' . esc_attr($project['thumbUrl']) . '"';
            $affordance = '';
        }

        // A gallery project may have gallery images but no featured thumb; show its first image.
        $thumbUrl = $project['thumbUrl'] !== '' ? $project['thumbUrl'] : ($project['gallerySrcs'][0] ?? '');

        /* translators: %s: project title. */
        $openLabel = sprintf(__('Open %s', 'perego-site'), $project['title']);

        return '<button type="button" class="' . esc_attr($classes) . '" ' . $trigger . $extraAttrs . ' '
            . 'aria-label="' . esc_attr($openLabel) . '">'
            . '<img src="' . esc_url($thumbUrl) . '" alt="' . esc_attr($project['thumbAlt']) . '" loading="lazy" />'
            . '<span class="work-card__overlay"></span>'
            . $affordance
            . '</button>';
    }
}
