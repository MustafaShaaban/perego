<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Forms\Rules;

defined('ABSPATH') || exit;

use Corex\Forms\Validation\Rule;

/**
 * A phone number that can actually be dialled from outside the country it was typed in.
 *
 * WHY THIS OVERRIDES THE FRAMEWORK RULE. CoreX v0.40.0 ships its own `phone` rule, adopted from the
 * one this site reported (#148 item 4) — but with a looser pattern: `/^\+?[1-9]\d{1,14}$/`, where the
 * `+` is optional and two digits are enough. Under it `5` and `01016999700` both pass. The client's
 * original report (2026-07-27) was precisely that the team received numbers it could not dial, and a
 * bare local number is exactly that number: correct where it was written, useless from anywhere else.
 *
 * The `+` is therefore required and the minimum length raised, so what is stored is unambiguously
 * international. Everything else — stripping the separators people really type, deferring emptiness
 * to `required`, returning the `phone` message key so the existing translation is reused — matches the
 * framework rule deliberately, because only the strictness is in dispute.
 *
 * Registered over CoreX's own in `PeregoSiteServiceProvider`: `RuleRegistry::register()` overwrites by
 * name, so this needs no framework edit and cannot be undone by the next update — which is the whole
 * reason it lives here rather than as a patch to `Corex\Forms\Validation\Rules\Phone`.
 */
final class StrictPhone implements Rule
{
    /** Separators people put in phone numbers. Nothing here changes which digits were meant. */
    private const FORMATTING = [' ', '(', ')', '-', '.', "\u{00a0}"];

    /**
     * E.164, with the country code mandatory: a required `+`, a non-zero leading digit, then 7 to 14
     * more. Seven is the shortest national number in use once a country code is present.
     */
    private const PATTERN = '/^\+[1-9]\d{7,14}$/';

    /**
     * @param array<int,string>   $params
     * @param array<string,mixed> $allValues
     */
    public function validate(mixed $value, array $params, array $allValues): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $digits = str_replace(self::FORMATTING, '', $raw);

        return preg_match(self::PATTERN, $digits) === 1 ? null : 'phone';
    }
}
