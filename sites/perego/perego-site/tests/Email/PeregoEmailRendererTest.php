<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\Email\PeregoEmailRenderer;

function renderEmail(string $template, string $locale, array $ctx = []): array
{
    return (new PeregoEmailRenderer('https://perego.local', 'https://perego.local/logo.png'))
        ->render($template, $locale, $ctx);
}

it('rejects an unknown template', function () {
    (new PeregoEmailRenderer())->render('nope', 'en', []);
})->throws(InvalidArgumentException::class);

it('renders the branded shell with the handoff signature + contact address', function () {
    $out = renderEmail('contact-confirmation', 'en', ['name' => 'Sara', 'email' => 's@x.com', 'message' => 'Hello there']);

    expect($out['html'])->toContain('linear-gradient(120deg,#4a0d8f') // gradient header
        ->and($out['html'])->toContain('The Perego Team')
        ->and($out['html'])->toContain('info@peregoads.com')
        ->and($out['html'])->toContain('width="600"') // 600px shell
        ->and($out['html'])->toStartWith('<!DOCTYPE html>');
});

it('localizes contact confirmation subject/heading and flips to RTL for Arabic', function () {
    $en = renderEmail('contact-confirmation', 'en', ['name' => 'Sara']);
    $ar = renderEmail('contact-confirmation', 'ar', ['name' => 'سارة']);

    expect($en['subject'])->toBe('Thanks for reaching out — Perego')
        ->and($en['html'])->toContain('Thank you, Sara!')
        ->and($en['html'])->toContain('dir="ltr"')
        ->and($ar['subject'])->toBe('شكرًا لتواصلك — بيريجو')
        ->and($ar['html'])->toContain('شكرًا لك، سارة!')
        ->and($ar['html'])->toContain('dir="rtl"');
});

it('escapes merge values so injected markup is inert', function () {
    $out = renderEmail('contact-confirmation', 'en', [
        'name' => '<script>alert(1)</script>',
        'email' => 'a@b.com',
        'message' => '<b>hi</b>',
    ]);

    expect($out['html'])->not->toContain('<script>alert(1)</script>')
        ->and($out['html'])->toContain('&lt;script&gt;')
        ->and($out['html'])->toContain('&lt;b&gt;hi&lt;/b&gt;');
});

it('hides optional phone/company on the brief when empty and shows them when present', function () {
    $bare = renderEmail('project-brief-confirmation', 'en', ['name' => 'A', 'email' => 'a@b.com', 'services' => 'Video', 'budget' => '1k-5k', 'subject' => 'S', 'message' => 'M']);
    $full = renderEmail('project-brief-confirmation', 'en', ['name' => 'A', 'email' => 'a@b.com', 'phone' => '123', 'company' => 'Acme', 'services' => 'Video', 'budget' => '1k-5k', 'subject' => 'S', 'message' => 'M']);

    // Reply line ends right after the email when phone/company are absent (no trailing bullets).
    expect($bare['html'])->toContain('Reply-to: a@b.com</p>')
        ->and($full['html'])->toContain('123')
        ->and($full['html'])->toContain('Acme');
});

it('drops a javascript: portfolio URL but keeps a valid https one on the join email', function () {
    $evil = renderEmail('join-confirmation', 'en', ['name' => 'A', 'portfolio' => 'javascript:alert(1)', 'cv_filename' => 'cv.pdf']);
    $good = renderEmail('join-confirmation', 'en', ['name' => 'A', 'portfolio' => 'https://folio.example', 'cv_filename' => 'cv.pdf']);

    expect($evil['html'])->not->toContain('javascript:')
        ->and($good['html'])->toContain('href="https://folio.example"')
        ->and($good['html'])->toContain('cv.pdf');
});

it('shows an em dash for a missing CV filename', function () {
    $out = renderEmail('join-confirmation', 'en', ['name' => 'A', 'portfolio' => 'https://x.example']);

    expect($out['html'])->toContain('—');
});

it('injects pre-rendered field rows into the admin notification and uses a minimal footer', function () {
    $out = renderEmail('admin-notification', 'en', [
        'form_name' => 'Quick message',
        'submitted_at' => '2026-07-12 10:00',
        'reply_email' => 'sender@x.com',
        'fields_html' => '<tr><td>Name</td><td>Sara</td></tr>',
    ]);

    expect($out['subject'])->toBe('New Quick message submission — Perego')
        ->and($out['html'])->toContain('<tr><td>Name</td><td>Sara</td></tr>')
        ->and($out['html'])->toContain('mailto:sender@x.com')
        ->and($out['html'])->toContain('Do not share externally')
        ->and($out['html'])->not->toContain('The Perego Team'); // minimal footer
});

it('builds the comment notification with approve + reply actions', function () {
    $out = renderEmail('comment-notification', 'en', [
        'commenter_name' => 'Ali',
        'commenter_email' => 'ali@x.com',
        'comment_body' => 'Great post',
        'post_title' => 'My Post',
        'post_url' => 'https://perego.local/journal/my-post',
        'approve_url' => 'https://perego.local/wp-admin/comment.php?action=approve',
        'submitted_at' => '2026-07-12',
    ]);

    expect($out['subject'])->toBe('New comment awaiting review — Perego')
        ->and($out['html'])->toContain('Review &amp; approve')
        ->and($out['html'])->toContain('Great post')
        ->and($out['html'])->toContain('mailto:ali@x.com');
});

it('always produces a plain-text alternative with the signature', function () {
    $out = renderEmail('contact-confirmation', 'en', ['name' => 'Sara', 'email' => 's@x.com', 'message' => 'Hi']);

    expect($out['text'])->toContain('Thank you, Sara!')
        ->and($out['text'])->toContain('The Perego Team')
        ->and($out['text'])->toContain('info@peregoads.com')
        ->and($out['text'])->not->toContain('<'); // no markup in the text part
});
