<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Forms;

defined('ABSPATH') || exit;

use Corex\Forms\Form;
use Corex\Forms\Listeners\StoreSubmissionListener;
use PeregoSite\PostTypes\ServicePostType;
use WP_Post;

/**
 * The "Start a Project" / project-brief form (spec Phase 7, form 2) — the contact-page brief.
 * Ported field-for-field from the handoff `contact.html`: full name, e-mail, phone, company,
 * estimated budget, subject, message, plus the service chooser. The chooser queries the service
 * content type (current-language, via Polylang's query filter) and honours `?service=` preselection
 * so a service page's "start a project" CTA arrives with that service picked. Client + server
 * validation, honeypot/captcha, storage, and email notification are all handled by the shared
 * CoreX Forms engine that renders this schema.
 */
final class ProjectBriefForm extends Form
{
    public const SLUG = 'perego-project-brief';

    /** Estimated-budget ranges, value => (translatable) label. Values mirror the handoff exactly. */
    private const BUDGET_VALUES = ['under-1k', '1k-5k', '5k-15k', '15k-plus', 'not-sure'];

    public string $slug = self::SLUG;

    public function label(): string
    {
        return __('Start a project', 'perego-site');
    }

    /** Storage only; PeregoFormMailListener sends the branded notification. See QuickMessageForm. */
    public function listeners(): array
    {
        return [StoreSubmissionListener::class];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function fields(): array
    {
        return [
            'name' => [
                'type' => 'text',
                'width' => 'half',
                'rules' => ['required', 'min:2', 'max:80'],
                'label' => __('Full name', 'perego-site'),
                'placeholder' => __('Put your name here', 'perego-site'),
            ],
            'email' => [
                'type' => 'email',
                'width' => 'half',
                'rules' => ['required', 'email', 'max:120'],
                'label' => __('E-mail', 'perego-site'),
                'placeholder' => __('Put your E-mail here', 'perego-site'),
            ],
            'phone' => [
                'type' => 'phone',
                // E.164, not a character count. `max:24` accepted "01016999700" — a number nobody
                // outside Egypt can dial, which for a studio serving EG/SA/AE is the whole point of
                // asking (client report 2026-07-27). The country picker in
                // contact-service-chooser/view.js makes supplying the code a choice, not typing.
                //
                // Full width, because this is the form's only *composite* control — the picker and
                // the number are two controls in one field, and a half-width slot sized for one left
                // the number ~151px for a ~190px placeholder, clipping the hint at every desktop
                // width (client report 2026-07-28).
                'rules' => ['phone'],
                'label' => __('Phone', 'perego-site'),
                'placeholder' => __('Best number to reach you', 'perego-site'),
            ],
            'company' => [
                'type' => 'text',
                'width' => 'half',
                'rules' => ['max:80'],
                'label' => __('Company', 'perego-site'),
                'placeholder' => __('Your brand or company', 'perego-site'),
            ],
            'budget' => [
                'type' => 'select',
                'width' => 'half',
                'rules' => [],
                'label' => __('Estimated budget', 'perego-site'),
                'options' => $this->budgetOptions(),
            ],
            'subject' => [
                // Full width, because phone took a row of its own and left five halves — one of
                // them would have been stranded beside a gap. A free-text line is also the better
                // candidate of the five for the extra room.
                'type' => 'text',
                'rules' => ['required', 'min:3', 'max:120'],
                'label' => __('Message Subject', 'perego-site'),
                'placeholder' => __('What is this about?', 'perego-site'),
            ],
            'message' => [
                'type' => 'textarea',
                // 200 words ≈ the original 1200-character cap at typical prose density — a word
                // budget reads more meaningfully than a character count (spec 020 round 4).
                // `data-max-words` is the live counter's (contact-service-chooser/view.js) source of
                // truth for the limit.
                'rules' => ['required', 'min:10', 'max_words:200'],
                'label' => __('Your message', 'perego-site'),
                'placeholder' => __('Tell us about your project.', 'perego-site'),
                'attrs' => ['data-max-words' => '200'],
            ],
            'services' => [
                'type' => 'multi-select',
                'rules' => ['required'],
                'label' => __('Choose your service', 'perego-site'),
                'options' => $this->serviceOptions(),
                'default_value' => $this->preselectedServices(),
            ],
        ];
    }

    /**
     * The estimated-budget ranges, value => translated label (values verbatim from the handoff).
     *
     * @return array<string,string>
     */
    private function budgetOptions(): array
    {
        $labels = [
            '' => __('Select a range', 'perego-site'),
            'under-1k' => __('Under $1,000', 'perego-site'),
            '1k-5k' => __('$1,000 – $5,000', 'perego-site'),
            '5k-15k' => __('$5,000 – $15,000', 'perego-site'),
            '15k-plus' => __('$15,000+', 'perego-site'),
            'not-sure' => __('Not sure yet', 'perego-site'),
        ];

        $options = [];
        foreach (array_merge([''], self::BUDGET_VALUES) as $value) {
            $options[$value] = $labels[$value];
        }

        return $options;
    }

    /**
     * Service choices, keyed by the canonical service slug the `/services/<slug>` routes and the
     * `?service=` param use. Queried from the published services in the current language (bounded,
     * uncounted); falls back to the four fixed services before any are published/translated.
     *
     * @return array<string,string>
     */
    private function serviceOptions(): array
    {
        $options = [];

        if (function_exists('get_posts')) {
            /** @var list<WP_Post> $services */
            $services = get_posts([
                'post_type' => ServicePostType::POST_TYPE,
                'numberposts' => 20,
                'post_status' => 'publish',
                'orderby' => 'menu_order title',
                'order' => 'ASC',
                'suppress_filters' => false,
            ]);

            foreach ($services as $service) {
                $meta = get_post_meta($service->ID, '_perego_service_slug', true);
                $slug = is_string($meta) && $meta !== '' ? $meta : $service->post_name;
                $options[$slug] = get_the_title($service);
            }
        }

        if ($options === []) {
            foreach (ServicePostType::SERVICES as $slug => $name) {
                $options[$slug] = $name;
            }
        }

        return $options;
    }

    /**
     * The `?service=` preselection, whitelisted against the real service options so an unknown or
     * spoofed value never selects anything. Returns a list for the multi-select default.
     *
     * @return list<string>
     */
    private function preselectedServices(): array
    {
        $requested = isset($_GET['service']) ? sanitize_key(wp_unslash((string) $_GET['service'])) : '';

        if ($requested === '') {
            return [];
        }

        return array_key_exists($requested, $this->serviceOptions()) ? [$requested] : [];
    }
}
