<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Email;

defined('ABSPATH') || exit;

use Corex\Mail\MailResult;
use Corex\Mail\SubmissionEmailGateway;
use DateTimeImmutable;

/**
 * Puts a Submissions-inbox reply into the Perego design system.
 *
 * The CoreX gateway wraps an operator's reply in the framework's generic brand Layout — a white 600px
 * table with a logo and an accent rule. Correct for a framework default, but it meant a client who
 * received a confirmation in Perego's dark violet shell got the answer to it as plain text on white,
 * looking like it came from somewhere else entirely (client report 2026-07-27). Replies now render
 * through {@see PeregoEmailRenderer}'s `reply` template, the same shell as every other Perego email,
 * and leave from `contact@` so the client's response reaches a monitored mailbox.
 *
 * A decorator, not a replacement: `resend()` must reproduce the *original* Email Studio template
 * byte-for-byte and `log()` reads Studio's attempt records, so both stay with the engine that owns
 * them. Only the one operation whose design we own is overridden.
 */
final class PeregoSubmissionEmailGateway implements SubmissionEmailGateway
{
    public function __construct(
        private readonly SubmissionEmailGateway $inner,
        private readonly PeregoMailer $mailer,
    ) {
    }

    public function reply(string $recipient, string $subject, string $htmlBody): MailResult
    {
        $result = $this->mailer->attempt('reply', $this->locale(), $recipient, [
            'subject' => $subject,
            'body' => $htmlBody,
        ], PeregoMailbox::CONTACT);

        // `AttemptingMailer` is documented as an *optional* extension of the mail seam, so a binding
        // that only implements `Mailer` is a supported configuration, not an impossible one. The mail
        // still went out on that path; `accepted` is the honest state for "handed off, not confirmed".
        return $result ?? new MailResult(
            attemptId: wp_generate_uuid4(),
            requestId: wp_generate_uuid4(),
            state: MailResult::STATE_ACCEPTED,
            provider: 'perego-mail',
            message: 'Reply handed to the mailer.',
            occurredAt: new DateTimeImmutable('now'),
            retryable: false,
        );
    }

    /** @param array<string,mixed> $context */
    public function resend(string $attemptId, string $recipient, array $context): MailResult
    {
        return $this->inner->resend($attemptId, $recipient, $context);
    }

    /** @return array<string,mixed>|null */
    public function log(string $attemptId): ?array
    {
        return $this->inner->log($attemptId);
    }

    /**
     * The recipient's language, as far as the admin can know it: replies are composed in wp-admin, so
     * there is no visitor request to read a language off. The site locale is the honest default.
     */
    private function locale(): string
    {
        return str_starts_with((string) get_locale(), 'ar') ? 'ar' : 'en';
    }
}
