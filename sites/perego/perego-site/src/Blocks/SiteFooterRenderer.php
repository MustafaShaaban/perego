<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

use PeregoSite\Forms\QuickMessageForm;

defined('ABSPATH') || exit;

/**
 * Server-renders the perego/site-footer block: the standard 3-column layout (contact / quick-
 * message form / careers form entry point) + a bottom bar, or the flat 2-column variant used on
 * the contact page (spec 001 FR-005). The quick-message column embeds the live CoreX form
 * (spec Phase 7, form 1) — the framework runtime drives its default/invalid/submitting/success/
 * server-error states — degrading to the heading-only entry point when CoreX Forms is inactive.
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
            . $this->quickMessageForm()
            . '</div>';
    }

    /**
     * The live quick-message form, rendered through the registered CoreX form block so the block's
     * enqueue/nonce/honeypot/aria-live wiring applies. Falls back to nothing (heading-only entry
     * point) when CoreX Forms — and thus the block — is inactive, keeping the footer non-fatal.
     */
    private function quickMessageForm(): string
    {
        if (! function_exists('do_blocks') || ! class_exists('WP_Block_Type_Registry')) {
            return '';
        }

        if (! \WP_Block_Type_Registry::get_instance()->is_registered('corex/form')) {
            return '';
        }

        return do_blocks(
            '<!-- wp:corex/form {"formSlug":"' . QuickMessageForm::SLUG . '"} /-->'
        );
    }

    private function renderCareersColumn(): string
    {
        return '<div class="perego-footer__careers">'
            . '<h2>' . esc_html__('Join us', 'perego-site') . '</h2>'
            . $this->joinForm()
            . '</div>';
    }

    /**
     * The live "Join us" / CV form, rendered through the registered perego-theme/join-form block so
     * its enqueue/upload-lifecycle wiring applies. Falls back to the heading-only entry point when the
     * block is unregistered (e.g. CoreX Careers inactive), keeping the footer non-fatal.
     */
    private function joinForm(): string
    {
        if (! function_exists('do_blocks') || ! class_exists('WP_Block_Type_Registry')) {
            return '';
        }

        if (! \WP_Block_Type_Registry::get_instance()->is_registered('perego-theme/join-form')) {
            return '';
        }

        return do_blocks('<!-- wp:perego-theme/join-form /-->');
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
