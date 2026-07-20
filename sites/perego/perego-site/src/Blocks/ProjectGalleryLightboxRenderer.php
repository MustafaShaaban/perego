<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

/**
 * Server-renders the perego/project-gallery-lightbox block (spec 003 / M3, US2; spec 020 round 6):
 * a project single's gallery thumbnail grid. Each thumb is a `data-gallery` trigger for the ONE
 * site-wide dialog (perego-theme/media-lightbox) — the handoff's GlobalMediaLightbox contract
 * mandates exactly one lightbox instance per page, opened by these thumbnails; this block's own
 * embedded Interactivity dialog was removed because it double-opened alongside the global one.
 * `data-gallery-index` tells the global dialog which image the clicked thumb represents.
 *
 * `render()` takes a resolved images array so it stays a pure, unit-testable function; the block's
 * render callback pulls the current project's gallery.
 */
final class ProjectGalleryLightboxRenderer
{
    /**
     * @param list<array{src: string, thumb: string, alt: string}> $images
     */
    public function render(array $images, string $sectionLabel): string
    {
        if ($images === []) {
            return '';
        }

        $html = '<section class="portfolio project-gallery" aria-labelledby="pjGallery" style="margin-top:clamp(32px,4vw,52px);">';
        $html .= '<h2 id="pjGallery" class="section-title" style="text-align:center;margin-bottom:clamp(20px,3vw,34px);">'
            . esc_html($sectionLabel) . '</h2>';
        $html .= $this->renderThumbs($images, $sectionLabel);
        $html .= '</section>';

        return $html;
    }

    /**
     * @param list<array{src: string, thumb: string, alt: string}> $images
     */
    private function renderThumbs(array $images, string $sectionLabel): string
    {
        $html = '<div class="work-masonry" aria-label="' . esc_attr($sectionLabel) . '">';
        $gallerySources = implode(',', array_map(static fn (array $image): string => $image['src'], $images));

        foreach ($images as $index => $image) {
            /* translators: %d: 1-based position of the image within the project gallery. */
            $openLabel = sprintf(__('Open project media %d', 'perego-site'), $index + 1);

            $html .= '<button type="button" class="work-card reveal" '
                . 'data-gallery="' . esc_attr($gallerySources) . '" data-gallery-index="' . $index . '" '
                . 'aria-label="' . esc_attr($openLabel) . '">'
                . '<img src="' . esc_url($image['thumb'] !== '' ? $image['thumb'] : $image['src']) . '" alt="' . esc_attr($image['alt']) . '" loading="lazy" />'
                . '<span class="work-card__overlay"></span><span class="work-zoom" aria-hidden="true"></span></button>';
        }

        $html .= '</div>';

        return $html;
    }
}
