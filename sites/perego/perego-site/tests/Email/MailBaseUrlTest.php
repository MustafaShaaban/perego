<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Email\MailBaseUrl;

/** Stub the two option reads MailBaseUrl makes, with the override empty unless a test sets it. */
function stubOptions(string $siteUrl, string $override = ''): void
{
    Functions\when('get_option')->alias(
        static fn (string $key, mixed $default = '') => match ($key) {
            MailBaseUrl::OPTION => $override,
            'siteurl' => $siteUrl,
            default => $default,
        }
    );
}

it('falls back to the stored site url, without a trailing slash', function () {
    stubOptions('https://peregoads.com/');

    expect(MailBaseUrl::resolve())->toBe('https://peregoads.com');
});

it('prefers the explicit override, so a local install can preview production mail', function () {
    stubOptions('http://perego.local', 'https://peregoads.com');

    expect(MailBaseUrl::resolve())->toBe('https://peregoads.com');
});

it('never returns an empty base, which would render CTA links as unfollowable paths', function () {
    stubOptions('', '');

    expect(MailBaseUrl::resolve())->toBe(MailBaseUrl::FALLBACK)
        ->and(MailBaseUrl::FALLBACK)->toStartWith('https://');
});

it('rebases a request-built url onto the public host', function () {
    stubOptions('http://perego.local', 'https://peregoads.com');
    Functions\when('site_url')->justReturn('http://perego.local');

    expect(MailBaseUrl::rebase('http://perego.local/wp-content/plugins/perego-site/assets/email/logo-full.png'))
        ->toBe('https://peregoads.com/wp-content/plugins/perego-site/assets/email/logo-full.png');
});

it('rebases away an ngrok host, so a tunnelled request cannot bake an ephemeral domain into mail', function () {
    // wp-config rewrites WP_HOME/WP_SITEURL from X-Forwarded-Host, so site_url() is the tunnel while the
    // siteurl OPTION stays stable. That is exactly why resolve() reads the option, not site_url().
    stubOptions('https://peregoads.com');
    Functions\when('site_url')->justReturn('https://abc123.ngrok-free.dev');

    expect(MailBaseUrl::rebase('https://abc123.ngrok-free.dev/wp-content/uploads/cv.pdf'))
        ->toBe('https://peregoads.com/wp-content/uploads/cv.pdf');
});

it('leaves a url alone when it does not belong to this site', function () {
    stubOptions('http://perego.local', 'https://peregoads.com');
    Functions\when('site_url')->justReturn('http://perego.local');

    expect(MailBaseUrl::rebase('https://cdn.example.com/logo.png'))->toBe('https://cdn.example.com/logo.png')
        ->and(MailBaseUrl::rebase(''))->toBe('');
});

it('leaves a url alone when the base already matches the running site', function () {
    stubOptions('https://peregoads.com');
    Functions\when('site_url')->justReturn('https://peregoads.com');

    expect(MailBaseUrl::rebase('https://peregoads.com/a.png'))->toBe('https://peregoads.com/a.png');
});
