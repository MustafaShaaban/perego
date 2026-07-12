<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Content\ServiceContent;

/**
 * Server-renders the perego-theme/service-hero block (spec 003 / M3, US3): the service single's
 * hero band — an "Our Services" eyebrow, the current service's full name as the page H1, and the
 * shared four-service tabs (each a main link to /services/<slug> plus a "Start your project" CTA to
 * the contact page with the service pre-selected). Ported faithfully from the handoff `.svc-hero` /
 * `.svc-tabs`. Structural navigation only — the editorial service prose lives in the post content.
 *
 * The current service is marked `.is-active` + `aria-current="page"`. The decorative background
 * artwork lands with the asset pipeline; the band renders correctly without it.
 */
final class ServiceHeroRenderer
{
    public function render(ServiceContent $content, string $currentSlug): string
    {
        $eyebrow = $content->overview()['h1']; // "Our Services" / "خدماتنا"
        $title = $currentSlug !== '' ? $content->fullName($currentSlug) : $eyebrow;

        $html = '<section class="svc-hero">';
        $html .= '<div class="svc-hero__bg" aria-hidden="true"></div>';
        $html .= '<div class="svc-hero__inner">';
        $html .= '<p class="svc-hero__eyebrow">' . esc_html($eyebrow) . '</p>';
        $html .= '<h1 class="svc-hero__title">' . esc_html($title) . '</h1>';
        $html .= $this->renderTabs($content, $currentSlug);
        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    private function renderTabs(ServiceContent $content, string $currentSlug): string
    {
        $startLabel = $content->label('startYourProject');

        $html = '<nav class="svc-tabs" aria-label="' . esc_attr($content->overview()['h1']) . '">';

        foreach ($content->slugs() as $slug) {
            $isActive = $slug === $currentSlug;
            $classes = 'svc-tab' . ($isActive ? ' is-active' : '');
            $mainHref = esc_url(home_url('/services/' . $slug));
            // Pass the canonical service slug (what ProjectBriefForm's chooser is keyed on and
            // whitelists), not the localized name — so the contact form preselects this service.
            $ctaHref = esc_url(home_url('/contact?service=' . rawurlencode($slug)));
            $aria = $isActive ? ' aria-current="page"' : '';

            $html .= '<div class="' . $classes . '">';
            $html .= '<a class="svc-tab__main" href="' . $mainHref . '"' . $aria . '>';
            $html .= '<span class="svc-tab__label">' . esc_html($content->name($slug)) . '</span>';
            $html .= '</a>';
            $html .= '<a class="svc-tab__cta" href="' . $ctaHref . '">' . esc_html($startLabel) . '</a>';
            $html .= '</div>';
        }

        $html .= '</nav>';

        return $html;
    }
}
