<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use Corex\Support\Config\ConfigInterface;
use PeregoSite\Email\TeamRecipient;

/** @param array<string,mixed> $values */
function stubConfig(array $values): ConfigInterface
{
    return new class ($values) implements ConfigInterface {
        /** @param array<string,mixed> $values */
        public function __construct(private array $values)
        {
        }

        public function get(string $key, mixed $default = null): mixed
        {
            return $this->values[$key] ?? $default;
        }

        public function has(string $key): bool
        {
            return isset($this->values[$key]);
        }
    };
}

beforeEach(function () {
    Functions\when('is_email')->alias(static fn (string $v): bool => str_contains($v, '@'));
});

it('prefers the configured forms recipient over the WordPress admin address', function () {
    Functions\when('get_option')->justReturn('admin@example.com');

    // The careers endpoint used to read `admin_email` directly, which on a real install is often
    // still the WordPress default — so applications went to a mailbox nobody owns while contact
    // and brief mail arrived correctly (client report 2026-07-27).
    expect((new TeamRecipient(stubConfig(['forms.email.recipient' => 'team@peregoads.com'])))->address())
        ->toBe('team@peregoads.com');
});

it('falls back to the site admin when no recipient is configured', function () {
    Functions\when('get_option')->justReturn('owner@peregoads.com');

    expect((new TeamRecipient(stubConfig([])))->address())->toBe('owner@peregoads.com')
        ->and((new TeamRecipient())->address())->toBe('owner@peregoads.com');
});

it('returns nothing rather than a bad address when neither source is usable', function () {
    Functions\when('get_option')->justReturn('not-an-address');

    // '' is a recipient the mailer skips; a malformed one would be handed to the transport.
    expect((new TeamRecipient(stubConfig([])))->address())->toBe('');
});
