<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Email;

defined('ABSPATH') || exit;

use Corex\Mail\AttemptingMailer;
use Corex\Mail\Mailer;
use Corex\Mail\MailRequest;
use Corex\Mail\MailResult;

/**
 * Sends Perego's branded transactional emails (spec Phase 7/8) through the CoreX Mailer seam:
 * PeregoEmailRenderer produces the exact HTML (+ plain-text) for the requested template/locale, and
 * this hands it to the engine as a ready-rendered MailRequest (raw subject + body, no CoreX template/
 * Layout), so the delivered email is the exact handoff design. Non-fatal: a missing recipient or an
 * absent mailer is a no-op, never an error on the request path.
 *
 * Sender and Reply-To are decided here, from the template alone (see {@see PeregoMailbox}), rather
 * than at each call site — there is one mail policy for the site, so there is one place that knows it.
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
     * @param string $replyTo an address the recipient should answer; empty means "nobody answers
     *                        this", which is Reply-To: noreply@ rather than no header at all
     */
    public function send(string $template, string $locale, string $to, array $ctx, string $replyTo = ''): bool
    {
        $request = $this->request($template, $locale, $to, $ctx, $replyTo);
        if ($request === null) {
            return false;
        }

        $this->mailer->send($request);

        return true;
    }

    /**
     * Send and report the delivery outcome, for callers that must answer an operator (the Submissions
     * inbox reply). Null when the recipient is unusable, or when the bound mailer is the older
     * outcome-free seam — the mail still goes out in that case, it just cannot be reported on.
     *
     * @param array<string,string> $ctx merge values for the template
     */
    public function attempt(string $template, string $locale, string $to, array $ctx, string $replyTo = ''): ?MailResult
    {
        $request = $this->request($template, $locale, $to, $ctx, $replyTo);
        if ($request === null) {
            return null;
        }

        if (! $this->mailer instanceof AttemptingMailer) {
            $this->mailer->send($request);

            return null;
        }

        return $this->mailer->attempt($request);
    }

    /**
     * The rendered request, with this site's sender and Reply-To policy applied. Null when the
     * recipient is not a usable address.
     *
     * @param array<string,string> $ctx
     */
    private function request(string $template, string $locale, string $to, array $ctx, string $replyTo): ?MailRequest
    {
        $to = sanitize_email($to);
        if ($to === '' || ! is_email($to)) {
            return null;
        }

        $rendered = $this->renderer->render($template, $locale, $ctx);

        // A blank/invalid reply-to becomes the no-reply mailbox, never null: null falls through to
        // whatever the global mail config happens to hold, which is a policy we do not control here.
        $reply = $replyTo !== '' && is_email($replyTo) ? $replyTo : PeregoMailbox::NOREPLY;

        $sender = PeregoMailbox::sender($template);

        return new MailRequest(
            to: [$to],
            templateName: null,
            context: [],
            subject: $rendered['subject'],
            body: $rendered['html'],
            replyTo: $reply,
            from: $sender !== '' ? $sender : null,
        );
    }
}
