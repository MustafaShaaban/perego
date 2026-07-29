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
        'Unknown form.' => 'نموذج غير معروف.',
        'Submission rejected.' => 'تم رفض الإرسال.',

        // CoreX v0.40.0 reworded the submission-level strings. `Validation failed.` replaced
        // `Please review the highlighted fields and try again.`, and file storage is new with
        // spec 081. The old source is kept below so an Arabic page served from a stale opcache
        // during deploy still resolves rather than flashing English.
        'Validation failed.' => 'يرجى مراجعة الحقول المميزة والمحاولة مرة أخرى.',
        'The file could not be stored.' => 'تعذّر حفظ الملف.',
        'Please review the highlighted fields and try again.' => 'يرجى مراجعة الحقول المميزة والمحاولة مرة أخرى.',

        // Per-field validation. These reach the browser through the form's `data-corex-messages`
        // attribute, which the engine renders with `__()` precisely so this filter can localize
        // them — the client runtime's own copies go through `wp.i18n`, which needs a JS translation
        // file this site does not ship, so they stayed English on the Arabic pages.
        'This field is required.' => 'هذا الحقل مطلوب.',
        'Enter a valid email address.' => 'يرجى إدخال بريد إلكتروني صحيح.',
        'Enter a number.' => 'يرجى إدخال رقم.',
        'This value is too long.' => 'هذه القيمة طويلة جدًا.',
        'This value is too short.' => 'هذه القيمة قصيرة جدًا.',
        'This value is not in the expected format.' => 'صيغة هذه القيمة غير صحيحة.',

        // Reworded by CoreX v0.40.0's new `Block\ValidationMessages`. Because this map keys on the
        // exact English source, a rewording upstream does not fail — it silently serves English on
        // the Arabic pages, which is the worst way for a translation to break. `FrameworkFormStringsTest`
        // now asserts every key of `ValidationMessages::all()` has an entry here, so the next rewording
        // is a red test instead.
        'Enter a valid web address.' => 'يرجى إدخال رابط صحيح.',
        'Enter a valid phone number.' => 'يرجى إدخال رقم الهاتف مع رمز الدولة.',
        'Please check this field.' => 'يرجى مراجعة هذا الحقل.',

        // Pre-v0.40.0 sources, kept for the same stale-opcache reason as above.
        'Enter a valid link.' => 'يرجى إدخال رابط صحيح.',
        'Enter a phone number including its country code.' => 'يرجى إدخال رقم الهاتف مع رمز الدولة.',

        // Perego's own `Forms\Rules\MaxWords` message. CoreX has no `max_words` rule, so this never
        // appears in `ValidationMessages::all()` and the coverage test does not look for it.
        'This message is too long.' => 'هذه الرسالة تتجاوز الحد المسموح من الكلمات.',
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
