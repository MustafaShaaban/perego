<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Email;

defined('ABSPATH') || exit;

use Corex\Mail\Mailer;
use Corex\Mail\MailRequest;

/**
 * Sends Perego's branded transactional emails (spec Phase 7/8) through the CoreX Mailer seam:
 * PeregoEmailRenderer produces the exact HTML (+ plain-text) for the requested template/locale, and
 * this hands it to the engine as a ready-rendered MailRequest (raw subject + body, no CoreX template/
 * Layout), so the delivered email is the exact handoff design. Non-fatal: a missing recipient or an
 * absent mailer is a no-op, never an error on the request path.
 *
 * Note: the CoreX MailRequest/EmailMessage carry a single HTML body (no multipart text part), so the
 * plain-text alternative the renderer produces cannot ride along on this seam yet — tracked as a
 * CoreX Mail enhancement. The HTML is fully email-client-safe on its own.
 */
final class PeregoMailer
{
    public function __construct(
        private readonly Mailer $mailer,
        private readonly PeregoEmailRenderer $renderer,
    ) {
    }

    /**
     * @param array<string,string> $ctx merge values for the template
     */
    public function send(string $template, string $locale, string $to, array $ctx, string $replyTo = ''): bool
    {
        $to = sanitize_email($to);
        if ($to === '' || ! is_email($to)) {
            return false;
        }

        $rendered = $this->renderer->render($template, $locale, $ctx);

        $reply = $replyTo !== '' && is_email($replyTo) ? $replyTo : null;

        $this->mailer->send(new MailRequest(
            to: [$to],
            templateName: null,
            context: [],
            subject: $rendered['subject'],
            body: $rendered['html'],
            replyTo: $reply,
        ));

        return true;
    }
}
