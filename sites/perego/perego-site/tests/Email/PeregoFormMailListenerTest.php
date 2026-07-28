<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use Corex\Forms\Submission\FormSubmittedEvent;
use Corex\Mail\Mailer;
use Corex\Mail\MailRequest;
use PeregoSite\Email\PeregoEmailRenderer;
use PeregoSite\Email\PeregoFormMailListener;
use PeregoSite\Email\PeregoMailbox;
use PeregoSite\Email\PeregoMailer;
use PeregoSite\Email\TeamRecipient;

/**
 * A capturing Mailer double. It records EVERY request, not just the last: one submission now produces
 * two emails — the submitter's confirmation and the team's branded notification — and a spy that kept
 * only the newest would silently hide whichever one regressed.
 */
final class SpyMailer implements Mailer
{
    /** @var list<MailRequest> */
    public array $sent = [];

    public function send(MailRequest $request): void
    {
        $this->sent[] = $request;
    }

    public function confirmation(): ?MailRequest
    {
        return $this->sent[0] ?? null;
    }

    public function notification(): ?MailRequest
    {
        return $this->sent[1] ?? null;
    }
}

function listenerWithSpy(): array
{
    $spy = new SpyMailer();
    $mailer = new PeregoMailer($spy, new PeregoEmailRenderer('https://perego.local', ''));
    $listener = new PeregoFormMailListener($mailer, new TeamRecipient());

    return [$listener, $spy];
}

beforeEach(function () {
    Functions\when('sanitize_email')->returnArg();
    Functions\when('is_email')->justReturn(true);
    Functions\when('get_locale')->justReturn('en_US');
    Functions\when('current_time')->justReturn('2026-07-27 12:00:00');
    Functions\when('get_option')->justReturn('team@peregoads.com');
    // pll_current_language intentionally undefined -> falls back to get_locale.
});

it('sends the contact confirmation for a quick-message submission', function () {
    [$listener, $spy] = listenerWithSpy();

    $listener(new FormSubmittedEvent('perego-quick-message', [
        'name' => 'Sara', 'email' => 'sara@example.com', 'message' => 'Hello',
    ]));

    expect($spy->confirmation())->not->toBeNull()
        ->and($spy->confirmation()->to)->toBe(['sara@example.com'])
        ->and($spy->confirmation()->subject)->toBe('Thanks for reaching out — Perego')
        ->and($spy->confirmation()->body)->toContain('Thank you, Sara!')
        // Not the submitter: this email goes TO them, so their own address as Reply-To made
        // "Reply" answer themselves. Sent from the monitored mailbox, per the sender policy.
        ->and($spy->confirmation()->replyTo)->toBe(PeregoMailbox::NOREPLY)
        ->and($spy->confirmation()->from)->toBe(PeregoMailbox::INFO);
});

it('does not echo the submitter their own address back', function () {
    [$listener, $spy] = listenerWithSpy();

    $listener(new FormSubmittedEvent('perego-quick-message', [
        'name' => 'Sara', 'email' => 'sara@example.com', 'message' => 'Hello',
    ]));

    expect($spy->confirmation()->body)->not->toContain('Your email')
        ->and($spy->confirmation()->body)->not->toContain('sara@example.com');
});

it('maps the brief: humanizes service slugs and resolves the budget label', function () {
    [$listener, $spy] = listenerWithSpy();

    $listener(new FormSubmittedEvent('perego-project-brief', [
        'name' => 'Ali', 'email' => 'ali@example.com',
        'services' => ['video-editing', 'graphic-design'],
        'budget' => '5k-15k', 'subject' => 'Launch', 'message' => 'A brand film',
    ]));

    expect($spy->confirmation()->subject)->toBe('We got your project brief — Perego')
        ->and($spy->confirmation()->body)->toContain('Video Editing, Graphic Design')
        ->and($spy->confirmation()->body)->toContain('$5,000 – $15,000');
});

it('lists brief contact details as labelled rows, not a trailing run-on line', function () {
    [$listener, $spy] = listenerWithSpy();

    $listener(new FormSubmittedEvent('perego-project-brief', [
        'name' => 'Ali', 'email' => 'ali@example.com', 'phone' => '+20 100', 'company' => 'Acme',
        'services' => '', 'budget' => '', 'subject' => '', 'message' => 'Hi',
    ]));

    $body = $spy->confirmation()->body;

    // "Your details: a • b • c" read as a malformed mail header; each value now says what it is.
    expect($body)->not->toContain('Your details')
        ->and($body)->not->toContain('Reply-to')
        ->and($body)->toContain('Email')
        ->and($body)->toContain('ali@example.com')
        ->and($body)->toContain('Phone')
        ->and($body)->toContain('Company')
        ->and($body)->toContain('Acme');
});

