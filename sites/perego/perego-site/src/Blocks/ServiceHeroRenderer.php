<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Content\ServiceContent;
use PeregoSite\Theme\SiteRoutes;

/**
 * Server-renders the perego-theme/service-hero block (spec 003 / M3, US3): the service single's
 * hero band — an "Our Services" eyebrow, the current service's full name as the page H1, and the
 * shared four-service tabs (each a main link to /services/<slug> plus a "Start your project" CTA to
 * the contact page with the service pre-selected). Ported faithfully from the handoff `.svc-hero` /
 * `.svc-tabs`. Structural navigation only — the editorial service prose lives in the post content.
 *
 * Spec 013 T003 — the H1 and tab labels are an editable projection of the `perego_service` CPT: the
 * caller passes the current service's post title and a per-slug label map (from each Service's
 * `_perego_teaser_label`), and this class falls back to the `ServiceContent` seed per field when they
 * are empty — so output is byte-identical until an editor changes a Service, then it tracks the post.
 * The current service is marked `.is-active` + `aria-current="page"`.
 */
final class ServiceHeroRenderer
{
    /**
     * @param string               $currentTitle the queried Service post's title (H1); '' → seed
     * @param array<string, string> $tabLabels    canonical slug => editable tab label; missing → seed
     */
    public function render(ServiceContent $content, string $currentSlug, string $currentTitle = '', array $tabLabels = []): string
    {
        $eyebrow = $content->overview()['h1']; // "Our Services" / "خدماتنا"
        $seedTitle = $currentSlug !== '' ? $content->fullName($currentSlug) : $eyebrow;
        $title = $currentTitle !== '' ? $currentTitle : $seedTitle;

        $html = '<section class="svc-hero">';
        $html .= '<div class="svc-hero__bg" aria-hidden="true">'
            . '<img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/svc-hero-bg.webp') . '" alt="" />'
            . '</div>';
        $html .= '<div class="container svc-hero__inner">';
        $html .= '<p class="svc-hero__eyebrow reveal">' . esc_html($eyebrow) . '</p>';
        $html .= '<h1 class="svc-hero__title reveal" data-delay="1">' . esc_html($title) . '</h1>';
        $html .= $this->renderTabs($content, $currentSlug, $tabLabels);
        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * @param array<string, string> $tabLabels
     */
    private function renderTabs(ServiceContent $content, string $currentSlug, array $tabLabels): string
    {
        $startLabel = $content->label('startYourProject');

        $html = '<nav class="svc-tabs reveal" data-delay="1" aria-label="' . esc_attr($content->overview()['h1']) . '">';

        foreach ($content->slugs() as $slug) {
            $isActive = $slug === $currentSlug;
            $classes = 'svc-tab' . ($isActive ? ' is-active' : '');
            $mainHref = esc_url(home_url('/services/' . $slug));
            // Pass the canonical service slug (what ProjectBriefForm's chooser is keyed on and
            // whitelists), not the localized name — so the contact form preselects this service.
            $ctaHref = esc_url(add_query_arg('service', $slug, home_url(SiteRoutes::START_PROJECT)));
            $aria = $isActive ? ' aria-current="page"' : '';
            $label = ($tabLabels[$slug] ?? '') !== '' ? $tabLabels[$slug] : $content->name($slug);

            $html .= '<div class="' . $classes . '">';
            $html .= '<a class="svc-tab__main" href="' . $mainHref . '"' . $aria . '>';
            $html .= '<span class="svc-tab__label">' . esc_html($label) . '</span>';
            $html .= '</a>';
            $html .= '<a class="svc-tab__cta" href="' . $ctaHref . '">' . esc_html($startLabel) . '</a>';
            $html .= '</div>';
        }

        $html .= '</nav>';

        return $html;
    }
}
