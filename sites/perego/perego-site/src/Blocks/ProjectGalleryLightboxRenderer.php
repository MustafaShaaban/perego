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

        $html = '<section class="project-gallery" aria-labelledby="project-gallery-title" data-wp-interactive="perego/project-gallery" '
            . "data-wp-context='" . $context . "'>";

        $html .= '<h2 id="project-gallery-title" class="project-gallery__title">' . esc_html($strings['sectionLabel']) . '</h2>';
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
        $html = '<ul class="project-gallery__grid" aria-label="' . esc_attr($sectionLabel) . '">';

        foreach ($images as $index => $image) {
            $thumb = $image['thumb'] !== '' ? $image['thumb'] : $image['src'];

            $html .= '<li class="project-gallery__item">'
                . '<button type="button" class="project-gallery__thumb" '
                . "data-wp-context='" . esc_attr((string) wp_json_encode(['index' => $index])) . "' "
                . 'data-wp-on--click="actions.open">'
                . '<img src="' . esc_url($thumb) . '" alt="' . esc_attr($image['alt']) . '" loading="lazy" />'
                . '</button></li>';
        }

        $html .= '</ul>';

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
