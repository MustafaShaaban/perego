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

    // Contact details are labelled summary rows, so an absent one is simply an absent row.
    expect($bare['html'])->not->toContain('Phone')
        ->and($bare['html'])->not->toContain('Company')
        ->and($bare['html'])->toContain('a@b.com')
        ->and($full['html'])->toContain('123')
        ->and($full['html'])->toContain('Acme');
});

it('never labels a contact line "Your details" or "Your email"', function () {
    $brief = renderEmail('project-brief-confirmation', 'en', ['name' => 'A', 'email' => 'a@b.com', 'phone' => '123', 'company' => 'Acme', 'message' => 'M']);
    $contact = renderEmail('contact-confirmation', 'en', ['name' => 'A', 'email' => 'a@b.com', 'message' => 'M']);

    // Both read to the visitor as their own data quoted back at them; the brief's version also
    // looked like a malformed mail header (client report 2026-07-27).
    expect($brief['html'])->not->toContain('Your details')
        ->and($brief['text'])->not->toContain('Your details')
        ->and($contact['html'])->not->toContain('Your email')
        ->and($contact['text'])->not->toContain('Your email')
        // The contact confirmation has no reason to restate the address it was delivered to.
        ->and($contact['html'])->not->toContain('a@b.com');
});

it('signs internal notifications as the platform in both HTML and plain text', function () {
    $out = renderEmail('admin-notification', 'en', [
        'form_name' => 'Contact', 'submitted_at' => '2026-07-27 12:00:00',
        'reply_email' => 'a@b.com', 'fields_html' => '',
    ]);

    // A team member reading this must see that nobody is waiting on a reply to the platform —
    // the client is reachable through Reply-To, not through this signature.
    foreach ([$out['html'], $out['text']] as $part) {
        expect($part)->toContain('Best regards,')
            ->and($part)->toContain('System Notification Service')
            ->and($part)->toContain('Perego Web Platform')
            ->and($part)->not->toContain('The Perego Team');
    }
});

it('keeps the team signature on visitor-facing mail', function () {
    $out = renderEmail('contact-confirmation', 'en', ['name' => 'A', 'email' => 'a@b.com', 'message' => 'M']);

    expect($out['text'])->toContain('The Perego Team')
        ->and($out['text'])->not->toContain('System Notification Service');
});

it('renders an operator reply inside the Perego shell', function () {
    $out = renderEmail('reply', 'en', ['subject' => 'About your brief', 'body' => '<p>Hi Ali, here are our next steps.</p>']);

    // The reply used to leave as bare HTML on the client's default white background while every
    // automated email from the same site was branded (client report 2026-07-27).
    expect($out['subject'])->toBe('About your brief')
        ->and($out['html'])->toStartWith('<!DOCTYPE html>')
        ->and($out['html'])->toContain('linear-gradient(120deg,#4a0d8f')
        ->and($out['html'])->toContain('width="600"')
        ->and($out['html'])->toContain('The Perego Team')
        // The operator's own markup survives — escaping it would print their formatting as source.
        ->and($out['html'])->toContain('<p>Hi Ali, here are our next steps.</p>');
});

it('gives a reply a subject even when the operator left it blank', function () {
    $en = renderEmail('reply', 'en', ['subject' => '', 'body' => '<p>x</p>']);
    $ar = renderEmail('reply', 'ar', ['subject' => '', 'body' => '<p>x</p>']);

    // An empty Subject: header is a spam signal, so it can never be shipped as-is.
    expect($en['subject'])->toBe('A message from Perego')
        ->and($ar['subject'])->toBe('رسالة من بيريجو')
        ->and($ar['html'])->toContain('dir="rtl"');
});

it('strips markup from an operator reply subject, which is data rather than markup', function () {
    $out = renderEmail('reply', 'en', ['subject' => '<script>alert(1)</script>', 'body' => '<p>x</p>']);

    // The subject reaches both the `Subject:` header and the shell's `<title>`, so escaping is not
    // enough on its own — an escaped header would ship a literal `&lt;script&gt;` to the reader.
    expect($out['subject'])->toBe('alert(1)')
        ->and($out['html'])->not->toContain('<script>');
});

