<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use Corex\Mail\Mailer;
use Corex\Mail\MailRequest;
use PeregoSite\Email\PeregoCommentNotifier;
use PeregoSite\Email\PeregoEmailRenderer;
use PeregoSite\Email\PeregoMailer;

if (! class_exists('WP_Comment')) {
    class WP_Comment
    {
        public int $comment_ID = 0;
        public int $comment_post_ID = 0;
        public string $comment_author = '';
        public string $comment_author_email = '';
        public string $comment_content = '';
    }
}
if (! class_exists('WP_Post')) {
    class WP_Post
    {
        public int $ID = 0;
        public string $post_name = '';
    }
}

function commentNotifierWithSpy(): array
{
    $spy = new class implements Mailer {
        public ?MailRequest $last = null;
        public function send(MailRequest $request): void
        {
            $this->last = $request;
        }
    };
    $notifier = new PeregoCommentNotifier(new PeregoMailer($spy, new PeregoEmailRenderer('https://perego.local', '')));

    return [$notifier, $spy];
}

beforeEach(function () {
    Functions\when('sanitize_email')->returnArg();
    Functions\when('is_email')->justReturn(true);
    Functions\when('get_locale')->justReturn('en_US');
    Functions\when('get_option')->justReturn('mod@perego.local');
    Functions\when('get_the_title')->justReturn('My Journal Post');
    Functions\when('get_permalink')->justReturn('https://perego.local/journal/my-post');
    Functions\when('get_comment_date')->justReturn('July 12, 2026');
    Functions\when('admin_url')->alias(fn (string $p) => 'https://perego.local/wp-admin/' . $p);
    Functions\when('wp_nonce_url')->alias(fn (string $u) => $u . '&_wpnonce=abc123');

    $comment = new WP_Comment();
    $comment->comment_ID = 5;
    $comment->comment_post_ID = 9;
    $comment->comment_author = 'Ali';
    $comment->comment_author_email = 'ali@example.com';
    $comment->comment_content = 'Insightful piece.';
    Functions\when('get_comment')->justReturn($comment);
    Functions\when('get_post')->justReturn(new WP_Post());
});

it('emails the moderator the branded comment notification with an approve link', function () {
    [$notifier, $spy] = commentNotifierWithSpy();

    $notifier->onCommentPosted(5, 0);

    expect($spy->last)->not->toBeNull()
        ->and($spy->last->to)->toBe(['mod@perego.local'])
        ->and($spy->last->subject)->toBe('New comment awaiting review — Perego')
        ->and($spy->last->body)->toContain('Ali')
        ->and($spy->last->body)->toContain('Insightful piece.')
        ->and($spy->last->body)->toContain('My Journal Post')
        ->and($spy->last->body)->toContain('approvecomment&amp;c=5');
});

it('skips spam comments', function () {
    [$notifier, $spy] = commentNotifierWithSpy();

    $notifier->onCommentPosted(5, 'spam');

    expect($spy->last)->toBeNull();
});
