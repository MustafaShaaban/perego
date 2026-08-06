<?php

/**
 * @package Corex\Email
 */

declare(strict_types=1);

namespace Corex\Email;

defined('ABSPATH') || exit;

use Corex\Email\Driver\MailDriver;
use Corex\Email\Log\EmailLogStore;
use Corex\Email\Message\EmailMessage;
use Corex\Email\Security\HeaderGuard;
use Corex\Support\BootLogger;
use Throwable;

/**
 * Orchestrates a send: header-injection guard → recipient validation → deliver
 * through the driver → record the outcome. Best-effort and non-fatal — a header
 * rejection, an empty valid-recipient set, or a driver error each short-circuit
 * with a logged result; the service never throws, so a triggering request or event
 * dispatch is never aborted (spec FR-006, FR-007, FR-011).
 */
final class MailService
{
    public function __construct(
        private readonly MailDriver $driver,
        private readonly EmailLogStore $log,
        private readonly HeaderGuard $guard,
        private readonly BootLogger $logger,
    ) {
    }

    public function deliver(EmailMessage $message): EmailResult
    {
        $rejection = $this->guard->inspect([
            'subject'  => $message->subject,
            'reply-to' => $message->replyTo ?? '',
        ]);

        if ($rejection !== null) {
            $this->logger->warning(sprintf('Mail rejected: %s', $rejection));

            return $this->result('rejected', $rejection, $message);
        }

        /*
         * ⚠ FORK DIVERGENCE from upstream v0.41.0 — deliberate, and the only one in this addon.
         *
         * Upstream rebuilds the message here with SEVEN arguments, which silently drops `$from` and
         * `$attachments`. Every send passes through this method, so a sender threaded all the way from
         * `MailRequest` → `EmailMessage` → `MessageBuilder` → `RequestMailer` → the dispatcher arrives
         * at the driver as null. Upstream's own spec-081 attachments feature is dead for the same
         * reason, and Perego's three-mailbox routing (`PeregoMailbox`: info@ / contact@ / noreply@)
         * collapses to the single configured identity with nothing logged. Reported upstream.
         *
         * Named arguments, not positional: this file has to survive upstream reordering the tail of a
         * constructor it owns, and a positional 8th/9th argument would put the wrong value in the wrong
         * slot with no error. Named binding fails loudly instead — which is the behaviour you want in
         * the one place that cannot be allowed to lose data quietly.
         */
        $clean = new EmailMessage(
            $this->valid($message->to),
            $this->valid($message->cc),
            $this->valid($message->bcc),
            $message->replyTo,
            $message->subject,
            $message->body,
            $message->headers,
            from: $message->from,
            attachments: $message->attachments,
        );

        if ($clean->to === []) {
            return $this->result('failed', 'No valid recipient.', $clean);
        }

        try {
            $accepted = $this->driver->send($clean);
        } catch (Throwable $e) {
            $this->logger->error(sprintf('Mail delivery failed: %s', $e->getMessage()));
            $accepted = false;
        }

        return $this->result($accepted ? 'sent' : 'failed', $accepted ? 'Sent.' : 'Delivery failed.', $clean);
    }

    private function result(string $status, string $message, EmailMessage $email): EmailResult
    {
        return new EmailResult($status, $message, $this->log->record($status, $email));
    }

    /**
     * @param list<string> $addresses
     *
     * @return list<string> only the syntactically valid addresses; invalid ones are dropped + logged
     */
    private function valid(array $addresses): array
    {
        $valid = [];

        foreach ($addresses as $address) {
            if (filter_var($address, FILTER_VALIDATE_EMAIL) !== false) {
                $valid[] = $address;
            } else {
                $this->logger->warning(sprintf('Dropped invalid recipient: %s', $address));
            }
        }

        return $valid;
    }
}
