<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

use PeregoSite\Content\HomeContent;
use PeregoSite\Forms\CountryCodes;
use PeregoSite\PostTypes\ServicePostType;
use PeregoSite\Services\LanguageService;

defined('ABSPATH') || exit;

/** Renders the handoff contact-service controls from the current-language service records. */
final class ContactServiceChooserRenderer
{
    /** Shared by the live region and the group's `aria-describedby`, so the two cannot drift. */
    private const ERROR_ID = 'perego-services-error';

    public function __construct(private readonly LanguageService $languageService)
    {
    }

    public function render(): string
    {
        // The chooser is the contact page's one site-owned wrapper around the framework-rendered
        // form, so it is also where the form's client-side data rides in. `view.js` reads both.
        $html = '<div class="contact-choose reveal" data-delay="1" data-perego-service-chooser'
            . ' data-countries="' . esc_attr((string) wp_json_encode(CountryCodes::options($this->locale()))) . '"'
            // Timezone ⇒ ISO, so the picker can preselect the visitor's own country without a
            // geolocation prompt or a third-party lookup. Emitted from PHP so the map and the
            // country list cannot drift apart.
            . ' data-country-zones="' . esc_attr((string) wp_json_encode(CountryCodes::zones())) . '"'
            . ' data-default-country="' . esc_attr(CountryCodes::DEFAULT_ISO) . '"'
            . ' data-phone-label="' . esc_attr__('Country code', 'perego-site') . '"'
            // The framework's per-rule message map only has the generic "This field is required.",
            // which names no field — and this control has no visible label of its own, because the
            // buttons below replaced it. Say what to do instead.
            . ' data-error-required="' . esc_attr__('Please choose at least one service.', 'perego-site') . '"'
            . ' data-words-label="' . esc_attr__('words', 'perego-site') . '">';
        $html .= '<h2 class="section-title">' . esc_html__('Choose your service', 'perego-site') . '</h2>';
        $html .= '<div class="svc-choice-list" role="group" aria-describedby="' . self::ERROR_ID . '"'
            . ' aria-label="' . esc_attr__('Choose your service (select one or more)', 'perego-site') . '">';

        foreach ($this->services() as $slug => $label) {
            $selected = $slug === $this->preselectedService();
            $html .= '<button type="button" class="svc-choice' . ($selected ? ' is-selected' : '') . '"'
                . ' data-service="' . esc_attr($slug) . '" aria-pressed="' . ($selected ? 'true' : 'false') . '">'
                . esc_html($this->handoffLabel($slug, $label)) . '</button>';
        }

        $html .= '</div>';
        // The framework writes its error into the `services` field wrapper, which this page hides
        // (the buttons above are its visible replacement) — so the message landed in a node nobody
        // could see and submitting looked like nothing happened. `view.js` mirrors it into here.
        // No `hidden` attribute: an author `display` rule beats the UA `[hidden]` sheet, so this
        // hides on `:empty` the way the framework's own `.corex-form__error` does.
        $html .= '<p class="svc-choice-error" id="' . self::ERROR_ID . '" role="alert"></p>';
        $html .= '<p class="svc-choice-help">' . esc_html__('Tap to select one or more services. Tap again to deselect.', 'perego-site') . '</p>';
        $html .= '<img class="contact-choose__watermark" src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/logo-full.png') . '" alt="" />';

        return $html . '</div>';
    }

    private function locale(): string
    {
        return $this->languageService->driver()->currentLocale();
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

    /**
     * The contact handoff deliberately uses concise service labels while the service records retain
     * their complete editorial names for headings, SEO, and form submissions. Reuses the home
     * services-teaser's own locale-aware short names (spec 002) so the two never drift apart.
     */
    private function handoffLabel(string $slug, string $fallback): string
    {
        $content = new HomeContent($this->languageService->driver()->currentLocale());

        foreach ($content->services() as $service) {
            if ($service['slug'] === $slug) {
                return $service['name'];
            }
        }

        return $fallback;
    }

    private function preselectedService(): string
    {
        return isset($_GET['service']) ? sanitize_key(wp_unslash((string) $_GET['service'])) : '';
    }
}
