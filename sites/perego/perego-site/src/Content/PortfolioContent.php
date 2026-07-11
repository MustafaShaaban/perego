<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Content;

defined('ABSPATH') || exit;

use PeregoSite\PostTypes\ProjectPostType;

/**
 * Portfolio (work archive) copy (spec 003 / M3), locale-aware. Mirrors the handoff
 * `content/{en,ar}.json → portfolio`. Falls back to `en`. The filter labels map the four
 * ProjectPostType category slugs to localized service names; `all` leads.
 */
final class PortfolioContent
{
    private const DEFAULT_LOCALE = 'en';

    /**
     * @var array<string, array{
     *   h1: string, intro: string, all: string, noResults: string,
     *   clientLabel: string, groupLabel: string,
     *   categories: array{video: string, motion: string, design: string, web: string}
     * }>
     */
    private const COPY = [
        'en' => [
            'h1' => 'Our Work',
            'intro' => 'A selection of projects across video, motion, design, and web.',
            'all' => 'All Projects',
            'noResults' => 'No projects in this category yet. Try another filter.',
            'clientLabel' => 'Client',
            'groupLabel' => 'Filter projects by service',
            'categories' => [
                'video' => 'Video Editing',
                'motion' => '2D Motion Graphics',
                'design' => 'Graphic Design',
                'web' => 'Website Making',
            ],
        ],
        'ar' => [
            'h1' => 'أعمالنا',
            'intro' => 'مجموعة مختارة من المشاريع في الفيديو والموشن والتصميم والويب.',
            'all' => 'كل المشاريع',
            'noResults' => 'لا توجد مشاريع في هذه الفئة بعد. جرّب فلترًا آخر.',
            'clientLabel' => 'العميل',
            'groupLabel' => 'تصفية المشاريع حسب الخدمة',
            'categories' => [
                'video' => 'مونتاج الفيديو',
                'motion' => 'موشن جرافيك ثنائي الأبعاد',
                'design' => 'التصميم الجرافيكي',
                'web' => 'إنشاء المواقع',
            ],
        ],
    ];

    private readonly string $locale;

    public function __construct(string $locale = self::DEFAULT_LOCALE)
    {
        $this->locale = isset(self::COPY[$locale]) ? $locale : self::DEFAULT_LOCALE;
    }

    public function heading(): string
    {
        return self::COPY[$this->locale]['h1'];
    }

    public function intro(): string
    {
        return self::COPY[$this->locale]['intro'];
    }

    /**
     * The localized category label for a slug (falls back to the raw slug if unknown).
     */
    public function categoryLabel(string $slug): string
    {
        return self::COPY[$this->locale]['categories'][$slug] ?? $slug;
    }

    /**
     * Ordered filter labels: 'all' first, then the four categories in ProjectPostType order.
     *
     * @return array<string, string>
     */
    public function filterLabels(): array
    {
        $labels = ['all' => self::COPY[$this->locale]['all']];

        foreach (array_keys(ProjectPostType::CATEGORIES) as $slug) {
            $labels[$slug] = $this->categoryLabel($slug);
        }

        return $labels;
    }

    /**
     * @return array{groupLabel: string, noResults: string, heading: string, intro: string}
     */
    public function gridStrings(): array
    {
        return [
            'groupLabel' => self::COPY[$this->locale]['groupLabel'],
            'noResults' => self::COPY[$this->locale]['noResults'],
            'heading' => self::COPY[$this->locale]['h1'],
            'intro' => self::COPY[$this->locale]['intro'],
        ];
    }

    public function clientLabel(): string
    {
        return self::COPY[$this->locale]['clientLabel'];
    }
}
