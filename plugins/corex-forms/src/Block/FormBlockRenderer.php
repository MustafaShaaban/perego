<?php

/**
 * @package Corex\Forms
 */

declare(strict_types=1);

namespace Corex\Forms\Block;

defined('ABSPATH') || exit;

use Corex\Blocks\BlockRenderer;
use Corex\Forms\FormRegistry;
use Corex\Forms\Schema\SchemaExporter;
use Corex\Forms\Schema\SchemaResolver;
use Corex\Forms\Submission\FormSubmissionService;

/**
 * Server-renders a registered form from its schema: accessible (label-bound inputs,
 * required markers, an aria-live status), translation-ready, RTL-aware (logical CSS,
 * applied via the block's stylesheet — no inline styles), and carrying the REST nonce
 * + honeypot the secured endpoint expects. Unknown slug → empty output (non-fatal).
 */
final class FormBlockRenderer implements BlockRenderer
{
    public function __construct(
        private readonly FormRegistry $forms,
        private readonly SchemaResolver $resolver,
        private readonly SchemaExporter $exporter,
        private readonly FieldRenderer $fieldRenderer,
        private readonly ?FlowBlockRenderer $flowRenderer = null,
    ) {
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public function render(array $attributes, string $content, object $block): string
    {
        // block.json declares `flowId`/`flowSlug` defaults (0 / ""), so `isset()` is always true
        // once WordPress merges those defaults in — which would route every block, including a
        // legacy `formSlug` form, to the flow renderer and produce empty output. Route to the flow
        // renderer only when a flow is actually referenced; otherwise fall through to the form path.
        $referencesFlow = ((int) ($attributes['flowId'] ?? 0)) > 0
            || trim((string) ($attributes['flowSlug'] ?? '')) !== '';
        if ($this->flowRenderer !== null && $referencesFlow) {
            return $this->flowRenderer->render(
                [...$attributes, 'variant' => 'form'],
                $content,
                $block,
            );
        }

        $slug = isset($attributes['formSlug']) ? sanitize_key((string) $attributes['formSlug']) : '';
        $form = $this->forms->find($slug);

        if ($form === null) {
            return '';
        }

        // Conditional load (Principle VI): the shared runtime + its styles enqueue only
        // here, where a form actually renders — never globally. The runtime drives the
        // submit lifecycle and auto-binds this form (spec 043).
        wp_enqueue_script('corex-runtime');
        wp_enqueue_style('corex-runtime');

        // Resolve the schema once: it both renders the fields and is exported to the
        // client so JS validates against the SAME definition the server enforces.
        $schema = $this->resolver->resolve($form->fields());

        $fields = '';

        foreach ($schema as $field) {
            $fields .= $this->fieldRenderer->render($slug, $field);
        }

        return sprintf(
            '<form class="corex-form" method="post" data-corex-form="%1$s" data-corex-endpoint="%2$s"'
            . ' data-corex-nonce="%3$s" data-corex-success="%4$s" data-corex-error="%5$s" data-corex-schema="%6$s"'
            . ' data-corex-messages="%7$s">'
            . '%8$s'
            . '<input type="text" name="%9$s" class="corex-form__hp" tabindex="-1" autocomplete="off" aria-hidden="true" value="" />'
            . '<button type="submit" class="corex-form__submit">%10$s</button>'
            . '<p class="corex-form__status" role="status" aria-live="polite"></p>'
            . '</form>',
            esc_attr($slug),
            esc_url(rest_url('corex/v1/forms/' . $slug)),
            esc_attr(wp_create_nonce('wp_rest')),
            esc_attr__('Thank you — your message has been sent.', 'corex'),
            esc_attr__('Please review the highlighted fields and try again.', 'corex'),
            esc_attr((string) wp_json_encode($this->exporter->toArray($schema))),
            esc_attr((string) wp_json_encode(self::validationMessages())),
            $fields,
            esc_attr(FormSubmissionService::HONEYPOT_KEY),
            esc_html__('Send', 'corex'),
        );
    }

    /**
     * The per-rule messages the client renders, keyed by the rule key the validator returns.
     *
     * These live here, in PHP, rather than in the runtime's own table because that table goes
     * through `wp.i18n`, which needs a per-domain JS translation file to be built and shipped. Where
     * none exists — and none did — every message stays English however the page is localized, so an
     * Arabic visitor got English validation errors on an otherwise Arabic form. Rendered through
     * `__()` they pick up the site's existing translations with nothing new to build.
     *
     * @return array<string,string>
     */
    private static function validationMessages(): array
    {
        return [
            'required' => __('This field is required.', 'corex'),
            'email' => __('Enter a valid email address.', 'corex'),
            'numeric' => __('Enter a number.', 'corex'),
            'url' => __('Enter a valid link.', 'corex'),
            'phone' => __('Enter a phone number including its country code.', 'corex'),
            'max' => __('This value is too long.', 'corex'),
            'min' => __('This value is too short.', 'corex'),
            'max_length' => __('This value is too long.', 'corex'),
            'min_length' => __('This value is too short.', 'corex'),
            'max_words' => __('This message is too long.', 'corex'),
            'pattern' => __('This value is not in the expected format.', 'corex'),
        ];
    }
}