it('omits brief rows whose value the visitor left empty', function () {
    [$listener, $spy] = listenerWithSpy();

    $listener(new FormSubmittedEvent('perego-project-brief', [
        'name' => 'Ali', 'email' => 'ali@example.com', 'phone' => '', 'company' => '',
        'services' => '', 'budget' => '', 'subject' => '', 'message' => 'Hi',
    ]));

    // The confirmation is a receipt for the visitor, so a blank row is noise — unlike the team
    // notification, where "—" tells the reader the field was asked for and skipped.
    expect($spy->confirmation()->body)->not->toContain('Phone')
        ->and($spy->confirmation()->body)->not->toContain('Company');
});

it('also sends the team a branded notification, replying to the submitter', function () {
    [$listener, $spy] = listenerWithSpy();

    $listener(new FormSubmittedEvent('perego-quick-message', [
        'name' => 'Sara', 'email' => 'sara@example.com', 'message' => 'Hello',
    ]));

    expect($spy->sent)->toHaveCount(2)
        ->and($spy->notification()->to)->toBe(['team@peregoads.com'])
        ->and($spy->notification()->subject)->toBe('New Contact submission — Perego')
        // The branded shell, not the engine's `label: value` plain text.
        ->and($spy->notification()->body)->toContain('<!DOCTYPE')
        ->and($spy->notification()->body)->toContain('Sara')
        ->and($spy->notification()->body)->toContain('sara@example.com')
        // The one email that keeps the submitter as Reply-To: the team answers a client by
        // hitting Reply. It leaves from the no-reply mailbox, because nobody answers the platform.
        ->and($spy->notification()->replyTo)->toBe('sara@example.com')
        ->and($spy->notification()->from)->toBe(PeregoMailbox::NOREPLY);
});

it('signs the team notification as the platform, not as the team', function () {
    [$listener, $spy] = listenerWithSpy();

    $listener(new FormSubmittedEvent('perego-quick-message', [
        'name' => 'Sara', 'email' => 'sara@example.com', 'message' => 'Hello',
    ]));

    expect($spy->notification()->body)->toContain('Best regards,')
        ->and($spy->notification()->body)->toContain('System Notification Service')
        ->and($spy->notification()->body)->toContain('Perego Web Platform')
        ->and($spy->notification()->body)->not->toContain('The Perego Team');
});

it('renders every service a visitor picked, not just the first', function () {
    [$listener, $spy] = listenerWithSpy();

    $listener(new FormSubmittedEvent('perego-project-brief', [
        'name' => 'Ali', 'email' => 'ali@example.com',
        'services' => ['video-editing', 'graphic-design', 'motion-graphics'],
        'budget' => '', 'subject' => '', 'message' => 'Hi',
    ]));

    // A multi-select used to reach here as one slug (the browser reported only the first selected
    // option), so both the confirmation and the notification under-reported the brief.
    foreach ([$spy->confirmation()->body, $spy->notification()->body] as $body) {
        expect($body)->toContain('Video Editing')
            ->and($body)->toContain('Graphic Design')
            ->and($body)->toContain('Motion Graphics');
    }
});

it('gives the team every brief field, marking the empty optional ones', function () {
    [$listener, $spy] = listenerWithSpy();

    $listener(new FormSubmittedEvent('perego-project-brief', [
        'name' => 'Ali', 'email' => 'ali@example.com', 'company' => 'Acme',
        'services' => ['video-editing'], 'budget' => '5k-15k', 'subject' => 'Launch', 'message' => 'Film',
    ]));

    $body = $spy->notification()->body;

    expect($spy->notification()->subject)->toBe('New Project brief submission — Perego')
        ->and($body)->toContain('Acme')
        ->and($body)->toContain('Video Editing')
        ->and($body)->toContain('$5,000 – $15,000')
        ->and($body)->toContain('Launch')
        // Phone was not supplied; the row is still present so the reader knows it was asked for.
        ->and($body)->toContain('Phone')
        ->and($body)->toContain('—');
});

it('ignores submissions with no email', function () {
    [$listener, $spy] = listenerWithSpy();

    $listener(new FormSubmittedEvent('perego-quick-message', ['name' => 'NoEmail']));

    expect($spy->sent)->toBe([]);
});

it('ignores form slugs it does not own', function () {
    [$listener, $spy] = listenerWithSpy();

    $listener(new FormSubmittedEvent('some-other-form', ['email' => 'x@example.com']));

    expect($spy->sent)->toBe([]);
});
