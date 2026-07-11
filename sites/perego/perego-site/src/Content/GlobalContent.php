<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Content;

defined('ABSPATH') || exit;

/**
 * Global editorial strings that appear on language-neutral FSE templates (404, search, legal chrome)
 * and therefore must be resolved per language at render time (Polylang Free cannot translate template
 * parts). Mirrors the handoff `content/{en,ar}.json` (notFound / search / legal). Falls back to `en`.
 *
 * This is the interim home for these strings; the prompt's `perego_global_section` CPT is the eventual
 * editor-canvas source (tracked in PROGRESS "Next"). Kept as a provider for now, consistent with the
 * other Perego content providers.
 */
final class GlobalContent
{
    private const DEFAULT_LOCALE = 'en';

    /** @var array<string, array<string, mixed>> */
    private const COPY = [
        'en' => [
            'notFound' => [
                'code' => '404',
                'title' => 'This page took a creative detour',
                'text' => "The page you're looking for doesn't exist or has been moved. Let's get you back to something worth watching.",
                'backHome' => 'Back to Home',
                'contact' => 'Contact Us',
            ],
            'search' => [
                'h1' => 'Search results',
                'resultsFor' => 'Showing results for',
                'matchesFound' => 'matches found',
                'placeholder' => 'Search the site…',
                'button' => 'Search',
                'empty' => 'No results found. Try a different search term.',
                'emptyHint' => 'Check your spelling or use more general keywords.',
            ],
            'legal' => [
                'lastUpdated' => 'Last updated',
                'tocTitle' => 'On this page',
                'reviewNote' => 'This is a draft for design/layout purposes and must be reviewed by legal counsel before publication.',
            ],
        ],
        'ar' => [
            'notFound' => [
                'code' => '404',
                'title' => 'هذه الصفحة سلكت منعطفًا إبداعيًا',
                'text' => 'الصفحة التي تبحث عنها غير موجودة أو تم نقلها. لنعُد بك إلى ما يستحق المشاهدة.',
                'backHome' => 'العودة إلى الرئيسية',
                'contact' => 'تواصل معنا',
            ],
            'search' => [
                'h1' => 'نتائج البحث',
                'resultsFor' => 'عرض نتائج عن',
                'matchesFound' => 'نتيجة مطابقة',
                'placeholder' => 'ابحث في الموقع…',
                'button' => 'بحث',
                'empty' => 'لا توجد نتائج. جرّب كلمة بحث مختلفة.',
                'emptyHint' => 'تحقق من الإملاء أو استخدم كلمات أعمّ.',
            ],
            'legal' => [
                'lastUpdated' => 'آخر تحديث',
                'tocTitle' => 'في هذه الصفحة',
                'reviewNote' => 'هذه مسودة لأغراض التصميم والتنسيق، ويجب مراجعتها من قبل مستشار قانوني قبل النشر.',
            ],
        ],
    ];

    private readonly string $locale;

    public function __construct(string $locale = self::DEFAULT_LOCALE)
    {
        $this->locale = isset(self::COPY[$locale]) ? $locale : self::DEFAULT_LOCALE;
    }

    /** @return array<string, string> */
    public function notFound(): array
    {
        return self::COPY[$this->locale]['notFound'];
    }

    /** @return array<string, string> */
    public function search(): array
    {
        return self::COPY[$this->locale]['search'];
    }

    /** @return array<string, string> */
    public function legal(): array
    {
        return self::COPY[$this->locale]['legal'];
    }
}
