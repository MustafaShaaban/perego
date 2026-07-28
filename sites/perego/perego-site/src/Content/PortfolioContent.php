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
     *   h1: string, intro: string, demoNote: string, all: string, noResults: string,
     *   clientLabel: string, groupLabel: string, galleryBadge: string,
     *   categories: array{video: string, motion: string, design: string, web: string}
     * }>
     */
    private const COPY = [
        'en' => [
            'h1' => 'Our Work',
            'intro' => 'A selection of projects across video, motion, design, and web.',
            'demoNote' => "Example projects shown below — to be replaced with Perego's real work.",
            'all' => 'All Projects',
            'noResults' => 'No projects in this category yet. Try another filter.',
            'clientLabel' => 'Client',
            'groupLabel' => 'Filter projects by service',
            // The badge on a work-grid tile whose icon is "Gallery label". Same word the services
            // mosaic uses (ServiceContent's `galleryBadge`), kept per-surface so either can be
            // reworded without dragging the other with it.
            'galleryBadge' => 'Gallery',
            'categories' => [
                'video' => 'Video Editing',
                'motion' => '2D Motion Graphics',
                'design' => 'Graphic Design',
                'web' => 'Website Making',
            ],
            'ctaTitle' => 'Have a project in mind?',
            'ctaBody' => "Tell us what you're working on and we'll help you shape the plan.",
            'ctaButton' => 'Start a Project',
        ],
        'ar' => [
            'h1' => 'أعمالنا',
            'intro' => 'مجموعة مختارة من المشاريع في الفيديو والموشن والتصميم والويب.',
            'demoNote' => 'المشاريع أدناه أمثلة توضيحية — سيتم استبدالها بأعمال بيريجو الحقيقية.',
            'all' => 'كل المشاريع',
            'noResults' => 'لا توجد مشاريع في هذه الفئة بعد. جرّب فلترًا آخر.',
            'clientLabel' => 'العميل',
            'groupLabel' => 'تصفية المشاريع حسب الخدمة',
            'galleryBadge' => 'معرض',
            'categories' => [
                'video' => 'مونتاج الفيديو',
                'motion' => 'موشن جرافيك ثنائي الأبعاد',
                'design' => 'التصميم الجرافيكي',
                'web' => 'إنشاء المواقع',
            ],
            'ctaTitle' => 'هل لديك مشروع في ذهنك؟',
            'ctaBody' => 'أخبرنا بما تعمل عليه وسنساعدك في بلورة الخطة.',
            'ctaButton' => 'ابدأ مشروعك',
        ],
    ];

    /**
     * Single-project (case study) labels — the meta labels, the five narrative section titles, the
     * related heading, and the closing CTA. Mirrors the handoff `content/{en,ar}.json → project`.
     *
     * @var array<string, array<string, mixed>>
     */
    private const PROJECT = [
        'en' => [
            'categoryLabel' => 'Category',
            'clientLabel' => 'Client',
            'yearLabel' => 'Year',
            'roleLabel' => 'Our role',
            'deliverablesLabel' => 'Deliverables',
            'overviewTitle' => 'Overview',
            'challengeTitle' => 'The Challenge',
            'approachTitle' => 'Our Approach',
            'solutionTitle' => 'The Solution',
            'resultTitle' => 'The Result',
            'relatedTitle' => 'Related projects',
            'galleryTitle' => 'Project gallery',
            'close' => 'Close gallery',
            'prev' => 'Previous project',
            'next' => 'Next project',
            'ctaTitle' => 'Like what you see?',
            'ctaBody' => "Let's create something worth watching together.",
            'ctaButton' => 'Start a Project',
        ],
        'ar' => [
            'categoryLabel' => 'الفئة',
            'clientLabel' => 'العميل',
            'yearLabel' => 'السنة',
            'roleLabel' => 'دورنا',
            'deliverablesLabel' => 'المُخرجات',
            'overviewTitle' => 'نظرة عامة',
            'challengeTitle' => 'التحدي',
            'approachTitle' => 'منهجيتنا',
            'solutionTitle' => 'الحل',
            'resultTitle' => 'النتيجة',
            'relatedTitle' => 'مشاريع ذات صلة',
            'galleryTitle' => 'معرض المشروع',
            'close' => 'إغلاق المعرض',
            'prev' => 'المشروع السابق',
            'next' => 'المشروع التالي',
            'ctaTitle' => 'أعجبك ما رأيت؟',
            'ctaBody' => 'لنصنع معًا شيئًا يستحق المشاهدة.',
            'ctaButton' => 'ابدأ مشروعك',
        ],
    ];

    private readonly string $locale;

    public function __construct(string $locale = self::DEFAULT_LOCALE)
    {
        $this->locale = isset(self::COPY[$locale]) ? $locale : self::DEFAULT_LOCALE;
    }

    /**
     * The localized single-project (case study) labels.
     *
     * @return array<string, mixed>
     */
    public function projectLabels(): array
    {
        return self::PROJECT[$this->locale];
    }

    /**
     * A single localized project label by key (empty string if unknown).
     */
    public function projectLabel(string $key): string
    {
        $value = self::PROJECT[$this->locale][$key] ?? '';

        return is_string($value) ? $value : '';
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
     * The Work-archive copy, with the portfolio-grid block's editable overrides applied (spec 021 C11).
     *
     * Every key falls back to this class's seed copy, so an unedited block renders exactly what it always
     * did. Only a non-empty override wins: clearing a field in the editor restores the seed rather than
     * blanking the page. `demoNote` is the one exception — it is a launch placeholder ("Example projects
     * shown below…"), so it takes an explicit `showDemoNote` boolean and an empty string genuinely removes
     * it. `groupLabel`, `noResults`, and `uiHome` stay seed-only: they are interface strings, not editorial
     * copy, and belong to the translation catalogue.
     *
     * @param array{heading?: string, intro?: string, ctaTitle?: string, ctaBody?: string, ctaButton?: string, showDemoNote?: bool} $overrides
     * @return array{groupLabel: string, noResults: string, galleryBadge: string, heading: string, intro: string, demoNote: string, uiHome: string, ctaTitle: string, ctaBody: string, ctaButton: string}
     */
    public function gridStrings(array $overrides = []): array
    {
        $pick = function (string $key, string $seedKey) use ($overrides): string {
            $value = $overrides[$key] ?? null;

            return is_string($value) && trim($value) !== '' ? $value : self::COPY[$this->locale][$seedKey];
        };

        return [
            'groupLabel' => self::COPY[$this->locale]['groupLabel'],
            'noResults' => self::COPY[$this->locale]['noResults'],
            'galleryBadge' => self::COPY[$this->locale]['galleryBadge'],
            'heading' => $pick('heading', 'h1'),
            'intro' => $pick('intro', 'intro'),
            'demoNote' => ($overrides['showDemoNote'] ?? true) ? self::COPY[$this->locale]['demoNote'] : '',
            'uiHome' => $this->uiHome(),
            'ctaTitle' => $pick('ctaTitle', 'ctaTitle'),
            'ctaBody' => $pick('ctaBody', 'ctaBody'),
            'ctaButton' => $pick('ctaButton', 'ctaButton'),
        ];
    }

    public function clientLabel(): string
    {
        return self::COPY[$this->locale]['clientLabel'];
    }

    /**
     * The localized "Home" breadcrumb root label (handoff `ui.breadcrumbHome`).
     */
    public function uiHome(): string
    {
        return $this->locale === 'ar' ? 'الرئيسية' : 'Home';
    }
}
