<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

/**
 * Server-renders the perego-theme/service-selected-work block: the service single's own "Selected
 * work" masonry (handoff `service-*.html`, e.g. `service-video-editing.html:129-134`) — real published
 * projects filtered to the current service's category, opening the site-wide media lightbox
 * (perego-theme/media-lightbox). Unlike the Services archive's version, the handoff's per-service
 * masonry has no heading of its own.
 *
 * `render()` takes an already-resolved project list so it stays a pure, unit-testable function; the
 * block's render callback does the category-filtered WP_Query.
 */
final class ServiceSelectedWorkRenderer
{
    /**
     * @param list<array{title: string, thumbUrl: string, thumbAlt: string, gallerySrcs: list<string>}> $projects
     */
    public function render(array $projects): string
    {
        $cards = array_filter(array_map(
            fn (array $project): string => $this->card($project),
            $projects,
        ));

        if ($cards === []) {
            return '';
        }

        $html = '<section class="portfolio page-section">';
        $html .= '<div class="container">';
        $html .= '<div class="work-masonry">';
        $html .= implode('', $cards);
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * @param array{title: string, thumbUrl: string, thumbAlt: string, gallerySrcs: list<string>} $project
     */
    private function card(array $project): string
    {
        if (count($project['gallerySrcs']) > 1) {
            $trigger = 'data-gallery="' . esc_attr(implode(',', $project['gallerySrcs'])) . '"';
        } elseif ($project['thumbUrl'] !== '') {
            $trigger = 'data-image="' . esc_attr($project['thumbUrl']) . '"';
        } else {
            return '';
        }

        return '<button type="button" class="work-card reveal" ' . $trigger . ' '
            . 'aria-label="' . esc_attr(sprintf(__('Open %s', 'perego-site'), $project['title'])) . '">'
            . '<img src="' . esc_url($project['thumbUrl']) . '" alt="' . esc_attr($project['thumbAlt']) . '" loading="lazy" />'
            . '<span class="work-card__overlay"></span><span class="work-zoom" aria-hidden="true"></span></button>';
    }
}
