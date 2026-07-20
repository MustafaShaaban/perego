<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Content\ServiceContent;

/**
 * Server-renders the Website-Making single's unique last section (handoff
 * `service-website-making.html:130-283`): the "Websites we've built" showcase — browser-chrome cards
 * with type-filter pills, a Preview shot opening the site-wide media lightbox
 * (perego-theme/media-lightbox), and an external Visit link. It replaces the Selected-work masonry
 * on that one service page (the perego-theme/service-selected-work render callback branches here),
 * so the shared single-perego_service template stays untouched. The filter behaviour lives in the
 * service-selected-work block's view.js (webFilter binder).
 *
 * `render()` takes an already-resolved card list so it stays a pure, unit-testable function; the
 * block's render callback does the category-filtered WP_Query + meta reads.
 */
final class WebShowcaseRenderer
{
    private const PADLOCK_SVG = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">'
        . '<path d="M12 1a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-9a2 2 0 0 0'
        . '-2-2h-1V6a5 5 0 0 0-5-5zm3 8H9V6a3 3 0 0 1 6 0v3z"/></svg>';

    private const VISIT_ARROW_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" '
        . 'stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . '<path d="M7 17 17 7M9 7h8v8"/></svg>';

    /**
     * @param list<array{title: string, shotUrl: string, fullUrl: string, siteType: string, siteUrl: string}> $cards
     */
    public function render(ServiceContent $content, array $cards): string
    {
        $copy = $content->webShowcase();
        $usable = array_values(array_filter(
            $cards,
            static fn (array $card): bool => $card['shotUrl'] !== '',
        ));

        if ($usable === []) {
            return '';
        }

        $html = '<section class="web-showcase page-section">';
        $html .= '<div class="container">';
        $html .= '<div class="web-showcase__head reveal">'
            . '<h2 class="section-title">' . esc_html($copy['title']) . '</h2>'
            . '<p>' . esc_html($copy['intro']) . '</p>'
            . '</div>';
        $html .= $this->renderFilters($copy, $usable);
        $html .= '<div class="web-grid" id="webGrid">';
        foreach ($usable as $index => $card) {
            $html .= $this->renderCard($copy, $card, $index);
        }
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * The type-filter pills — only for types actually present in the data, so no pill ever
     * filters down to an empty grid. No pills at all (0–1 distinct types) hides the group.
     *
     * @param array<string, mixed> $copy
     * @param list<array{title: string, shotUrl: string, fullUrl: string, siteType: string, siteUrl: string}> $cards
     */
    private function renderFilters(array $copy, array $cards): string
    {
        $present = array_unique(array_filter(array_column($cards, 'siteType')));
        // Keep the handoff's fixed pill order, not data order.
        $types = array_intersect_key($copy['types'], array_flip($present));

        if (count($types) < 2) {
            return '';
        }

        $html = '<div class="web-filters reveal" data-delay="1" role="group" aria-label="'
            . esc_attr($copy['filterAria']) . '">';
        $html .= '<button type="button" class="web-filter is-active" data-filter="all">'
            . esc_html($copy['filterAll']) . '</button>';
        foreach ($types as $type => $label) {
            $html .= '<button type="button" class="web-filter" data-filter="' . esc_attr($type) . '">'
                . esc_html($label) . '</button>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * @param array<string, mixed> $copy
     * @param array{title: string, shotUrl: string, fullUrl: string, siteType: string, siteUrl: string} $card
     */
    private function renderCard(array $copy, array $card, int $index): string
    {
        $host = $this->displayHost($card['siteUrl']);
        $delay = $index % 3;
        $lightboxSrc = $card['fullUrl'] !== '' ? $card['fullUrl'] : $card['shotUrl'];

        $html = '<article class="web-card reveal" data-category="' . esc_attr($card['siteType']) . '"'
            . ($delay > 0 ? ' data-delay="' . $delay . '"' : ' data-delay="0"') . '>';

        $html .= '<div class="web-card__bar">'
            . '<div class="web-card__dots"><span></span><span></span><span></span></div>';
        if ($host !== '') {
            $html .= '<div class="web-card__url">' . self::PADLOCK_SVG . '<span>' . esc_html($host) . '</span></div>';
        }
        $html .= '</div>';

        $html .= '<button type="button" class="web-card__shot" data-image="' . esc_attr($lightboxSrc) . '" '
            . 'aria-label="' . esc_attr($copy['preview'] . ' ' . $card['title']) . '">'
            . '<img src="' . esc_url($card['shotUrl']) . '" alt="' . esc_attr($card['title']) . '" loading="lazy" />'
            . '<span class="web-card__view"><span>' . esc_html($copy['preview']) . '</span></span>'
            . '</button>';

        $html .= '<div class="web-card__body">';
        $html .= '<div><h3 class="web-card__title">' . esc_html($card['title']) . '</h3>';
        $typeLabel = $copy['types'][$card['siteType']] ?? '';
        if ($typeLabel !== '') {
            $html .= '<span class="web-card__cat">' . esc_html($typeLabel) . '</span>';
        }
        $html .= '</div>';
        if ($card['siteUrl'] !== '') {
            $html .= '<a class="web-card__visit" href="' . esc_url($card['siteUrl']) . '" target="_blank" rel="noopener">'
                . esc_html($copy['visit']) . ' ' . self::VISIT_ARROW_SVG . '</a>';
        }
        $html .= '</div>';

        $html .= '</article>';

        return $html;
    }

    /** The bare host shown in the card's browser bar ("aurora-retail.com" for https://aurora-retail.com/x). */
    private function displayHost(string $siteUrl): string
    {
        if ($siteUrl === '') {
            return '';
        }

        $host = parse_url($siteUrl, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return '';
        }

        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }
}
