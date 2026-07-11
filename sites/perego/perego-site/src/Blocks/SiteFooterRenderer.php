<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

/**
 * Server-renders the perego/site-footer block: the standard 3-column layout (contact / quick-
 * message form entry point / careers form entry point) + a bottom bar, or the flat 2-column
 * variant used on the contact page (spec 001 FR-005). Real form submission wiring is M4 — this
 * renders the structural entry points only.
 */
final class SiteFooterRenderer
{
    public function render(bool $flat = false): string
    {
        $classes = 'perego-footer' . ($flat ? ' perego-footer--flat' : '');

        $html = '<footer class="' . esc_attr($classes) . '" id="contact">';
        $html .= '<div class="perego-footer__columns">';
        $html .= $this->renderContactColumn();
        $html .= $this->renderQuickMessageColumn();

        if (! $flat) {
            $html .= $this->renderCareersColumn();
        }

        $html .= '</div>';
        $html .= $this->renderBottomBar();
        $html .= '</footer>';

        return $html;
    }

    private function renderContactColumn(): string
    {
        return '<div class="perego-footer__contact">'
            . '<h2>' . esc_html__('Contact us', 'perego-site') . '</h2>'
            . '</div>';
    }

    private function renderQuickMessageColumn(): string
    {
        return '<div class="perego-footer__quick-message">'
            . '<h2>' . esc_html__('Send a quick message', 'perego-site') . '</h2>'
            . '</div>';
    }

    private function renderCareersColumn(): string
    {
        return '<div class="perego-footer__careers">'
            . '<h2>' . esc_html__('Join us', 'perego-site') . '</h2>'
            . '</div>';
    }

    private function renderBottomBar(): string
    {
        $year = esc_html(gmdate('Y'));

        return '<div class="perego-footer__bottom">'
            . '<p>&copy; ' . $year . ' ' . esc_html__('Perego', 'perego-site') . '</p>'
            . '<nav aria-label="' . esc_attr__('Legal', 'perego-site') . '">'
            . '<a href="' . esc_url(home_url('/terms')) . '">' . esc_html__('Terms & Conditions', 'perego-site') . '</a>'
            . '<a href="' . esc_url(home_url('/privacy')) . '">' . esc_html__('Privacy Policy', 'perego-site') . '</a>'
            . '</nav>'
            . '</div>';
    }
}
