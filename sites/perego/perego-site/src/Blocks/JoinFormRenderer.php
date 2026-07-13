<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Content\GlobalContent;

/**
 * Server-renders the perego-theme/join-form block (spec Phase 7): the footer "Join us" / CV form —
 * full name, e-mail, portfolio link, and a CV upload (pdf/doc/docx ≤ 10 MB). Accessible (label-bound
 * inputs, required markers, an aria-live status region, described-by constraints), language-aware,
 * and carrying the honeypot + endpoint the secured `perego/v1/careers/apply` route expects. The
 * upload lifecycle + every handoff state is driven by view.js; the markup is present without JS.
 */
final class JoinFormRenderer
{
    public function __construct(private readonly GlobalContent $content)
    {
    }

    public function render(): string
    {
        $t = $this->strings();
        $endpoint = esc_url(rest_url('perego/v1/careers/apply'));
        $nonce = esc_attr(wp_create_nonce('wp_rest'));

        // The localized state messages view.js renders into the aria-live status region.
        $messages = [
            'uploading' => $t['uploading'],
            'submitting' => $t['submitting'],
            'success' => $t['success'],
            'invalid' => $t['invalid'],
            'wrong_type' => $t['wrong_type'],
            'too_large' => $t['too_large'],
            'rate_limit' => $t['rate_limit'],
            'server_error' => $t['server_error'],
        ];

        $html = '<form class="footer-form join-form" method="post" enctype="multipart/form-data"'
            . ' data-perego-join data-endpoint="' . $endpoint . '" data-nonce="' . $nonce . '"'
            . ' data-max-bytes="10485760" data-messages="' . esc_attr((string) wp_json_encode($messages)) . '"'
            . ' aria-describedby="join-form-status" novalidate>';

        $html .= $this->field('jf-name', 'name', 'text', $t['name'], true, ['autocomplete' => 'name', 'maxlength' => '80']);
        $html .= $this->field('jf-email', 'email', 'email', $t['email'], true, ['autocomplete' => 'email', 'maxlength' => '120']);
        $html .= $this->field('jf-portfolio', 'portfolio', 'url', $t['portfolio'], false, ['inputmode' => 'url', 'placeholder' => 'https://']);

        // CV file input + accessible constraint hint.
        $html .= '<div class="field join-form__field">';
        $html .= '<label for="jf-cv">' . esc_html($t['cv']) . ' <span class="join-form__req" aria-hidden="true">*</span></label>';
        $html .= '<label class="file-drop" for="jf-cv"><span class="file-drop__text">' . esc_html($t['cvHint']) . '</span>';
        $html .= '<input type="file" id="jf-cv" name="cv" accept=".pdf,.doc,.docx" required aria-describedby="jf-cv-hint" /></label>';
        $html .= '<p class="join-form__hint" id="jf-cv-hint">' . esc_html($t['cvHint']) . '</p>';
        $html .= '</div>';

        // Honeypot (visually hidden, ignored by real users).
        $html .= '<input type="text" name="perego_hp" class="join-form__hp" tabindex="-1" autocomplete="off" aria-hidden="true" value="" />';

        $html .= '<button type="submit" class="btn btn--accent footer-form__submit join-form__submit">' . esc_html($t['submit']) . '</button>';
        $html .= '<p class="join-form__status" id="join-form-status" role="status" aria-live="polite"></p>';
        $html .= '</form>';

        return $html;
    }

    /** @param array<string,string> $attrs */
    private function field(string $id, string $name, string $type, string $label, bool $required, array $attrs = []): string
    {
        $extra = '';
        foreach ($attrs as $k => $v) {
            $extra .= ' ' . esc_attr($k) . '="' . esc_attr($v) . '"';
        }
        $req = $required ? ' required' : '';
        $mark = $required ? ' <span class="join-form__req" aria-hidden="true">*</span>' : '';

        return '<div class="field join-form__field">'
            . '<label for="' . esc_attr($id) . '">' . esc_html($label) . $mark . '</label>'
            . '<input type="' . esc_attr($type) . '" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '"' . $req . $extra . ' />'
            . '</div>';
    }

    /** @return array<string,string> */
    private function strings(): array
    {
        return $this->content->join();
    }
}
