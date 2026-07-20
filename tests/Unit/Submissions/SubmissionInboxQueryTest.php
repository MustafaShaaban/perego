<?php

/**
 * Covers the `flow` filter parsing added for code-registered forms: a numeric value is a DB flow id,
 * a `slug:<x>` value is a code-form slug matched on `corex_form_slug`, and the two are never mixed.
 *
 * @package Corex\Tests\Unit\Submissions
 */

declare(strict_types=1);

use Corex\Config\Submissions\SubmissionInboxQuery;

// The unit suite runs without WordPress; from() sanitizes the slug with sanitize_key. Provide a
// faithful minimal shim only when WP is absent, so the slug path is exercisable in isolation (the
// integration suite uses the real function).
if (! function_exists('sanitize_key')) {
    function sanitize_key($key): string
    {
        // Match WordPress: lowercase first, then strip anything but [a-z0-9_-].
        return (string) preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $key));
    }
}

it('reads a numeric flow filter as a DB flow id with no form slug', function () {
    $query = SubmissionInboxQuery::from(['flow' => '42']);

    expect($query->flowId)->toBe(42);
    expect($query->formSlug)->toBe('');
});

it('reads a slug: flow filter as a code-form slug with no flow id', function () {
    $query = SubmissionInboxQuery::from(['flow' => 'slug:perego-quick-message']);

    expect($query->formSlug)->toBe('perego-quick-message');
    expect($query->flowId)->toBe(0);
});

it('sanitizes the form slug from the flow filter', function () {
    $query = SubmissionInboxQuery::from(['flow' => 'slug:Bad Slug!!']);

    expect($query->formSlug)->toBe('badslug');
    expect($query->flowId)->toBe(0);
});

it('treats an empty flow filter as neither id nor slug', function () {
    $query = SubmissionInboxQuery::from(['flow' => '']);

    expect($query->flowId)->toBe(0);
    expect($query->formSlug)->toBe('');
});
