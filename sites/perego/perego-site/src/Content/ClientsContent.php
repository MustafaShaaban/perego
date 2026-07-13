<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Content;

defined('ABSPATH') || exit;

/**
 * Homepage clients section copy (spec M6), locale-aware. Mirrors the handoff
 * `content/{en,ar}.json → home.clients`. Falls back to `en`.
 */
final class ClientsContent
{
    private const DEFAULT_LOCALE = 'en';

    /** @var array<string, array<string, string>> */
    private const COPY = [
        'en' => [
            'corporateTitle' => 'Corporate Clients',
            'corporateSubtitle' => "Organizations and companies we've collaborated with on professional projects.",
            'individualTitle' => 'Individual Clients',
            'individualSubtitle' => "Professionals and individuals we've worked with.",
            'viewWork' => 'View client work',
            'sectionLabel' => 'Clients',
        ],
        'ar' => [
            'corporateTitle' => 'عملاء الشركات',
            'corporateSubtitle' => 'مؤسسات وشركات تعاونّا معها في مشاريع احترافية.',
            'individualTitle' => 'عملاء أفراد',
            'individualSubtitle' => 'محترفون وأفراد عملنا معهم.',
            'viewWork' => 'استعرض أعمال العميل',
            'sectionLabel' => 'العملاء',
        ],
    ];

    private readonly string $locale;

    public function __construct(string $locale = self::DEFAULT_LOCALE)
    {
        $this->locale = isset(self::COPY[$locale]) ? $locale : self::DEFAULT_LOCALE;
    }

    public function get(string $key): string
    {
        return self::COPY[$this->locale][$key] ?? '';
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return self::COPY[$this->locale];
    }
}
