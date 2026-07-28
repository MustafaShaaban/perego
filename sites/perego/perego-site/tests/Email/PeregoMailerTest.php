<?php

/**
 * The one place that decides which mailbox an email leaves from and who may answer it.
 *
 * FluentSMTP routes on the From address, so the sender is not cosmetic: it selects which
 * authenticated connection relays the message. Everything used to leave as one global address, and
 * confirmations carried the visitor's own address as Reply-To — replying to a confirmation replied
 * to yourself (client report 2026-07-27).
 *
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use Corex\Mail\Mailer;
use Corex\Mail\MailRequest;
use PeregoSite\Email\PeregoEmailRenderer;
use PeregoSite\Email\PeregoMailbox;
use PeregoSite\Email\PeregoMailer;

function mailerWithSpy(): array
{
    $spy = new class () implements Mailer {
        /** @var list<MailRequest> */
        public array $sent = [];

        public function send(MailRequest $request): void
        {
            $this->sent[] = $request;
        }
    };

    return [new PeregoMailer($spy, new PeregoEmailRenderer('https://perego.local', '')), $spy];
}

beforeEach(function () {
    Functions\when('sanitize_email')->returnArg();
    Functions\when('is_email')->alias(static fn (string $v): bool => str_contains($v, '@'));
});

it('sends visitor-facing confirmations from the monitored mailbox', function (string $template) {
    [$mailer, $spy] = mailerWithSpy();
    $mailer->send($template, 'en', 'a@b.com', ['name' => 'A']);

    // A person who just wrote to us may well answer the confirmation, so this has to be an
    // address somebody reads.
    expect($spy->sent[0]->from)->toBe(PeregoMailbox::INFO);
})->with(['contact-confirmation', 'project-brief-confirmation', 'join-confirmation']);

it('sends automated internal mail from the no-reply mailbox', function (string $template) {
    [$mailer, $spy] = mailerWithSpy();
    $mailer->send($template, 'en', 'team@peregoads.com', ['form_name' => 'Contact']);

    expect($spy->sent[0]->from)->toBe(PeregoMailbox::NOREPLY);
})->with(['admin-notification', 'comment-notification']);

it('sends a human-typed reply from the contact mailbox', function () {
    [$mailer, $spy] = mailerWithSpy();
    $mailer->send('reply', 'en', 'a@b.com', ['subject' => 'Hi', 'body' => '<p>x</p>']);

    // A person wrote it, so the client's answer has to land somewhere a person reads.
    expect($spy->sent[0]->from)->toBe(PeregoMailbox::CONTACT);
});

it('defaults Reply-To to no-reply rather than leaving it unset', function () {
    [$mailer, $spy] = mailerWithSpy();
    $mailer->send('contact-confirmation', 'en', 'a@b.com', ['name' => 'A']);

    // null would fall through to whatever the global mail config happens to hold — a policy this
    // site does not control. Stating it here makes the header deterministic.
    expect($spy->sent[0]->replyTo)->toBe(PeregoMailbox::NOREPLY);
});

it('keeps an explicit Reply-To, which is how the team answers a client', function () {
    [$mailer, $spy] = mailerWithSpy();
    $mailer->send('admin-notification', 'en', 'team@peregoads.com', ['form_name' => 'Contact'], 'client@example.com');

    expect($spy->sent[0]->replyTo)->toBe('client@example.com');
});

it('substitutes no-reply for a malformed Reply-To instead of forwarding it', function () {
    [$mailer, $spy] = mailerWithSpy();
    $mailer->send('admin-notification', 'en', 'team@peregoads.com', ['form_name' => 'Contact'], 'not-an-address');

    expect($spy->sent[0]->replyTo)->toBe(PeregoMailbox::NOREPLY);
});

it('sends nothing at all when the recipient is not a usable address', function () {
    [$mailer, $spy] = mailerWithSpy();

    expect($mailer->send('contact-confirmation', 'en', 'nope', ['name' => 'A']))->toBeFalse()
        ->and($spy->sent)->toBe([]);
});

it('reports no outcome, yet still delivers, on the outcome-free mailer seam', function () {
    [$mailer, $spy] = mailerWithSpy();

    // The bound mailer implements AttemptingMailer in every real configuration; this covers the
    // older seam, where the mail must still go out even though nothing can be said about it.
    expect($mailer->attempt('reply', 'en', 'a@b.com', ['subject' => 'Hi', 'body' => '<p>x</p>']))->toBeNull()
        ->and($spy->sent)->toHaveCount(1);
});
