<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Options;

defined('ABSPATH') || exit;

/**
 * A sample options page (Settings → Perego Example) — capability-gated, nonce-checked on
 * save, escaped on output. Part of the --starter example; delete it per REMOVE-EXAMPLE.md.
 */
final class ExampleOptions
{
    private const OPTION = 'perego_example_note';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_post_perego_save_example', [$this, 'save']);
    }

    public function menu(): void
    {
        add_options_page(
            __('Perego Example', 'perego-site'),
            __('Perego Example', 'perego-site'),
            'manage_options',
            'perego_example',
            [$this, 'render'],
        );
    }

    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $note = (string) get_option(self::OPTION, '');

        echo '<div class="wrap"><h1>' . esc_html__('Perego Example', 'perego-site') . '</h1>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('perego_example');
        echo '<input type="hidden" name="action" value="perego_save_example" />';
        echo '<input type="text" name="note" class="regular-text" value="' . esc_attr($note) . '" />';
        echo '<p><button type="submit" class="button button-primary">'
            . esc_html__('Save', 'perego-site') . '</button></p>';
        echo '</form></div>';
    }

    public function save(): void
    {
        if (! current_user_can('manage_options') || ! check_admin_referer('perego_example')) {
            wp_die(esc_html__('You are not allowed to do that.', 'perego-site'));
        }

        $note = isset($_POST['note']) ? sanitize_text_field(wp_unslash($_POST['note'])) : '';
        update_option(self::OPTION, $note);

        wp_safe_redirect(admin_url('options-general.php?page=perego_example'));
        exit;
    }
}
