<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Email;

defined('ABSPATH') || exit;

use Corex\Support\Config\ConfigInterface;

/**
 * Where internal mail goes: the CoreX forms recipient when configured, else the site admin.
 *
 * This exists because the two paths disagreed. The forms listener read `forms.email.recipient` — the
 * setting the CoreX admin exposes — while the careers endpoint read `admin_email` directly. On this
 * install `admin_email` is still the WordPress default `admin@example.com`, so every job application
 * was delivered to a mailbox nobody owns while contact and brief mail arrived correctly (client
 * report 2026-07-27: "I am not receiving the careers form"). One resolver, one setting, both paths.
 */
final class TeamRecipient
{
    public function __construct(private readonly ?ConfigInterface $config = null)
    {
    }

    /** The internal recipient, or '' when neither source yields a valid address. */
    public function address(): string
    {
        $configured = $this->config !== null
            ? (string) $this->config->get('forms.email.recipient', '')
            : '';

        $recipient = $configured !== '' ? $configured : (string) get_option('admin_email', '');

        return is_email($recipient) ? $recipient : '';
    }
}
