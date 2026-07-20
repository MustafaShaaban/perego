<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Forms\Rules;

defined('ABSPATH') || exit;

use Corex\Forms\Validation\Rule;

/**
 * Perego-owned validation rule: cap a long-form text field by WORD count rather than character count
 * — for a message or a project brief, "how much you can say" is the meaningful limit. Registered on
 * CoreX Forms' shared RuleRegistry by PeregoSiteServiceProvider (client-side composition), so a
 * framework update can never wipe it — unlike the earlier in-framework MaxWords, whose removal left
 * QuickMessageForm/ProjectBriefForm requesting an unknown `max_words` rule and rendering empty.
 *
 * An empty value passes (emptiness is `required`'s job). Words are whitespace-separated runs counted
 * with `/\s+/u` so the count is script-agnostic (Latin and Arabic behave the same) rather than
 * `\w`-based, which would undercount non-Latin text. Returns the i18n message key on failure.
 */
final class MaxWords implements Rule
{
    public function validate(mixed $value, array $params, array $allValues): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $limit = (int) ($params[0] ?? 0);
        $words = preg_split('/\s+/u', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY);

        return count($words) > $limit ? 'max_words' : null;
    }
}
