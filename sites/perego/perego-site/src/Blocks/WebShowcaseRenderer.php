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
 * with type-filter pills, a shot linking to the live site, and an external Visit link. It replaces the
 * Selected-work masonry on that one service page (the perego-theme/service-selected-work render callback
 * branches here), so the shared single-perego_service template stays untouched. The filter behaviour
 * lives in the service-selected-work block's view.js (webFilter binder).
 *
 * The shot used to open the full-size screenshot in the site-wide media lightbox. Client request
 * 2026-07-27 removed that: on a card that already wears browser chrome the useful destination is the
 * real site, not a bigger picture of it. This section no longer participates in the lightbox at all.
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
     * @param list<array{title: string, shotUrl: string, fullUrl: string, siteType: string, siteUrl: string, logoUrl: string, logoAlt: string}> $cards
     */
    public function render(ServiceContent $content, array $cards): string
    {
        $copy = $content->webShowcase();
        // A card needs a logo OR a screenshot. It used to require a screenshot, which would now drop
        // every site the client asked to be listed but whose logo is the only asset we hold — and the
        // client's instruction was explicitly "don't miss any site". A card with neither renders its
        // name plate, so the only real requirement left is a title.
        $usable = array_values(array_filter(
            $cards,
            static fn (array $card): bool => $card['logoUrl'] !== '' || $card['shotUrl'] !== '',
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
     * @param list<array{title: string, shotUrl: string, fullUrl: string, siteType: string, siteUrl: string, logoUrl: string, logoAlt: string}> $cards
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
     * @param array{title: string, shotUrl: string, fullUrl: string, siteType: string, siteUrl: string, logoUrl: string, logoAlt: string} $card
     */
    private function renderCard(array $copy, array $card, int $index): string
    {
        $host = $this->displayHost($card['siteUrl']);
        $delay = $index % 3;

        $html = '<article class="web-card reveal" data-category="' . esc_attr($card['siteType']) . '"'
            . ($delay > 0 ? ' data-delay="' . $delay . '"' : ' data-delay="0"') . '>';

        $html .= '<div class="web-card__bar">'
            . '<div class="web-card__dots"><span></span><span></span><span></span></div>';
        if ($host !== '') {
            $html .= '<div class="web-card__url">' . self::PADLOCK_SVG . '<span>' . esc_html($host) . '</span></div>';
        }
        $html .= '</div>';

        $html .= $this->renderShot($copy, $card);

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

    /**
     * The screenshot inside the browser chrome, as a link to the live site.
     *
     * A card with no recorded URL has nowhere to go, so it renders as a plain <div> with no hover
     * affordance rather than a control that does nothing — the same rule the home Work grid follows for
     * a logo card without a URL. The hover label reuses the existing `visit` string; there is no
     * "preview" any more now that nothing is previewed.
     *
     * @param array<string, mixed> $copy
     * @param array{title: string, shotUrl: string, fullUrl: string, siteType: string, siteUrl: string, logoUrl: string, logoAlt: string} $card
     */
    private function renderShot(array $copy, array $card): string
    {
        // The logo is the subject now, not the screenshot: the client asked this section to show the
        // brand marks of the sites they built. A screenshot is kept only as the fallback for a project
        // that has one and no logo yet, and a project with neither gets the same typographic plate the
        // home grid uses, so the row never breaks.
        if ($card['logoUrl'] !== '') {
            $img = '<img src="' . esc_url($card['logoUrl']) . '" alt="' . esc_attr($card['logoAlt'] !== '' ? $card['logoAlt'] : $card['title']) . '" loading="lazy" />';
            $shotClass = 'web-card__shot web-card__shot--logo';
        } elseif ($card['shotUrl'] !== '') {
            $img = '<img src="' . esc_url($card['shotUrl']) . '" alt="' . esc_attr($card['title']) . '" loading="lazy" />';
            $shotClass = 'web-card__shot';
        } else {
            $img = '<span class="post-card__plate" aria-hidden="true">'
                . '<span class="post-card__plate-name">' . esc_html($card['title']) . '</span>'
                . '<span class="post-card__plate-host">' . esc_html($this->displayHost($card['siteUrl'])) . '</span></span>';
            $shotClass = 'web-card__shot web-card__shot--plate';
        }

        if ($card['siteUrl'] === '') {
            return '<div class="' . $shotClass . '">' . $img . '</div>';
        }

        return '<a class="' . $shotClass . '" href="' . esc_url($card['siteUrl']) . '" target="_blank" rel="noopener" '
            . 'aria-label="' . esc_attr($copy['visit'] . ' ' . $card['title']) . '">'
            . $img
            . '<span class="web-card__view"><span>' . esc_html($copy['visit']) . '</span></span>'
            . '</a>';
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