it('uses no rgba() anywhere, because Outlook drops the whole declaration', function (string $template) {
    $out = renderEmail($template, 'en', [
        'name' => 'A', 'email' => 'a@b.com', 'message' => 'M', 'subject' => 'S', 'body' => '<p>x</p>',
        'form_name' => 'Contact', 'submitted_at' => 'now', 'reply_email' => 'a@b.com',
        'fields_html' => PeregoEmailRenderer::fieldRows(['Name' => 'A']),
        'post_title' => 'P', 'post_url' => 'https://x.test', 'commenter_name' => 'C',
        'commenter_email' => 'c@d.com', 'comment_body' => 'B', 'approve_url' => 'https://x.test/a',
    ]);

    // Every rgba() colour rendered as the client default instead — white text on white, which is
    // what the client saw in the notification email (report 2026-07-27).
    expect($out['html'])->not->toContain('rgba(');
})->with(['contact-confirmation', 'project-brief-confirmation', 'join-confirmation', 'admin-notification', 'comment-notification', 'reply']);

it('gives every field value cell its own colour rather than inheriting one', function () {
    $rows = PeregoEmailRenderer::fieldRows(['Name' => 'Mustafa', 'Email' => 'a@b.com']);

    // Outlook does not carry `color` from a <table> into a <td>, so a value cell that only sets
    // padding renders in the client default — black, on the dark card.
    preg_match_all('/<td style="([^"]*)"/', $rows, $matches);
    expect($matches[1])->not->toBeEmpty();
    foreach ($matches[1] as $style) {
        expect($style)->toContain('color:');
    }
});

it('backs every dark surface with a bgcolor attribute as well as a style', function () {
    $out = renderEmail('admin-notification', 'en', [
        'form_name' => 'Contact', 'submitted_at' => 'now', 'reply_email' => 'a@b.com', 'fields_html' => '',
    ]);

    // A client that strips CSS backgrounds still gets the dark canvas, so the light text stays
    // readable. Without this the whole shell collapses to white-on-white.
    expect($out['html'])->toContain('bgcolor="#0c0020"')
        ->toContain('bgcolor="#160435"')
        ->toContain('bgcolor="#0f0126"')
        ->toContain('bgcolor="#280450"');
});

it('gives the gradient header a solid background a gradient-blind client can use', function () {
    $out = renderEmail('contact-confirmation', 'en', ['name' => 'A', 'message' => 'M']);

    // Outlook ignores CSS gradients. Declared through the `background` shorthand, that left the
    // header with no background at all — white brand mark on white.
    expect($out['html'])->toContain('bgcolor="#6c1eae"')
        ->toContain('background-color:#6c1eae')
        ->toContain('background-image:linear-gradient');
});

it('puts the CTA label on a bgcolor cell, not only on the anchor', function () {
    $out = renderEmail('contact-confirmation', 'en', ['name' => 'A', 'message' => 'M']);

    // The label is near-black violet and is legible only on the pink field; clients routinely drop
    // `background` from an inline-block anchor.
    expect($out['html'])->toContain('<td bgcolor="#d86af3"');
});

it('declares itself dark so auto-inverting clients leave the text alone', function () {
    $out = renderEmail('contact-confirmation', 'en', ['name' => 'A', 'message' => 'M']);

    expect($out['html'])->toContain('<meta name="color-scheme" content="dark" />')
        ->toContain('<meta name="supported-color-schemes" content="dark" />');
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

it('builds admin-notification rows, escaping labels and values', function () {
    $rows = PeregoEmailRenderer::fieldRows(['Name' => 'A & B', 'Email' => 'a@b.com']);

    expect($rows)->toContain('>Name</td>')
        ->and($rows)->toContain('A &amp; B')
        ->and($rows)->toContain('a@b.com')
        ->and($rows)->toContain('<tr>');
});

it('renders a link cell for the CV so HR can download it from the email', function () {
    // The mail stack carries no attachments, so the file has to arrive as a link.
    $rows = PeregoEmailRenderer::fieldRows([
        'CV' => ['text' => 'sara-cv.pdf', 'url' => 'https://peregoads.com/wp-content/uploads/sara-cv.pdf'],
    ]);

    expect($rows)->toContain('<a href="https://peregoads.com/wp-content/uploads/sara-cv.pdf"')
        ->and($rows)->toContain('>sara-cv.pdf</a>');
});

it('degrades an unsafe or missing url to plain text rather than emitting the href', function () {
    $unsafe = PeregoEmailRenderer::fieldRows(['CV' => ['text' => 'cv.pdf', 'url' => 'javascript:alert(1)']]);
    $missing = PeregoEmailRenderer::fieldRows(['CV' => ['text' => 'cv.pdf', 'url' => '']]);

    expect($unsafe)->not->toContain('javascript:')
        ->and($unsafe)->not->toContain('<a href')
        ->and($unsafe)->toContain('cv.pdf')
        ->and($missing)->not->toContain('<a href')
        ->and($missing)->toContain('cv.pdf');
});
