<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\JournalCommentsRenderer;

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_url')->returnArg();
});

// Minimal context; the renderer falls back to defaults for any missing key.
$ctx = [
    'title' => '%d Comments',
    'titleOne' => '%d Comment',
    'reply' => 'Reply',
    'leaveComment' => 'Leave a comment',
    'postId' => 234,
    'endpoint' => 'https://perego.local/wp-json/perego/v1/comments',
    'nonce' => 'abc123',
    'messagesJson' => '{}',
    'commentsPostUrl' => 'https://perego.local/wp-comments-post.php',
];

function comment(string $author, string $text, bool $isReply = false, int $id = 0): array
{
    return ['id' => $id, 'author' => $author, 'date' => 'July 9, 2026', 'text' => $text, 'isReply' => $isReply];
}

it('always renders the comments section and the leave-a-comment form, even with no comments', function () use ($ctx) {
    $html = (new JournalCommentsRenderer())->render([], $ctx);

    expect($html)->toContain('<section class="comments"')
        ->toContain('data-perego-comments')
        ->toContain('id="respond"')
        ->toContain('name="comment_post_ID"')
        ->toContain('name="comment_parent"')
        ->toContain('name="perego_hp"')                 // honeypot present
        ->toContain('class="comment-form__title">Leave a comment')
        ->not->toContain('<ul class="comment-list">');  // no list when there are no comments
});

it('exposes endpoint + nonce on the section for the AJAX view script', function () use ($ctx) {
    $html = (new JournalCommentsRenderer())->render([], $ctx);

    expect($html)->toContain('data-endpoint="https://perego.local/wp-json/perego/v1/comments"')
        ->toContain('data-nonce="abc123"');
});

it('renders the comment list with a pluralized count title and one card per comment', function () use ($ctx) {
    $html = (new JournalCommentsRenderer())->render([
        comment('Sara Adel', 'Great read.', false, 21),
        comment('Mostafa Emam', 'Thanks Sara!', true, 22),
        comment('Karim Hassan', 'Useful filter.', false, 23),
    ], $ctx);

    expect($html)->toContain('<h2 class="comments__title">3 Comments</h2>')
        ->toContain('<ul class="comment-list">');
    expect(substr_count($html, '<li'))->toBe(3);
});

it('uses the singular title for a single comment', function () use ($ctx) {
    $html = (new JournalCommentsRenderer())->render([comment('Sara Adel', 'Nice.', false, 21)], $ctx);

    expect($html)->toContain('<h2 class="comments__title">1 Comment</h2>');
});

it('marks a reply with comment--reply', function () use ($ctx) {
    $html = (new JournalCommentsRenderer())->render([
        comment('Sara Adel', 'Great read.', false, 21),
        comment('Mostafa Emam', 'Thanks!', true, 22),
    ], $ctx);

    expect(substr_count($html, 'comment--reply'))->toBe(1);
});

it('renderCard emits the card with initials avatar, id, and a reply link carrying the parent data', function () {
    $html = (new JournalCommentsRenderer())->renderCard(
        comment('Sara Adel', 'Great read.', false, 21),
        ['reply' => 'Reply'],
    );

    expect($html)->toContain('id="comment-21"')
        ->toContain('<span class="comment__avatar" aria-hidden="true">SA</span>')
        ->toContain('<span class="comment__name">Sara Adel</span>')
        ->toContain('<p class="comment__text">Great read.</p>')
        ->toContain('data-parent-id="21"')
        ->toContain('data-parent-name="Sara Adel"');
});

it('derives up to two uppercase initials for the avatar', function () use ($ctx) {
    $html = (new JournalCommentsRenderer())->render([
        comment('Sara Adel', 'x', false, 1),
        comment('Karim Hassan', 'y', false, 2),
    ], $ctx);

    expect($html)->toContain('>SA</span>')->toContain('>KH</span>');
});
