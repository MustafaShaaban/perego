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
use PeregoSite\Email\PeregoMailer;

/** A capturing Mailer double — records the MailRequest the listener produces. */
final class SpyMailer implements Mailer
{
    public ?MailRequest $last = null;

    public function send(MailRequest $request): void
    {
        $this->last = $request;
    }
}

function listenerWithSpy(): array
{
    $spy = new SpyMailer();
    $mailer = new PeregoMailer($spy, new PeregoEmailRenderer('https://perego.local', ''));
    $listener = new PeregoFormMailListener($mailer);

    return [$listener, $spy];
}

beforeEach(function () {
    Functions\when('sanitize_email')->returnArg();
    Functions\when('is_email')->justReturn(true);
    Functions\when('get_locale')->justReturn('en_US');
    // pll_current_language intentionally undefined -> falls back to get_locale.
});

it('sends the contact confirmation for a quick-message submission', function () {
    [$listener, $spy] = listenerWithSpy();

    $listener(new FormSubmittedEvent('perego-quick-message', [
        'name' => 'Sara', 'email' => 'sara@example.com', 'message' => 'Hello',
    ]));

    expect($spy->last)->not->toBeNull()
        ->and($spy->last->to)->toBe(['sara@example.com'])
        ->and($spy->last->subject)->toBe('Thanks for reaching out — Perego')
        ->and($spy->last->body)->toContain('Thank you, Sara!')
        ->and($spy->last->replyTo)->toBe('sara@example.com');
});

it('maps the brief: humanizes service slugs and resolves the budget label', function () {
    [$listener, $spy] = listenerWithSpy();

    $listener(new FormSubmittedEvent('perego-project-brief', [
        'name' => 'Ali', 'email' => 'ali@example.com',
        'services' => ['video-editing', 'graphic-design'],
        'budget' => '5k-15k', 'subject' => 'Launch', 'message' => 'A brand film',
    ]));

    expect($spy->last->subject)->toBe('We got your project brief — Perego')
        ->and($spy->last->body)->toContain('Video Editing, Graphic Design')
        ->and($spy->last->body)->toContain('$5,000 – $15,000');
});

it('ignores submissions with no email', function () {
    [$listener, $spy] = listenerWithSpy();

    $listener(new FormSubmittedEvent('perego-quick-message', ['name' => 'NoEmail']));

    expect($spy->last)->toBeNull();
});

it('ignores form slugs it does not own', function () {
    [$listener, $spy] = listenerWithSpy();

    $listener(new FormSubmittedEvent('some-other-form', ['email' => 'x@example.com']));

    expect($spy->last)->toBeNull();
});
