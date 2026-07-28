<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Email;

defined('ABSPATH') || exit;

/**
 * The three mailboxes Perego sends from, and the policy for choosing between them.
 *
 * FluentSMTP routes on the From address: each of these is a configured connection, so the address a
 * message carries decides which authenticated mailbox actually relays it. Until now every email left
 * as one global sender, which meant an automated internal alert and a visitor's confirmation were
 * indistinguishable to both the relay and the reader.
 *
 * The split:
 *   - {@see self::INFO}    — visitor-facing confirmations. A real, monitored mailbox, because a
 *                            person who just wrote to us may well answer the confirmation.
 *   - {@see self::NOREPLY} — machine-generated internal mail (team notifications, comment alerts).
 *                            Nobody should ever answer these, and the address says so.
 *   - {@see self::CONTACT} — a human-typed reply from the Submissions inbox. A person wrote it, so
 *                            the client's answer has to land somewhere a person reads.
 *
 * `Reply-To` is {@see self::NOREPLY} everywhere **except** the internal notification, which keeps the
 * submitter so the team can answer a client by hitting Reply. Visitor mail used to carry the
 * visitor's own address as Reply-To — replying to a confirmation replied to yourself.
 */
final class PeregoMailbox
{
    public const INFO = 'info@peregoads.com';

    public const CONTACT = 'contact@peregoads.com';

    public const NOREPLY = 'noreply@peregoads.com';

    /** Template => sending mailbox. Anything unlisted falls back to the configured global sender. */
    private const SENDERS = [
        'contact-confirmation' => self::INFO,
        'project-brief-confirmation' => self::INFO,
        'join-confirmation' => self::INFO,
        'reply' => self::CONTACT,
        'admin-notification' => self::NOREPLY,
        'comment-notification' => self::NOREPLY,
    ];

    /** The mailbox a template sends from, or '' to leave the configured sender in place. */
    public static function sender(string $template): string
    {
        return self::SENDERS[$template] ?? '';
    }
}
