<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

use PeregoSite\Content\GlobalContent;
use PeregoSite\Forms\QuickMessageForm;
use PeregoSite\Services\LanguageService;

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
    /**
     * Contact channels from the approved design handoff (locale-neutral facts, not translatable
     * prose). Social links are the handoff's own placeholder destinations pending real owner handles.
     *
     * @var list<array{label: string, href: string}>
     */
    private const CONTACT_CHANNELS = [
        ['label' => 'mostafa.emam3313@gmail.com', 'href' => 'mailto:mostafa.emam3313@gmail.com'],
        ['label' => 'yehemam2@gmail.com', 'href' => 'mailto:yehemam2@gmail.com'],
        ['label' => '+996 56 293 2759', 'href' => 'tel:+996562932759'],
        ['label' => '+20 111 54 855 72', 'href' => 'tel:+201115485572'],
    ];

    /**
     * @var list<array{network: string, href: string}>
     */
    private const SOCIAL_LINKS = [
        ['network' => 'Instagram', 'href' => 'https://www.instagram.com/'],
        ['network' => 'Facebook', 'href' => 'https://www.facebook.com/'],
        ['network' => 'LinkedIn', 'href' => 'https://www.linkedin.com/'],
        ['network' => 'YouTube', 'href' => 'https://www.youtube.com/'],
        ['network' => 'WhatsApp', 'href' => 'https://wa.me/'],
    ];

    public function __construct(private readonly LanguageService $languageService)
    {
    }

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
        $blurb = ( new GlobalContent($this->languageService->driver()->currentLocale()) )->footer()['blurb'];

        $html = '<div class="perego-footer__contact">';

        $html .= '<a class="perego-footer__logo" href="' . esc_url(home_url('/')) . '" '
            . 'aria-label="' . esc_attr__('Perego — home', 'perego-site') . '">'
            . '<img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/logo-full.png') . '" alt="" />'
            . '</a>';

        $html .= '<h2>' . esc_html__('Contact us', 'perego-site') . '</h2>';
        $html .= $this->renderContactChannels();
        $html .= '<p class="perego-footer__blurb">' . esc_html($blurb) . '</p>';
        $html .= $this->renderSocialLinks();

        $html .= '</div>';

        return $html;
    }

    private function renderContactChannels(): string
    {
        $html = '<ul class="perego-footer__channels">';

        foreach (self::CONTACT_CHANNELS as $channel) {
            // bdi isolates the LTR email/phone text from the surrounding RTL paragraph direction so
            // digit groups and punctuation don't reorder under the Arabic bidi algorithm.
            $html .= '<li><a href="' . esc_url($channel['href']) . '"><bdi>' . esc_html($channel['label']) . '</bdi></a></li>';
        }

        $html .= '</ul>';

        return $html;
    }

    private function renderSocialLinks(): string
    {
        $html = '<ul class="perego-footer__social" aria-label="' . esc_attr__('Social media', 'perego-site') . '">';

        foreach (self::SOCIAL_LINKS as $social) {
            $label = sprintf(
                /* translators: %s: social network name */
                __('Perego on %s', 'perego-site'),
                $social['network']
            );

            $html .= '<li><a href="' . esc_url($social['href']) . '" target="_blank" rel="noopener" '
                . 'aria-label="' . esc_attr($label) . '">'
                . '<span class="perego-footer__social-icon perego-footer__social-icon--' . esc_attr(strtolower($social['network'])) . '" aria-hidden="true"></span>'
                . '</a></li>';
        }

        $html .= '</ul>';

        return $html;
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
