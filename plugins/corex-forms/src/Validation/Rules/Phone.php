<?php

/**
 * @package Corex\Forms
 */

declare(strict_types=1);

namespace Corex\Forms\Validation\Rules;

defined('ABSPATH') || exit;

use Corex\Forms\Validation\Rule;

/**
 * Fails when a non-empty value is not a phone number in E.164 form: a leading `+`, a non-zero
 * country code, and 8–15 digits in total.
 *
 * A `phone` field was previously only length-capped, so "12345" and "call me" both passed and the
 * team received numbers they could not dial — an international audience makes the country code the
 * part that actually matters. E.164 is the format every telco, SMS gateway and CRM already agrees
 * on, so validating to it keeps the stored value useful rather than merely well-formed.
 *
 * Formatting characters people naturally type (spaces, dashes, dots, brackets) are ignored before
 * the check: rejecting `+20 101 699 9700` for its spaces would be pedantry, not validation.
 *
 * An empty value passes here — emptiness is the `required` rule's concern.
 */
final class Phone implements Rule
{
    public function validate(mixed $value, array $params, array $allValues): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $digits = preg_replace('/[\s()\-.]/u', '', (string) $value) ?? '';

        return preg_match('/^\+[1-9]\d{7,14}$/', $digits) === 1 ? null : 'phone';
    }
}
