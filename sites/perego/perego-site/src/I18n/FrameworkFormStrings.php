<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\I18n;

defined('ABSPATH') || exit;

/**
 * Localizes into Arabic the handful of framework (`corex` text domain) form UI strings that surface on
 * Perego's public forms — the submit label and the success/error status lines. The CoreX Forms engine
 * renders these with `esc_html__(…, 'corex')` and exposes no per-form override, so the client localizes
 * them at the presentation layer via the domain-scoped `gettext_corex` filter — no framework code is
 * touched. Only exact-match `corex` sources are mapped, and only when the active locale is Arabic, so
 * English (and every other locale/string) is untouched. Spec 005.
 */
final class FrameworkFormStrings
{
    /** @var array<string, string> corex source string => Arabic. */
    private const AR = [
        'Send' => 'إرسال',
        'Thank you — your message has been sent.' => 'شكرًا لك — تم إرسال رسالتك.',
        'Please review the highlighted fields and try again.' => 'يرجى مراجعة الحقول المميزة والمحاولة مرة أخرى.',
    ];

    public function register(): void
    {
        add_filter('gettext_corex', [$this, 'translate'], 10, 3);
    }

    /**
     * @param string $translation the current (framework) translation
     * @param string $text        the untranslated source string
     * @param string $domain      the text domain (always `corex` for this filter)
     */
    public function translate(string $translation, string $text, string $domain): string
    {
        if ($domain !== 'corex' || ! isset(self::AR[$text])) {
            return $translation;
        }

        return str_starts_with(get_locale(), 'ar') ? self::AR[$text] : $translation;
    }
}
