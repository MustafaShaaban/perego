<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

/**
 * Server-renders the perego/project-gallery-lightbox block (spec 003 / M3, US2): a thumbnail grid
 * plus an accessible lightbox dialog (focus trap, counter, prev/next, dots). Ported from the handoff
 * prototype's `.lightbox`. All interaction (open/close/navigate, keyboard, focus trap) is wired by
 * view.js via the Interactivity API directives emitted here.
 *
 * `render()` takes a resolved images array so it stays a pure, unit-testable function; the block's
 * render callback pulls the current project's gallery.
 *
 * @phpstan-type GalleryImage array{src: string, thumb: string, alt: string}
 */
final class ProjectGalleryLightboxRenderer
{
    /**
     * @param list<array{src: string, thumb: string, alt: string}> $images
     * @param array{sectionLabel: string, close: string, prev: string, next: string, counter: string} $strings
     *        `counter` is a template like "%1$s / %2$s"
     */
    public function render(array $images, array $strings): string
    {
        if ($images === []) {
            return '';
        }

        // The lightbox needs the full-size srcs client-side; embed a compact list in context.
        $items = array_map(
            static fn (array $image): array => ['src' => $image['src'], 'alt' => $image['alt']],
            $images,
        );

        $context = esc_attr((string) wp_json_encode([
            'isOpen' => false,
            'activeIndex' => 0,
            'count' => count($images),
            'items' => $items,
            'counter' => $strings['counter'],
        ]));

        $html = '<section class="portfolio project-gallery" aria-labelledby="pjGallery" style="margin-top:clamp(32px,4vw,52px);" data-wp-interactive="perego/project-gallery" '
            . "data-wp-context='" . $context . "' data-wp-init=\"callbacks.init\">";

        $html .= '<h2 id="pjGallery" class="section-title" style="text-align:center;margin-bottom:clamp(20px,3vw,34px);">' . esc_html($strings['sectionLabel']) . '</h2>';
        $html .= $this->renderThumbs($images, $strings['sectionLabel']);
        $html .= $this->renderLightbox($strings);

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
            $html .= '<button type="button" class="work-card reveal" '
                . "data-wp-context='" . esc_attr((string) wp_json_encode(['index' => $index])) . "' "
                . 'data-wp-on--click="actions.open" data-gallery="' . esc_attr($gallerySources) . '" '
                . 'aria-label="' . esc_attr(sprintf(__('Open project media %d', 'perego-site'), $index + 1)) . '">'
                . '<img src="' . esc_url($image['thumb'] !== '' ? $image['thumb'] : $image['src']) . '" alt="' . esc_attr($image['alt']) . '" loading="lazy" />'
                . '<span class="work-card__overlay"></span><span class="work-zoom" aria-hidden="true"></span></button>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * @param array{sectionLabel: string, close: string, prev: string, next: string, counter: string} $strings
     */
    private function renderLightbox(array $strings): string
    {
        $html = '<div class="lightbox" role="dialog" aria-modal="true" '
            . 'aria-label="' . esc_attr($strings['sectionLabel']) . '" '
            . 'hidden '
            . 'data-wp-bind--hidden="callbacks.lightboxHidden" '
            . 'data-wp-on--keydown="actions.onKeydown">';

        $html .= '<div class="lightbox__backdrop" data-wp-on--click="actions.close"></div>';

        $html .= '<div class="lightbox__inner">';
        $html .= '<button type="button" class="lightbox__close" aria-label="' . esc_attr($strings['close']) . '" '
            . 'data-wp-on--click="actions.close">&times;</button>';
        $html .= '<button type="button" class="lightbox__nav lightbox__nav--prev" aria-label="' . esc_attr($strings['prev']) . '" '
            . 'data-wp-on--click="actions.prev">&#8249;</button>';
        $html .= '<div class="lightbox__frame">'
            . '<img class="lightbox__img" data-wp-bind--src="state.currentSrc" data-wp-bind--alt="state.currentAlt" alt="" />'
            . '</div>';
        $html .= '<button type="button" class="lightbox__nav lightbox__nav--next" aria-label="' . esc_attr($strings['next']) . '" '
            . 'data-wp-on--click="actions.next">&#8250;</button>';
        $html .= '<p class="lightbox__counter" aria-live="polite" data-wp-text="state.counterLabel"></p>';
        $html .= '</div>'; // .lightbox__inner

        $html .= '</div>'; // .lightbox

        return $html;
    }
}
