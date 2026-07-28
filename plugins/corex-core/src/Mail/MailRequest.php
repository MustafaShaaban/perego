<?php

/**
 * @package Corex
 */

declare(strict_types=1);

namespace Corex\Mail;

defined('ABSPATH') || exit;

/**
 * A primitive, transport-neutral mail request. It carries only scalars and arrays
 * so corex-core (and any consumer, e.g. the Forms add-on) can describe an email
 * without depending on the Corex Mail engine's concrete types. The bound Mailer
 * implementation turns it into a real, validated message.
 */
final class MailRequest
{
    /** @var list<string> */
    public readonly array $to;

    public readonly ?string $templateName;

    /** @var array<string,mixed> */
    public readonly array $context;

    public readonly ?string $subject;
    public readonly ?string $body;
    public readonly ?string $replyTo;
    public readonly string $requestId;
    public readonly ?string $parentAttemptId;

    /**
     * The sender address for this one message, overriding the configured `mail.from.address`.
     *
     * A site may legitimately send from more than one mailbox — a visitor confirmation from a
     * monitored address, an internal alert from a no-reply one — and SMTP relays commonly route on
     * the From address. A single global setting cannot express that. Null keeps the configured
     * identity, so every existing caller is unaffected.
     */
    public readonly ?string $from;

    /**
     * @param list<string>        $to
     * @param array<string,mixed> $context merge data for $templateName
     */
    public function __construct(
        array $to,
        ?string $templateName = null,
        array $context = [],
        ?string $subject = null,
        ?string $body = null,
        ?string $replyTo = null,
        ?string $requestId = null,
        ?string $parentAttemptId = null,
        ?string $from = null,
    ) {
        $this->to              = $to;
        $this->templateName    = $templateName;
        $this->context         = $context;
        $this->subject         = $subject;
        $this->body            = $body;
        $this->replyTo         = $replyTo;
        $this->requestId       = $requestId ?? \Corex\Support\Uuid::v4();
        $this->parentAttemptId = $parentAttemptId;
        $this->from            = $from;

        foreach ([$this->requestId, $this->parentAttemptId] as $id) {
            if ($id !== null && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $id) !== 1) {
                throw new \InvalidArgumentException('Mail correlation IDs must be version 4 UUIDs.');
            }
        }
    }
}
