<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Forms;

defined('ABSPATH') || exit;

/**
 * The dial codes offered beside the phone field, and the order they are offered in.
 *
 * Why this exists at all: the phone field was a bare `<input type="tel">` with a character cap, so
 * "01016999700" was accepted and nobody could dial it from outside Egypt. The team's audience spans
 * Egypt, the Gulf and beyond, which makes the country code the part of the number that matters
 * (client report 2026-07-27).
 *
 * Why it is written here rather than pulled from a library: the constitution forbids loading a global
 * JS library (Principle VI), which rules out intl-tel-input and its flag sprites. The list is small,
 * static, and public data — carrying it costs one array and keeps the control inside the design
 * system, styled by the same rules as every other Perego select.
 *
 * The order is deliberate. The primary markets lead, then the rest of the Arab region, then the
 * remaining countries the studio actually hears from, alphabetically. A visitor from Cairo should
 * not scroll past Afghanistan to find Egypt.
 */
final class CountryCodes
{
    /** The code preselected when the visitor's own country cannot be inferred. */
    public const DEFAULT_ISO = 'EG';

    /**
     * ISO 3166-1 alpha-2 => [dial code, English name, Arabic name].
     *
     * @var array<string, array{0:string,1:string,2:string}>
     */
    private const COUNTRIES = [
        // Primary markets, first because most submissions come from them.
        'EG' => ['+20', 'Egypt', 'مصر'],
        'SA' => ['+966', 'Saudi Arabia', 'السعودية'],
        'AE' => ['+971', 'United Arab Emirates', 'الإمارات'],
        'KW' => ['+965', 'Kuwait', 'الكويت'],
        'QA' => ['+974', 'Qatar', 'قطر'],
        'BH' => ['+973', 'Bahrain', 'البحرين'],
        'OM' => ['+968', 'Oman', 'عُمان'],

        // The rest of the Arab region.
        'JO' => ['+962', 'Jordan', 'الأردن'],
        'LB' => ['+961', 'Lebanon', 'لبنان'],
        'IQ' => ['+964', 'Iraq', 'العراق'],
        'SY' => ['+963', 'Syria', 'سوريا'],
        'PS' => ['+970', 'Palestine', 'فلسطين'],
        'YE' => ['+967', 'Yemen', 'اليمن'],
        'SD' => ['+249', 'Sudan', 'السودان'],
        'LY' => ['+218', 'Libya', 'ليبيا'],
        'TN' => ['+216', 'Tunisia', 'تونس'],
        'DZ' => ['+213', 'Algeria', 'الجزائر'],
        'MA' => ['+212', 'Morocco', 'المغرب'],
        'MR' => ['+222', 'Mauritania', 'موريتانيا'],
        'SO' => ['+252', 'Somalia', 'الصومال'],
        'DJ' => ['+253', 'Djibouti', 'جيبوتي'],
        'KM' => ['+269', 'Comoros', 'جزر القمر'],

        // Everywhere else the studio hears from, alphabetically by English name.
        'AU' => ['+61', 'Australia', 'أستراليا'],
        'AT' => ['+43', 'Austria', 'النمسا'],
        'BE' => ['+32', 'Belgium', 'بلجيكا'],
        'BR' => ['+55', 'Brazil', 'البرازيل'],
        'CA' => ['+1', 'Canada', 'كندا'],
        'CN' => ['+86', 'China', 'الصين'],
        'CY' => ['+357', 'Cyprus', 'قبرص'],
        'CZ' => ['+420', 'Czechia', 'التشيك'],
        'DK' => ['+45', 'Denmark', 'الدنمارك'],
        'ET' => ['+251', 'Ethiopia', 'إثيوبيا'],
        'FI' => ['+358', 'Finland', 'فنلندا'],
        'FR' => ['+33', 'France', 'فرنسا'],
        'DE' => ['+49', 'Germany', 'ألمانيا'],
        'GR' => ['+30', 'Greece', 'اليونان'],
        'HK' => ['+852', 'Hong Kong', 'هونغ كونغ'],
        'IN' => ['+91', 'India', 'الهند'],
        'ID' => ['+62', 'Indonesia', 'إندونيسيا'],
        'IE' => ['+353', 'Ireland', 'أيرلندا'],
        'IT' => ['+39', 'Italy', 'إيطاليا'],
        'JP' => ['+81', 'Japan', 'اليابان'],
        'KE' => ['+254', 'Kenya', 'كينيا'],
        'MY' => ['+60', 'Malaysia', 'ماليزيا'],
        'MT' => ['+356', 'Malta', 'مالطا'],
        'MX' => ['+52', 'Mexico', 'المكسيك'],
        'NL' => ['+31', 'Netherlands', 'هولندا'],
        'NZ' => ['+64', 'New Zealand', 'نيوزيلندا'],
        'NG' => ['+234', 'Nigeria', 'نيجيريا'],
        'NO' => ['+47', 'Norway', 'النرويج'],
        'PK' => ['+92', 'Pakistan', 'باكستان'],
        'PH' => ['+63', 'Philippines', 'الفلبين'],
        'PL' => ['+48', 'Poland', 'بولندا'],
        'PT' => ['+351', 'Portugal', 'البرتغال'],
        'RO' => ['+40', 'Romania', 'رومانيا'],
        'RU' => ['+7', 'Russia', 'روسيا'],
        'SG' => ['+65', 'Singapore', 'سنغافورة'],
        'ZA' => ['+27', 'South Africa', 'جنوب أفريقيا'],
        'KR' => ['+82', 'South Korea', 'كوريا الجنوبية'],
        'ES' => ['+34', 'Spain', 'إسبانيا'],
        'SE' => ['+46', 'Sweden', 'السويد'],
        'CH' => ['+41', 'Switzerland', 'سويسرا'],
        'TH' => ['+66', 'Thailand', 'تايلاند'],
        'TR' => ['+90', 'Türkiye', 'تركيا'],
        'UA' => ['+380', 'Ukraine', 'أوكرانيا'],
        'GB' => ['+44', 'United Kingdom', 'المملكة المتحدة'],
        'US' => ['+1', 'United States', 'الولايات المتحدة'],
        'VN' => ['+84', 'Vietnam', 'فيتنام'],
    ];

