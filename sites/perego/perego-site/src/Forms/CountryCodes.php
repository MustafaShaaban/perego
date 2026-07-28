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
 * remaining countries the studio actually hears from, alphabetically. A visitor from Dubai should
 * not scroll past Afghanistan to find the Emirates.
 */
final class CountryCodes
{
    /**
     * The code preselected when the visitor's own country cannot be inferred.
     *
     * The Gulf, not Egypt, because the fallback should favour the audience rather than the studio's
     * own address: the target segment is the UAE and Saudi Arabia, and someone the detection missed
     * is far likelier to be there than in Cairo.
     */
    public const DEFAULT_ISO = 'AE';

    /**
     * ISO 3166-1 alpha-2 => [dial code, English name, Arabic name, example number?].
     *
     * The example is the placeholder the field shows once this country is picked, and it is
     * deliberately absent for most of the list: a *wrong* format teaches worse than no format, and
     * inventing plausible-looking numbers for sixty countries is how wrong ones ship. Where it is
     * absent the field keeps its translated "best number to reach you" copy.
     *
     * @var array<string, array{0:string,1:string,2:string,3?:string}>
     */
    private const COUNTRIES = [
        // Primary markets, first because most submissions come from them.
        'AE' => ['+971', 'United Arab Emirates', 'الإمارات', '+971 50 123 4567'],
        'SA' => ['+966', 'Saudi Arabia', 'السعودية', '+966 50 123 4567'],
        'EG' => ['+20', 'Egypt', 'مصر', '+20 100 123 4567'],
        'KW' => ['+965', 'Kuwait', 'الكويت', '+965 500 12345'],
        'QA' => ['+974', 'Qatar', 'قطر', '+974 3312 3456'],
        'BH' => ['+973', 'Bahrain', 'البحرين', '+973 3600 1234'],
        'OM' => ['+968', 'Oman', 'عُمان', '+968 9212 3456'],

        // The rest of the Arab region.
        'JO' => ['+962', 'Jordan', 'الأردن', '+962 7 9012 3456'],
        'LB' => ['+961', 'Lebanon', 'لبنان', '+961 71 123 456'],
        'IQ' => ['+964', 'Iraq', 'العراق', '+964 790 123 4567'],
        'SY' => ['+963', 'Syria', 'سوريا'],
        'PS' => ['+970', 'Palestine', 'فلسطين', '+970 599 123 456'],
        'YE' => ['+967', 'Yemen', 'اليمن'],
        'SD' => ['+249', 'Sudan', 'السودان', '+249 91 123 4567'],
        'LY' => ['+218', 'Libya', 'ليبيا', '+218 91 234 5678'],
        'TN' => ['+216', 'Tunisia', 'تونس', '+216 20 123 456'],
        'DZ' => ['+213', 'Algeria', 'الجزائر', '+213 551 23 45 67'],
        'MA' => ['+212', 'Morocco', 'المغرب', '+212 650 123456'],
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
     * IANA timezone => ISO code, for inferring the visitor's country in the browser.
     *
     * `Intl.DateTimeFormat().resolvedOptions().timeZone` is the only country signal a browser gives
     * away for free: no network call, no geolocation permission, no third-party service, and it is
     * already correct on a phone that set its own clock. It is also, for this purpose, better than
     * `navigator.language` — an Emirati on an English-locale laptop reports `en-US`.
     *
     * One canonical zone per offered country (a few carry two, where a country spans zones or where
     * a legacy alias is still what browsers report — `Asia/Calcutta`, and the US zones). Anything
     * outside the list falls through to the language hint and then to the default, which is the
     * right outcome for a visitor the picker was never ordered around.
     *
     * Kept here rather than in JS so it cannot drift from COUNTRIES — every value below must be a
     * key above, and CountryCodesTest fails if one is not.
     *
     * @return array<string, string>
     */
    public static function zones(): array
    {
        return [
            // The Gulf and the wider Arab region: the audience this form is for.
            'Asia/Dubai' => 'AE',
            'Asia/Riyadh' => 'SA',
            'Africa/Cairo' => 'EG',
            'Asia/Kuwait' => 'KW',
            'Asia/Qatar' => 'QA',
            'Asia/Bahrain' => 'BH',
            'Asia/Muscat' => 'OM',
            'Asia/Amman' => 'JO',
            'Asia/Beirut' => 'LB',
            'Asia/Baghdad' => 'IQ',
            'Asia/Damascus' => 'SY',
            'Asia/Gaza' => 'PS',
            'Asia/Hebron' => 'PS',
            'Asia/Aden' => 'YE',
            'Africa/Khartoum' => 'SD',
            'Africa/Tripoli' => 'LY',
            'Africa/Tunis' => 'TN',
            'Africa/Algiers' => 'DZ',
            'Africa/Casablanca' => 'MA',
            'Africa/Nouakchott' => 'MR',
            'Africa/Mogadishu' => 'SO',
            'Africa/Djibouti' => 'DJ',
            'Indian/Comoro' => 'KM',

            // Europe.
            'Europe/London' => 'GB',
            'Europe/Dublin' => 'IE',
            'Europe/Paris' => 'FR',
            'Europe/Berlin' => 'DE',
            'Europe/Madrid' => 'ES',
            'Europe/Lisbon' => 'PT',
            'Europe/Rome' => 'IT',
            'Europe/Malta' => 'MT',
            'Europe/Amsterdam' => 'NL',
            'Europe/Brussels' => 'BE',
            'Europe/Zurich' => 'CH',
            'Europe/Vienna' => 'AT',
            'Europe/Prague' => 'CZ',
            'Europe/Warsaw' => 'PL',
            'Europe/Bucharest' => 'RO',
            'Europe/Athens' => 'GR',
            'Europe/Stockholm' => 'SE',
            'Europe/Oslo' => 'NO',
            'Europe/Copenhagen' => 'DK',
            'Europe/Helsinki' => 'FI',
            'Europe/Kyiv' => 'UA',
            'Europe/Kiev' => 'UA',
            'Europe/Istanbul' => 'TR',
            'Europe/Moscow' => 'RU',
            'Asia/Nicosia' => 'CY',

            // The Americas.
            'America/New_York' => 'US',
            'America/Chicago' => 'US',
            'America/Denver' => 'US',
            'America/Los_Angeles' => 'US',
            'America/Toronto' => 'CA',
            'America/Vancouver' => 'CA',
            'America/Mexico_City' => 'MX',
            'America/Sao_Paulo' => 'BR',

            // Asia-Pacific and the rest of Africa.
            'Asia/Karachi' => 'PK',
            'Asia/Kolkata' => 'IN',
            'Asia/Calcutta' => 'IN',
            'Asia/Shanghai' => 'CN',
            'Asia/Hong_Kong' => 'HK',
            'Asia/Tokyo' => 'JP',
            'Asia/Seoul' => 'KR',
            'Asia/Singapore' => 'SG',
            'Asia/Kuala_Lumpur' => 'MY',
            'Asia/Jakarta' => 'ID',
            'Asia/Bangkok' => 'TH',
            'Asia/Manila' => 'PH',
            'Asia/Ho_Chi_Minh' => 'VN',
            'Australia/Sydney' => 'AU',
            'Australia/Melbourne' => 'AU',
            'Pacific/Auckland' => 'NZ',
            'Africa/Lagos' => 'NG',
            'Africa/Nairobi' => 'KE',
            'Africa/Addis_Ababa' => 'ET',
            'Africa/Johannesburg' => 'ZA',
        ];
    }

    /**
     * The picker's options, in display order, for the locale given.
     *
     * Three labels per country, because the control has three jobs. `label` names the country and is
     * what the open list shows. `short` is what the closed trigger shows — an ISO plus a dial code,
     * which is what you would read off a SIM card, so it stays Latin in both languages: localizing
     * it would help nobody dial. `example` is the placeholder the number input adopts once this
     * country is selected, and is an empty string where no format is published here.
     *
     * @return list<array{iso:string,dial:string,label:string,short:string,example:string}>
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
                // Digits read the same in both catalogues, so there is nothing here to translate.
                'example' => self::COUNTRIES[$iso][3] ?? '',
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
