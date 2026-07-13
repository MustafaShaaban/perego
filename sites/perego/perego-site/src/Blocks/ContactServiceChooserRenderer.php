<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

use PeregoSite\PostTypes\ServicePostType;

defined('ABSPATH') || exit;

/** Renders the handoff contact-service controls from the current-language service records. */
final class ContactServiceChooserRenderer
{
    /**
     * The contact handoff deliberately uses concise service labels while the service records retain
     * their complete editorial names for headings, SEO, and form submissions.
     *
     * @var array<string,string>
     */
    private const HANDOFF_LABELS = [
        'video-editing' => 'Video Editing',
        'motion-graphics' => '2D Motion Graphics',
        'graphic-design' => 'Graphic Design',
        'website-making' => 'Website Making',
    ];

    public function render(): string
    {
        $html = '<div class="contact-choose reveal" data-delay="1" data-perego-service-chooser>';
        $html .= '<h2 class="section-title">' . esc_html__('Choose your service', 'perego-site') . '</h2>';
        $html .= '<div class="svc-choice-list" role="group" aria-label="' . esc_attr__('Choose your service (select one or more)', 'perego-site') . '">';

        foreach ($this->services() as $slug => $label) {
            $selected = $slug === $this->preselectedService();
            $html .= '<button type="button" class="svc-choice' . ($selected ? ' is-selected' : '') . '"'
                . ' data-service="' . esc_attr($slug) . '" aria-pressed="' . ($selected ? 'true' : 'false') . '">'
                . esc_html($this->handoffLabel($slug, $label)) . '</button>';
        }

        $html .= '</div>';
        $html .= '<p class="svc-choice-help">' . esc_html__('Tap to select one or more services. Tap again to deselect.', 'perego-site') . '</p>';
        $html .= '<img class="contact-choose__watermark" src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/logo-full.png') . '" alt="" />';

        return $html . '</div>';
    }

    /** @return array<string,string> */
    private function services(): array
    {
        $services = [];

        foreach ((array) get_posts([
            'post_type' => ServicePostType::POST_TYPE,
            'numberposts' => 20,
            'post_status' => 'publish',
            'orderby' => 'menu_order title',
            'order' => 'ASC',
            'suppress_filters' => false,
        ]) as $post) {
            if (! $post instanceof \WP_Post) {
                continue;
            }

            $slug = (string) get_post_meta($post->ID, '_perego_service_slug', true);
            $services[$slug !== '' ? $slug : $post->post_name] = get_the_title($post);
        }

        return $services !== [] ? $services : ServicePostType::SERVICES;
    }

    private function handoffLabel(string $slug, string $fallback): string
    {
        return isset(self::HANDOFF_LABELS[$slug])
            ? __(self::HANDOFF_LABELS[$slug], 'perego-site')
            : $fallback;
    }

    private function preselectedService(): string
    {
        return isset($_GET['service']) ? sanitize_key(wp_unslash((string) $_GET['service'])) : '';
    }
}