    /**
     * The picker's options, in display order, for the locale given.
     *
     * Two labels per country, because the control has two jobs. `label` names the country and is what
     * the open list shows. `short` is what the closed trigger shows — the phone row is a half-width
     * field on a two-column form, roughly 150–255px wide, so a trigger rendering "United Arab Emirates
     * (+971)" squeezed the number input down to almost nothing (client report 2026-07-28).
     *
     * `short` stays Latin in both languages: an ISO code and a dial code are what you would read off a
     * SIM card, and localizing them would help nobody dial.
     *
     * @return list<array{iso:string,dial:string,label:string,short:string}>
     */
    public static function options(string $locale = 'en'): array
    {
        $arabic = str_starts_with($locale, 'ar');

        $options = [];
        foreach (self::COUNTRIES as $iso => [$dial, $english, $arabicName]) {
            $options[] = [
                'iso' => $iso,
                'dial' => $dial,
                'label' => ($arabic ? $arabicName : $english) . ' (' . $dial . ')',
                'short' => $iso . ' ' . $dial,
            ];
        }

        return $options;
    }

    /**
     * The ISO code whose dial code a number already carries, or '' when none matches.
     *
     * Longest dial code first: `+1` prefixes nothing else here, but `+20` (Egypt) is a prefix of
     * `+201…` and `+7` (Russia) of `+7xx`, so a shortest-first scan would mis-assign them.
     */
    public static function fromNumber(string $number): string
    {
        $digits = preg_replace('/[\s()\-.]/u', '', $number) ?? '';
        if (! str_starts_with($digits, '+')) {
            return '';
        }

        $best = '';
        $bestLength = 0;
        foreach (self::COUNTRIES as $iso => [$dial]) {
            $length = strlen($dial);
            if ($length > $bestLength && str_starts_with($digits, $dial)) {
                $best = $iso;
                $bestLength = $length;
            }
        }

        return $best;
    }
}
